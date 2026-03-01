<?php

namespace App\Jobs;

use App\Events\ServiceRequestUpdated;
use App\Jobs\DispatchToCompanyApiJob;
use App\Models\Message;
use App\Models\ServiceRequest;
use App\Services\AiCollectorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAiMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 60;

    public function __construct(
        public readonly int $serviceRequestId,
    ) {}

    public function handle(AiCollectorService $ai): void
    {
        $sr = ServiceRequest::find($this->serviceRequestId);

        if (! $sr) {
            Log::warning("[AI Job] ServiceRequest #{$this->serviceRequestId} não encontrado.");
            return;
        }

        // Não processa se já foi encerrada ou escalada para atendente
        if (in_array($sr->status, [
            ServiceRequest::STATUS_RESOLVED,
            ServiceRequest::STATUS_FAILED,
            ServiceRequest::STATUS_IN_PROGRESS,
        ])) {
            return;
        }

        // Verifica limite de turnos para evitar loop infinito
        $maxTurns = (int) config('services.ai.max_turns', 8);
        $aiMessages = $sr->messages()
            ->where('sender_type', Message::SENDER_AI)
            ->count();

        if ($aiMessages >= $maxTurns) {
            $this->escalateToHuman($sr, 'Limite de turnos da IA atingido.');
            return;
        }

        // Carrega histórico de mensagens (sem media)
        $messages = $sr->messages()
            ->whereIn('direction', ['inbound', 'outbound'])
            ->whereNotNull('content')
            ->orderBy('created_at')
            ->get();

        // Atualiza status para "coletando"
        if ($sr->status === ServiceRequest::STATUS_PENDING) {
            $sr->update(['status' => ServiceRequest::STATUS_AI_COLLECTING]);
        }

        $result = $ai->process($sr, $messages);

        match ($result['type']) {
            'message'  => $this->saveAiMessage($sr, $result['content']),
            'complete' => $this->completeCollection($sr, $result['content'], $result['data'] ?? []),
            'escalate' => $this->escalateToHuman($sr, $result['content']),
            default    => null,
        };
    }

    // ── Privado ───────────────────────────────────────────────────────────────

    private function saveAiMessage(ServiceRequest $sr, string $content): void
    {
        $sr->messages()->create([
            'direction'   => Message::DIRECTION_OUTBOUND,
            'sender_type' => Message::SENDER_AI,
            'content'     => $content,
            'is_read'     => false,
        ]);

        broadcast(new ServiceRequestUpdated($sr->fresh(), 'message'));
    }

    private function completeCollection(ServiceRequest $sr, string $rawContent, array $data): void
    {
        $company = $sr->company;
        $hasApi  = $company?->api_enabled && $company?->api_endpoint;

        $sr->update([
            'status'         => $hasApi ? ServiceRequest::STATUS_SENT_API : ServiceRequest::STATUS_AWAITING_REVIEW,
            'collected_data' => $data,
        ]);

        // Despacha para a API da empresa se estiver configurada
        if ($hasApi) {
            DispatchToCompanyApiJob::dispatch($sr->id)->onQueue('default');
        }

        $confirmMsg = $hasApi
            ? 'Obrigado! Suas informações foram registradas e encaminhadas. Você receberá uma confirmação em breve.'
            : 'Obrigado! Coletei todas as informações necessárias. Um atendente irá dar continuidade ao seu atendimento em breve.';

        $sr->messages()->create([
            'direction'   => Message::DIRECTION_OUTBOUND,
            'sender_type' => Message::SENDER_AI,
            'content'     => $confirmMsg,
            'is_read'     => false,
        ]);

        broadcast(new ServiceRequestUpdated($sr->fresh(), 'updated'));
        Log::info("[AI Job] SR #{$sr->id} coleta concluída. API dispatch: " . ($hasApi ? 'sim' : 'não'));
    }

    private function escalateToHuman(ServiceRequest $sr, string $reason): void
    {
        $sr->update(['status' => ServiceRequest::STATUS_AWAITING_REVIEW]);

        $sr->messages()->create([
            'direction'   => Message::DIRECTION_OUTBOUND,
            'sender_type' => Message::SENDER_SYSTEM,
            'content'     => "⚠️ Escalado para atendente humano. Motivo: {$reason}",
            'is_read'     => false,
        ]);

        broadcast(new ServiceRequestUpdated($sr->fresh(), 'escalated'));
        Log::info("[AI Job] SR #{$sr->id} escalado. Motivo: {$reason}");
    }
}
