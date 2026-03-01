<?php

namespace App\Http\Controllers\Api;

use App\Events\ServiceRequestUpdated;
use App\Jobs\ProcessAiMessageJob;
use App\Models\Message;
use App\Models\ServiceRequest;
use App\Models\WhatsappInstance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    /**
     * Recebe eventos da Evolution API.
     *
     * Payload esperado (Evolution API v2):
     * {
     *   "event": "messages.upsert",
     *   "instance": "demo-instance-01",
     *   "data": {
     *     "key": { "id": "...", "fromMe": false, "remoteJid": "5511912345678@s.whatsapp.net" },
     *     "pushName": "João Silva",
     *     "message": { "conversation": "Preciso de ajuda..." }
     *   }
     * }
     */
    public function handle(Request $request): JsonResponse
    {
        // Validação do secret (opcional mas recomendada)
        $secret = config('services.evolution.secret');
        if ($secret) {
            $headerSecret = $request->header('apikey') ?? $request->header('x-evolution-secret');
            if ($headerSecret !== $secret) {
                Log::warning('[Webhook] Secret inválido recebido.');
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        $payload = $request->all();
        $event   = $payload['event'] ?? null;

        Log::info('[Webhook] Evento recebido: ' . $event);

        // Só processa mensagens recebidas (não enviadas pelo bot)
        if ($event !== 'messages.upsert') {
            return response()->json(['ok' => true]);
        }

        $data = $payload['data'] ?? [];
        $key  = $data['key'] ?? [];

        // Ignora mensagens enviadas pelo próprio bot
        if (!empty($key['fromMe'])) {
            return response()->json(['ok' => true]);
        }

        $instanceKey = $payload['instance'] ?? null;
        $remoteJid   = $key['remoteJid'] ?? null;
        $waMessageId = $key['id'] ?? null;
        $pushName    = $data['pushName'] ?? 'Contato';
        $content     = $this->extractMessageContent($data['message'] ?? []);

        if (! $remoteJid || ! $content) {
            return response()->json(['ok' => true]);
        }

        // Normaliza telefone (+55XXXXXXXXXX)
        $phone = $this->normalizePhone($remoteJid);

        // Encontra a instância WhatsApp
        $instance = WhatsappInstance::where('instance_key', $instanceKey)
            ->where('is_active', true)
            ->first();

        if (! $instance) {
            Log::warning("[Webhook] Instância '{$instanceKey}' não encontrada ou inativa.");
            return response()->json(['ok' => true]);
        }

        // Busca SR aberta para este telefone ou cria nova
        $sr = ServiceRequest::where('company_id', $instance->company_id)
            ->where('contact_phone', $phone)
            ->whereNotIn('status', [
                ServiceRequest::STATUS_RESOLVED,
                ServiceRequest::STATUS_FAILED,
            ])
            ->latest()
            ->first();

        if (! $sr) {
            $sr = ServiceRequest::create([
                'company_id'           => $instance->company_id,
                'whatsapp_instance_id' => $instance->id,
                'contact_name'         => $pushName,
                'contact_phone'        => $phone,
                'contact_message'      => $content,
                'status'               => ServiceRequest::STATUS_PENDING,
                'urgency_level'        => ServiceRequest::URGENCY_NORMAL,
                'channel'              => ServiceRequest::CHANNEL_WHATSAPP,
            ]);

            broadcast(new ServiceRequestUpdated($sr, 'created'));
        }

        // Salva a mensagem inbound (evita duplicatas pelo ID do WhatsApp)
        $alreadyExists = $waMessageId
            ? $sr->messages()->where('whatsapp_message_id', $waMessageId)->exists()
            : false;

        if (! $alreadyExists) {
            $sr->messages()->create([
                'direction'          => Message::DIRECTION_INBOUND,
                'sender_type'        => Message::SENDER_CONTACT,
                'content'            => $content,
                'whatsapp_message_id'=> $waMessageId,
                'is_read'            => false,
            ]);
        }

        // Detecta urgência com keywords do banco
        $this->detectUrgency($sr, $content);

        // Dispara processamento pela IA se não houver atendente
        if (
            ! $sr->attendant_id &&
            in_array($sr->status, [
                ServiceRequest::STATUS_PENDING,
                ServiceRequest::STATUS_AI_COLLECTING,
            ])
        ) {
            ProcessAiMessageJob::dispatch($sr->id)->onQueue('default');
        }

        return response()->json(['ok' => true]);
    }

    // ── Privado ───────────────────────────────────────────────────────────────

    private function extractMessageContent(array $message): ?string
    {
        return $message['conversation']
            ?? $message['extendedTextMessage']['text']
            ?? $message['imageMessage']['caption']
            ?? $message['documentMessage']['caption']
            ?? null;
    }

    private function normalizePhone(string $jid): string
    {
        // Remove sufixo do WhatsApp: "5511912345678@s.whatsapp.net" → "+5511912345678"
        $digits = preg_replace('/[^\d]/', '', explode('@', $jid)[0]);
        return '+' . $digits;
    }

    private function detectUrgency(ServiceRequest $sr, string $content): void
    {
        if ($sr->urgency_level === ServiceRequest::URGENCY_CRITICAL) {
            return; // Já é crítico, não rebaixa
        }

        $keywords = \App\Models\UrgencyKeyword::where('active', true)
            ->where(function ($q) use ($sr) {
                $q->whereNull('company_id')
                  ->orWhere('company_id', $sr->company_id);
            })
            ->get();

        $lower   = mb_strtolower($content);
        $matched = collect();

        foreach ($keywords as $kw) {
            if (str_contains($lower, mb_strtolower($kw->keyword))) {
                $matched->push($kw);
            }
        }

        if ($matched->isEmpty()) {
            return;
        }

        $maxLevel = $matched->max('priority_level');
        $level = match (true) {
            $maxLevel >= 8  => ServiceRequest::URGENCY_CRITICAL,
            $maxLevel >= 5  => ServiceRequest::URGENCY_URGENT,
            default         => ServiceRequest::URGENCY_NORMAL,
        };

        if ($level !== $sr->urgency_level) {
            $sr->update([
                'urgency_level'    => $level,
                'urgency_keywords' => array_merge(
                    $sr->urgency_keywords ?? [],
                    $matched->pluck('keyword')->toArray(),
                ),
            ]);

            broadcast(new ServiceRequestUpdated($sr->fresh(), 'urgency'));
        }
    }
}
