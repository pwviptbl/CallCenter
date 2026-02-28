# Estratégia de Testes

## Princípio

Testar antes de ir para produção **não é opcional**. Falhas no atendimento de emergência ou na integração de API geram impacto direto no negócio do cliente.

---

## Testes Automatizados (obrigatórios desde o dia zero)

### 1. Testes Unitários — PHPUnit / Pest

Cobrem a lógica isolada de cada componente.

| Componente | O que testar |
|---|---|
| **Filtro de urgência** | Cada keyword padrão dispara corretamente |
| | Keywords desativadas não disparam |
| | Keywords por empresa funcionam junto com globais |
| | Normalização de texto (acentos, maiúsculas) |
| | Word boundary (evitar falsos positivos) |
| | Reincidência de contato detectada |
| | Solicitação explícita de humano |
| | Fora do horário comercial |
| **Mapeamento de campos** | Payload montado corretamente com field mapping |
| | Campos ausentes tratados |
| | Campos extras ignorados |
| **Identificação de empresa** | Identificador por telefone encontra empresa correta |
| | Identificador por CNPJ encontra empresa correta |
| | Número não cadastrado retorna null |
| **Criptografia de API key** | Chave cifrada no banco, decifrada para uso |
| | Chave nunca aparece em serialização JSON |

**Ferramenta:** Pest (PHP)

```php
// Exemplo: teste do filtro de urgência
test('detects global emergency keyword "preso"', function () {
    $filter = new UrgencyFilter();
    $result = $filter->check('tem gente presa no elevador', $company, $phone);

    expect($result['is_urgent'])->toBeTrue();
    expect($result['reason'])->toBe('keyword_global');
});

test('does not trigger on word containing keyword', function () {
    $filter = new UrgencyFilter();
    $result = $filter->check('o elevador está empresado', $company, $phone);

    expect($result['is_urgent'])->toBeFalse();
});

test('normalizes accents before matching', function () {
    $filter = new UrgencyFilter();
    $result = $filter->check('tem fumaça saindo', $company, $phone);

    expect($result['is_urgent'])->toBeTrue();
    expect($result['matched_keyword'])->toBe('fumaca');
});
```

---

### 2. Testes de Integração — Pest + HTTP Fake

Cobrem o fluxo completo de um atendimento.

| Fluxo | O que testar |
|---|---|
| **Webhook WhatsApp → Registro** | Mensagem chega via webhook, empresa identificada, solicitação criada |
| **Urgência → Humano** | Mensagem com keyword → service_request com is_emergency=true |
| **Rotina → IA** | Mensagem sem keyword → job de IA enfileirado |
| **IA → Registro** | IA coleta campos → collected_data salvo corretamente |
| **Integração API** | Chamado enviado para API mock → status atualizado |
| **Erro de API** | API retorna 500 → retry + status api_error |
| **Transfer IA → Humano** | Contato pede atendente → evento WebSocket disparado |

```php
// Exemplo: teste de fluxo completo
test('webhook creates emergency service request', function () {
    $company = Company::factory()->create();
    UrgencyKeyword::factory()->create(['keyword' => 'preso', 'scope' => 'global']);

    $payload = [
        'event' => 'messages.upsert',
        'instance' => 'test-instance',
        'data' => [
            'key' => ['remoteJid' => '5511999887766@s.whatsapp.net', 'fromMe' => false],
            'message' => ['conversation' => 'estou preso no elevador'],
        ],
    ];

    $response = $this->postJson('/api/webhooks/whatsapp', $payload, [
        'X-Webhook-Secret' => config('services.evolution.webhook_secret'),
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('service_requests', [
        'company_id' => $company->id,
        'is_emergency' => true,
        'channel' => 'whatsapp',
    ]);
});
```

---

### 3. Testes de API Externa (Mock) — Laravel HTTP Fake

Simula respostas da API do cliente para validar todos os caminhos.

