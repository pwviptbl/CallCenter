# Filtro de Urgência

## Resumo

O filtro de urgência é a **primeira lógica executada** ao receber qualquer contato (WhatsApp ou VOIP). É **determinístico**, **síncrono** e **sem IA**. Opera em milissegundos com regex/keyword match puro.

**Se detectar urgência → rota imediata para humano.**
**Se não detectar → rota para IA (rotina).**

---

## Princípios

| Princípio | Detalhe |
|---|---|
| **Sem IA** | Nunca usa inferência probabilística para decisões de segurança |
| **Síncrono** | Executa inline, sem fila, sem latência |
| **Determinístico** | Mesma entrada = mesmo resultado, sempre |
| **Configurável** | Keywords podem ser adicionadas/removidas via admin |
| **Duas camadas** | Keywords globais + keywords extras por empresa |

---

## Keywords Padrão (globais)

Essas keywords vêm pré-configuradas no seed do banco e são válidas para **todas** as empresas:

| Keyword | Ativa por padrão |
|---|---|
| `preso` | ✅ |
| `fumaca` | ✅ |
| `queda` | ✅ |
| `socorro` | ✅ |
| `fogo` | ✅ |
| `travado com pessoa` | ✅ |
| `caiu` | ✅ |
| `incendio` | ✅ |
| `ajuda` | ✅ |
| `emergencia` | ✅ |

> Todas são configuráveis. O admin pode desativar, editar ou adicionar novas keywords globais via interface.

---

## Keywords Extras por Empresa

Cada empresa cliente pode ter palavras-chave adicionais configuradas no seu cadastro. São verificadas **junto com** as globais.

Exemplo: uma empresa pode adicionar `"desabamento"`, `"alagamento"` se operar em região com riscos específicos.

---

## Critérios de Roteamento para Humano

O filtro não verifica apenas keywords. São **múltiplos critérios**:

| # | Critério | Descrição |
|---|---|---|
| 1 | **Keywords globais** | Mensagem contém alguma keyword global ativa |
| 2 | **Keywords da empresa** | Mensagem contém alguma keyword extra da empresa identificada |
| 3 | **Fora do horário** | Empresa configurou que fora do horário comercial vai direto para humano |
| 4 | **Reincidência** | Mesmo número ligando pela 2ª vez em menos de X minutos (configurável) |
| 5 | **Falha da IA** | IA não conseguiu identificar empresa ou completar campos após N tentativas |
| 6 | **Solicitação explícita** | Contato digitou/falou "falar com atendente" a qualquer momento |

**Qualquer um** desses critérios sendo verdadeiro → humano.

---

## Lógica de Implementação

### Pseudocódigo

```php
class UrgencyFilter
{
    /**
     * Verifica se a mensagem deve ser roteada para humano.
     * Retorno: ['is_urgent' => bool, 'reason' => string|null, 'matched_keyword' => string|null]
     */
    public function check(string $message, ?Company $company, string $contactPhone): array
    {
        $normalized = $this->normalize($message);

        // 1. Verificar keywords globais
        $globalKeywords = $this->getActiveGlobalKeywords();
        foreach ($globalKeywords as $keyword) {
            if ($this->matchesKeyword($normalized, $keyword)) {
                return [
                    'is_urgent' => true,
                    'reason' => 'keyword_global',
                    'matched_keyword' => $keyword,
                ];
            }
        }

        // 2. Verificar keywords da empresa
        if ($company) {
            $companyKeywords = $this->getCompanyKeywords($company->id);
            foreach ($companyKeywords as $keyword) {
                if ($this->matchesKeyword($normalized, $keyword)) {
                    return [
                        'is_urgent' => true,
                        'reason' => 'keyword_company',
                        'matched_keyword' => $keyword,
                    ];
                }
            }

            // 3. Verificar horário
            if ($this->isOutsideBusinessHours($company)) {
                return [
                    'is_urgent' => true,
                    'reason' => 'outside_business_hours',
                    'matched_keyword' => null,
                ];
            }
        }

        // 4. Verificar reincidência
        if ($this->isRecontact($contactPhone, $company)) {
            return [
                'is_urgent' => true,
                'reason' => 'recontact',
                'matched_keyword' => null,
            ];
        }

        // 5. Verificar solicitação explícita de humano
        if ($this->requestsHuman($normalized)) {
            return [
                'is_urgent' => true,
                'reason' => 'explicit_request',
                'matched_keyword' => null,
            ];
        }

        return ['is_urgent' => false, 'reason' => null, 'matched_keyword' => null];
    }

    private function normalize(string $text): string
    {
        // Lowercase, remover acentos, normalizar espaços
        $text = mb_strtolower($text);
        $text = $this->removeAccents($text);
        $text = preg_replace('/\s+/', ' ', trim($text));
        return $text;
    }

    private function matchesKeyword(string $normalizedText, string $keyword): bool
    {
        $normalizedKeyword = $this->normalize($keyword);
        // Word boundary match para evitar falsos positivos
        $pattern = '/\b' . preg_quote($normalizedKeyword, '/') . '\b/u';
        return (bool) preg_match($pattern, $normalizedText);
    }

    private function requestsHuman(string $text): bool
    {
        $humanPhrases = [
            'falar com atendente',
            'quero um humano',
            'atendente humano',
            'falar com pessoa',
            'atendente por favor',
        ];
        foreach ($humanPhrases as $phrase) {
            if (str_contains($text, $this->normalize($phrase))) {
                return true;
            }
        }
        return false;
    }
}
```

