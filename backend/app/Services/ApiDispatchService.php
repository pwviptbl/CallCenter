<?php

namespace App\Services;

use App\Models\ServiceRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiDispatchService
{
    /**
     * Envia os dados coletados para o endpoint configurado na empresa.
     *
     * @return array{success: bool, status_code: int|null, response: array, error: string|null}
     */
    public function dispatch(ServiceRequest $sr): array
    {
        $company = $sr->company;

        if (! $company || ! $company->api_enabled || ! $company->api_endpoint) {
            return [
                'success'     => false,
                'status_code' => null,
                'response'    => [],
                'error'       => 'Empresa sem integração API configurada.',
            ];
        }

        $payload = $this->buildPayload($sr);
        $headers = $this->buildHeaders($company);
        $method  = strtolower($company->api_method ?? 'post');

        Log::info("[ApiDispatch] SR #{$sr->id} → {$method} {$company->api_endpoint}");

        try {
            $response = Http::withHeaders($headers)
                ->timeout(15)
                ->$method($company->api_endpoint, $payload);

            $success = $response->successful();

            Log::info("[ApiDispatch] SR #{$sr->id} resposta {$response->status()}");

            return [
                'success'     => $success,
                'status_code' => $response->status(),
                'response'    => $response->json() ?? [],
                'error'       => $success ? null : "HTTP {$response->status()}",
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("[ApiDispatch] SR #{$sr->id} timeout/conexão: {$e->getMessage()}");

            return [
                'success'     => false,
                'status_code' => null,
                'response'    => [],
                'error'       => "Timeout/conexão: {$e->getMessage()}",
            ];
        } catch (\Throwable $e) {
            Log::error("[ApiDispatch] SR #{$sr->id} erro inesperado: {$e->getMessage()}");

            return [
                'success'     => false,
                'status_code' => null,
                'response'    => [],
                'error'       => $e->getMessage(),
            ];
        }
    }

    // ── Privado ───────────────────────────────────────────────────────────────

    private function buildPayload(ServiceRequest $sr): array
    {
        return [
            'chamado' => [
                'id'              => $sr->id,
                'empresa_id'      => $sr->company_id,
                'canal'           => $sr->channel,
                'status'          => $sr->status,
                'urgencia'        => $sr->urgency_level,
                'contato_nome'    => $sr->contact_name,
                'contato_telefone'=> $sr->contact_phone,
                'mensagem_inicial'=> $sr->contact_message,
                'dados_coletados' => $sr->collected_data ?? [],
                'criado_em'       => $sr->created_at?->toIso8601String(),
            ],
        ];
    }

    private function buildHeaders(mixed $company): array
    {
        $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json'];

        // Adiciona API key da empresa se configurada
        if ($company->api_key) {
            $headers['Authorization'] = "Bearer {$company->api_key}";
        }

        // Mescla headers personalizados cadastrados na empresa
        if (! empty($company->api_headers)) {
            $headers = array_merge($headers, $company->api_headers);
        }

        return $headers;
    }
}