| Cenário | O que testar |
|---|---|
| API retorna 200 com ID | `external_id` salvo, status `sent_api` |
| API retorna 400 | Status `api_error`, resposta salva |
| API retorna 500 | Retry 3x, depois `api_error` |
| API timeout | Retry, depois `api_error` |
| API com payload errado | Erro de mapeamento tratado |

```php
test('sends service request to external API and saves response', function () {
    Http::fake([
        'api.cliente.com/*' => Http::response(['chamado_id' => 'OS-001'], 200),
    ]);

    $company = Company::factory()->create([
        'integration_mode' => 'api',
        'api_endpoint' => 'https://api.cliente.com/chamados',
    ]);

    $request = ServiceRequest::factory()->create(['company_id' => $company->id]);

    (new SendToExternalApi($request))->handle();

    expect($request->fresh())
        ->status->toBe('sent_api')
        ->external_id->toBe('OS-001');
});
```

---

### 4. Testes de Isolamento de Dados — Pest

Garantir que empresa A nunca acessa dados da empresa B.

| Caso | O que testar |
|---|---|
| Listagem filtrada | GET `/api/service-requests?company_id=A` não retorna dados de B |
| Detalhes | GET `/api/service-requests/{id_de_B}` não é acessível (nota: como é single-tenant, o atendente pode ver tudo por design — o teste valida que os **filtros** funcionam corretamente) |
| Scopes Eloquent | `ServiceRequest::forCompany($id)` filtra corretamente |

---

### 5. Testes Frontend — Vitest + Vue Test Utils

| Componente | O que testar |
|---|---|
| **Alerta de emergência** | Renderiza corretamente com dados da emergência |
| **Tela de atendimento** | Mostra campos obrigatórios da empresa |
| **Tela assistida** | Dados organizados para cópia/abertura manual |
| **Filtros do painel** | Filtros alteram query corretamente |
| **WebSocket** | Componente reage a eventos em tempo real |

---

## Testes Manuais com Cliente Real (Piloto)

### Checklist

| # | Teste | Status |
|---|---|---|
| 1 | Cadastrar empresa real com campos configurados | ⬜ |
| 2 | Simular atendimento WhatsApp — rotina | ⬜ |
| 3 | Simular atendimento WhatsApp — emergência | ⬜ |
| 4 | Verificar filtro de urgência dispara corretamente | ⬜ |
| 5 | Testar integração API com endpoint de teste do cliente | ⬜ |
| 6 | Testar modo assistido — dados chegam na tela do atendente | ⬜ |
| 7 | Registrar e revisar atendimentos no painel | ⬜ |
| 8 | Coletar feedback do atendente sobre usabilidade | ⬜ |
| 9 | Testar transferência IA → humano | ⬜ |
| 10 | Testar reincidência (mesmo número 2x em X min) | ⬜ |

### Recomendação

Rodar piloto com **uma empresa cliente** que tenha:
- Volume baixo de chamados
- Tolerância a ajustes
- Dados reais mas ambiente de homologação nas APIs externas

---

## Cobertura Mínima

| Área | Cobertura mínima |
|---|---|
| Filtro de urgência | 100% |
| Integração API externa | 95% |
| Fluxo de atendimento (webhook → registro) | 90% |
| Mapeamento de campos | 90% |
| Frontend (componentes críticos) | 80% |

---

## CI/CD

Todos os testes rodam automaticamente no pipeline:

```yaml
# .github/workflows/ci.yml
jobs:
  test:
    steps:
      - name: PHP Lint (PHPStan)
        run: vendor/bin/phpstan analyse

      - name: Backend Tests (Pest)
        run: vendor/bin/pest --coverage --min=85

      - name: Security Analysis (Enlightn)
        run: php artisan enlightn --ci

      - name: Frontend Lint (ESLint)
        run: npm run lint

      - name: Frontend Tests (Vitest)
        run: npm run test:unit

  deploy:
    needs: test
    # Deploy só ocorre se todos os testes passarem
```

**Regra**: merge só é permitido se todos os testes passarem. Sem exceção.
