<?php

namespace App\Services;

use App\Models\Message;
use App\Models\ServiceRequest;
use Illuminate\Support\Collection;
use OpenAI\Laravel\Facades\OpenAI as OpenAIFacade;

class AiCollectorService
{
    /**
     * Prompt base enviado como sistema.
     * O assistente coleta dados estruturados do contato via conversa natural.
     */
    private function systemPrompt(): string
    {
        return <<<PROMPT
Você é um atendente virtual de call center. Sua função é identificar e coletar,
via conversa natural em português, as seguintes informações do contato:

1. nome_completo – Nome completo do solicitante
2. descricao_problema – Descrição detalhada do problema ou solicitação
3. urgencia_percebida – Se o contato considera urgente (sim/não) e porquê
4. dados_adicionais – Qualquer informação extra relevante (endereço, número de contrato, etc.)

Regras:
- Seja cordial, objetivo e empático.
- Faça UMA pergunta de cada vez.
- Quando tiver coletado todas as informações necessárias, responda SOMENTE com o JSON:
  {"completo": true, "dados": {"nome_completo":"...","descricao_problema":"...","urgencia_percebida":"...","dados_adicionais":"..."}}
- Enquanto ainda estiver coletando, responda normalmente em texto (sem JSON).
- Nunca invente informações. Se o contato não fornecer algo, marque como "não informado".
- Se o contato demonstrar urgência extrema (vida em risco, emergência), responda:
  {"escalar": true, "motivo": "..."}
PROMPT;
    }

    /**
     * Processa o histórico de mensagens e retorna a próxima resposta da IA.
     *
     * @return array{type: 'message'|'complete'|'escalate', content: string, data?: array}
     */
    public function process(ServiceRequest $serviceRequest, Collection $messages): array
    {
        $history = $this->buildHistory($messages);

        try {
            $response = OpenAIFacade::chat()->create([
                'model'       => config('services.openai.model', 'gpt-4o-mini'),
                'messages'    => array_merge(
                    [['role' => 'system', 'content' => $this->systemPrompt()]],
                    $history,
                ),
                'temperature' => 0.4,
                'max_tokens'  => 400,
            ]);

            $text = trim($response->choices[0]->message->content ?? '');

            return $this->parseResponse($text);
        } catch (\Throwable $e) {
            \Log::error('[AI] Erro ao chamar OpenAI: ' . $e->getMessage());
            return [
                'type'    => 'message',
                'content' => 'Desculpe, estou com dificuldades técnicas. Um atendente irá lhe contatar em breve.',
            ];
        }
    }

    /**
     * Converte as mensagens do banco para o formato da API OpenAI.
     */
    private function buildHistory(Collection $messages): array
    {
        return $messages->map(function (Message $msg) {
            return [
                'role'    => $msg->direction === 'inbound' ? 'user' : 'assistant',
                'content' => $msg->content ?? '',
            ];
        })->values()->toArray();
    }

    /**
     * Interpreta a resposta bruta da IA.
     */
    private function parseResponse(string $text): array
    {
        // Verifica se é JSON de conclusão ou escalada
        if (str_starts_with($text, '{')) {
            $json = json_decode($text, true);

            if (is_array($json)) {
                if (!empty($json['completo'])) {
                    return ['type' => 'complete', 'content' => $text, 'data' => $json['dados'] ?? []];
                }

                if (!empty($json['escalar'])) {
                    return ['type' => 'escalate', 'content' => $json['motivo'] ?? 'Escalado pela IA'];
                }
            }
        }

        return ['type' => 'message', 'content' => $text];
    }
}
