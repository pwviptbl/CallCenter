<?php

namespace App\Jobs;

use App\Events\ServiceRequestUpdated;
use App\Models\Message;
use App\Models\ServiceRequest;
use App\Services\ApiDispatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DispatchToCompanyApiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    /**
     * Intervalo de retry: 60s, 300s, 900s (1min, 5min, 15min).
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function __construct(
        public readonly int $serviceRequestId,
    ) {}

    public function handle(ApiDispatchService $dispatcher): void
    {
        $sr = ServiceRequest::with('company')->find($this->serviceRequestId);

        if (! $sr) {
            Log::warning("[ApiDispatch Job] SR #{$this->serviceRequestId} não encontrado.");
            return;
        }

        // Já foi enviado com sucesso anteriormente
        if ($sr->status === ServiceRequest::STATUS_RESOLVED) {
            return;
        }

        // Incrementa tentativas
        $sr->increment('api_attempts');

        $result = $dispatcher->dispatch($sr);

        if ($result['success']) {
            // Extrai ID do ticket externo quando disponível
            $externalId = $result['response']['id']
                ?? $result['response']['ticket_id']
                ?? $result['response']['numero']
                ?? null;

            $sr->update([
                'status'             => ServiceRequest::STATUS_RESOLVED,
                'api_response'       => $result['response'],
                'api_sent_at'        => now(),
                'external_ticket_id' => $externalId,
                'resolved_at'        => now(),
            ]);

            $sr->messages()->create([
                'direction'   => Message::DIRECTION_OUTBOUND,
                'sender_type' => Message::SENDER_SYSTEM,
                'content'     => '✅ Chamado registrado no sistema da empresa com sucesso.'
                    . ($externalId ? " Protocolo: {$externalId}" : ''),
                'is_read'     => false,
            ]);

            broadcast(new ServiceRequestUpdated($sr->fresh(), 'updated'));
        } else {
            // Se esgotou as tentativas → marca como falha
            if ($this->attempts() >= $this->tries) {
                $sr->update([
                    'status'       => ServiceRequest::STATUS_FAILED,
                    'api_response' => ['error' => $result['error'], 'status_code' => $result['status_code']],
                ]);

                $sr->messages()->create([
                    'direction'   => Message::DIRECTION_OUTBOUND,
                    'sender_type' => Message::SENDER_SYSTEM,
                    'content'     => "❌ Falha ao enviar para API da empresa após {$this->tries} tentativas: {$result['error']}",
                    'is_read'     => false,
                ]);

            broadcast(new ServiceRequestUpdated($sr->fresh(), 'updated'));

                Log::error("[ApiDispatch Job] SR #{$sr->id} → falhou definitivamente: {$result['error']}");
            } else {
                // Vai tentar novamente via backoff
                Log::warning("[ApiDispatch Job] SR #{$sr->id} tentativa {$this->attempts()}/{$this->tries} falhou: {$result['error']}");
                $this->release($this->backoff()[$this->attempts() - 1] ?? 60);
            }
        }
    }
}