### Normalização de Texto

Antes de verificar keywords:
1. Converter para lowercase
2. Remover acentos (`fumação` → `fumacao`, `incêndio` → `incendio`)
3. Normalizar espaços múltiplos
4. NÃO fazer stemming ou lemmatization (mantém determinístico)

### Cache de Keywords

As keywords são carregadas do banco e cacheadas em Redis:
- Cache key: `urgency:keywords:global` e `urgency:keywords:company:{id}`
- TTL: 5 minutos (ou invalidar no update)
- Permite atualizar keywords sem redeploy

---

## O que Acontece no Roteamento para Humano

1. **Alerta visual e sonoro** no painel do atendente disponível
2. **Popup com dados** coletados até o momento (nome, número, empresa identificada, mensagens/áudio)
3. **Atendente assume imediatamente** sem o condomínio perceber transição
4. **Registro marcado** como "Atendimento humano" com motivo da urgência

---

## Configuração via Admin

### Interface de Gerenciamento

O admin pode:
- ✅ Listar todas as keywords (globais e por empresa)
- ✅ Adicionar nova keyword (global ou para empresa específica)
- ✅ Desativar/ativar keyword sem deletar
- ✅ Editar keyword existente
- ✅ Testar keyword (simular mensagem e ver se dispara)

### Config file de fallback

```php
// config/urgency.php
return [
    'default_keywords' => [
        'preso',
        'fumaca',
        'queda',
        'socorro',
        'fogo',
    ],
    'recontact_window_minutes' => 15,
    'ai_max_retries' => 3,
    'human_request_phrases' => [
        'falar com atendente',
        'quero um humano',
        'atendente humano',
        'falar com pessoa',
    ],
];
```

---

## Testes Obrigatórios

| Caso de Teste | Esperado |
|---|---|
| Mensagem com "preso no elevador" | `is_urgent: true`, reason: keyword_global |
| Mensagem com "fumaca saindo" | `is_urgent: true`, reason: keyword_global |
| Mensagem com "elevador parado" (sem keyword) | `is_urgent: false` |
| Mensagem com keyword desativada | `is_urgent: false` |
| Mensagem com keyword extra da empresa | `is_urgent: true`, reason: keyword_company |
| Mensagem fora do horário (empresa config) | `is_urgent: true`, reason: outside_business_hours |
| Mesmo número ligando 2x em 10min | `is_urgent: true`, reason: recontact |
| "quero falar com atendente" | `is_urgent: true`, reason: explicit_request |
| Mensagem "preso" com acento "prêso" | `is_urgent: true` (normalização) |
| Mensagem com "empresou" (contém "preso"?) | `is_urgent: false` (word boundary) |

> Todos esses testes devem existir desde o dia zero. O filtro de urgência é funcionalidade crítica de segurança.
