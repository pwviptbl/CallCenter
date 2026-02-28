# Modelagem de Dados

## Decisões de Design

- **Single-tenant**: sem RLS, sem schema-per-tenant. Uma única base de dados.
- **Isolamento lógico**: toda entidade relacionada a empresa cliente tem `company_id`.
- **Campos dinâmicos**: empresas configuram quais campos são obrigatórios para chamados — armazenados em JSONB.
- **Auditoria**: timestamps (`created_at`, `updated_at`) em todas as tabelas + `soft deletes` onde aplicável.
- **UUIDs**: chaves primárias UUID para entidades expostas via API (evita enumeração).

---

## Diagrama ER (Simplificado)

```
┌─────────────────┐       ┌─────────────────────┐
│     users        │       │    companies         │
│─────────────────│       │─────────────────────│
│ id (uuid, PK)   │       │ id (uuid, PK)        │
│ name             │       │ name                 │
│ email            │       │ identifiers (jsonb)  │
│ password         │       │ active_channels      │
│ role             │       │ required_fields(jsonb)│
│ is_active        │       │ integration_mode     │
│ created_at       │       │ api_endpoint         │
│ updated_at       │       │ api_key_encrypted    │
└─────────────────┘       │ api_field_mapping(j) │
                           │ ai_opening_script    │
                           │ emergency_keywords(j)│
                           │ settings (jsonb)     │
                           │ created_at           │
                           │ updated_at           │
                           │ deleted_at           │
                           └──────────┬──────────┘
                                      │
                                      │ 1:N
                                      ▼
┌────────────────────────────────────────────────────────┐
│                    service_requests                      │
│────────────────────────────────────────────────────────│
│ id (uuid, PK)                                           │
│ company_id (uuid, FK → companies)                       │
│ channel (enum: whatsapp, voip)                          │
│ contact_phone                                           │
│ contact_name                                            │
│ attended_by (enum: ai, human)                           │
│ attendant_id (uuid, FK → users, nullable)               │
│ is_emergency (boolean)                                  │
│ collected_data (jsonb)                                  │
│ integration_mode (enum: api, assisted, local)           │
│ status (enum: pending, sent_api, confirmed_manual,      │
│         registered_local, api_error)                    │
│ external_id (string, nullable)                          │
│ api_response (jsonb, nullable)                          │
│ created_at                                              │
│ updated_at                                              │
└────────────┬───────────────────────────────────────────┘
             │
             │ 1:N
             ▼
┌────────────────────────────────────────────┐
│          conversation_messages              │
│────────────────────────────────────────────│
│ id (uuid, PK)                              │
│ service_request_id (uuid, FK)              │
│ direction (enum: inbound, outbound)        │
│ sender_type (enum: contact, ai, attendant) │
│ content (text)                             │
│ media_url (string, nullable)               │
│ created_at                                 │
└────────────────────────────────────────────┘

┌────────────────────────────────────────────┐
│          call_recordings (fase 2)          │
│────────────────────────────────────────────│
│ id (uuid, PK)                              │
│ service_request_id (uuid, FK)              │
│ audio_path (string)                        │
│ transcription (text, nullable)             │
│ duration_seconds (integer)                 │
│ created_at                                 │
└────────────────────────────────────────────┘

┌────────────────────────────────────────────┐
│          urgency_keywords                  │
│────────────────────────────────────────────│
│ id (uuid, PK)                              │
│ keyword (string)                           │
│ scope (enum: global, company)              │
│ company_id (uuid, FK, nullable)            │
│ is_active (boolean, default: true)         │
│ created_at                                 │
│ updated_at                                 │
└────────────────────────────────────────────┘

┌────────────────────────────────────────────┐
│          whatsapp_instances                │
│────────────────────────────────────────────│
│ id (uuid, PK)                              │
│ company_id (uuid, FK → companies)          │
│ instance_name (string)                     │
│ phone_number (string)                      │
│ evolution_instance_id (string)             │
│ status (enum: connected, disconnected)     │
│ created_at                                 │
│ updated_at                                 │
└────────────────────────────────────────────┘

┌────────────────────────────────────────────┐
│          activity_logs                     │
│────────────────────────────────────────────│
│ id (bigint, PK)                            │
│ user_id (uuid, FK → users, nullable)       │
│ action (string)                            │
│ subject_type (string)                      │
│ subject_id (uuid)                          │
│ properties (jsonb)                         │
│ created_at                                 │
└────────────────────────────────────────────┘
```

---

## Detalhamento das Tabelas

### `users` — Operadores do callcenter

| Campo | Tipo | Descrição |
|---|---|---|
| `id` | UUID, PK | Identificador único |
| `name` | VARCHAR(255) | Nome do operador |
| `email` | VARCHAR(255), UNIQUE | E-mail de login |
| `password` | VARCHAR(255) | Hash bcrypt |
| `role` | ENUM('admin', 'supervisor', 'attendant') | Perfil de acesso |
| `is_active` | BOOLEAN, DEFAULT true | Se o usuário está ativo |
| `created_at` | TIMESTAMP | Criação |
| `updated_at` | TIMESTAMP | Última atualização |

**Observação**: são os funcionários do callcenter, não das empresas clientes.

---

### `companies` — Empresas clientes

| Campo | Tipo | Descrição |
|---|---|---|
| `id` | UUID, PK | Identificador único |
| `name` | VARCHAR(255) | Nome da empresa |
| `identifiers` | JSONB | Lista de identificadores: CNPJs, telefones, palavras que identificam a empresa |
| `active_channels` | VARCHAR[] | Canais ativos: `['whatsapp']`, `['whatsapp', 'voip']` |
| `required_fields` | JSONB | Campos obrigatórios para abrir chamado (ver abaixo) |
| `integration_mode` | ENUM('api', 'assisted', 'local') | Modo de integração padrão |
| `api_endpoint` | VARCHAR(500), nullable | URL do endpoint para envio de chamados |
| `api_key_encrypted` | TEXT, nullable | Chave de API cifrada (AES-256) |
| `api_field_mapping` | JSONB, nullable | De→Para entre campos do atendimento e campos da API |
| `ai_opening_script` | TEXT, nullable | Script de abertura da IA (tom, saudação) |
| `emergency_keywords` | JSONB | Keywords extras de emergência específicas da empresa |
| `settings` | JSONB | Configurações extras (horário atendimento, reincidência, etc.) |
| `created_at` | TIMESTAMP | Criação |
| `updated_at` | TIMESTAMP | Última atualização |
| `deleted_at` | TIMESTAMP, nullable | Soft delete |

#### Exemplo de `required_fields`

```json
[
  { "key": "condominium_name", "label": "Nome do condomínio", "type": "text", "required": true },
  { "key": "elevator_id", "label": "Número do elevador", "type": "text", "required": true },
  { "key": "floor", "label": "Andar de parada", "type": "text", "required": false },
  { "key": "problem_description", "label": "Descrição do problema", "type": "text", "required": true },
  { "key": "requester_name", "label": "Nome do solicitante", "type": "text", "required": true },
  { "key": "requester_phone", "label": "Telefone do solicitante", "type": "phone", "required": true }
]
```

#### Exemplo de `api_field_mapping`

```json
{
  "condominium_name": "cliente_nome",
  "elevator_id": "equipamento_codigo",
  "problem_description": "descricao",
  "requester_name": "solicitante",
  "requester_phone": "telefone"
}
```

#### Exemplo de `settings`

```json
{
  "business_hours": { "start": "07:00", "end": "18:00", "timezone": "America/Sao_Paulo" },
  "after_hours_mode": "human_only",
  "recontact_window_minutes": 15,
  "ai_max_retries": 3
}
```

---

### `service_requests` — Solicitações de atendimento

| Campo | Tipo | Descrição |
|---|---|---|
| `id` | UUID, PK | Identificador único |
| `company_id` | UUID, FK | Empresa cliente associada |
| `channel` | ENUM('whatsapp', 'voip') | Canal de entrada |
| `contact_phone` | VARCHAR(20) | Telefone do condomínio/solicitante |
| `contact_name` | VARCHAR(255), nullable | Nome identificado do contato |
| `attended_by` | ENUM('ai', 'human') | Quem conduziu o atendimento |
| `attendant_id` | UUID, FK, nullable | Atendente humano (se aplicável) |
| `is_emergency` | BOOLEAN, DEFAULT false | Se disparou o filtro de urgência |
| `collected_data` | JSONB | Dados coletados durante o atendimento |
| `integration_mode` | ENUM('api', 'assisted', 'local') | Modo usado nesta solicitação |
| `status` | ENUM | Status atual (ver abaixo) |
| `external_id` | VARCHAR(255), nullable | ID retornado pela API externa |
| `api_response` | JSONB, nullable | Resposta completa da API externa |
| `created_at` | TIMESTAMP | Data/hora de entrada do contato |
| `updated_at` | TIMESTAMP | Última atualização |

#### Status possíveis

| Status | Descrição |
|---|---|
| `pending` | Atendimento em andamento, dados sendo coletados |
| `sent_api` | Chamado enviado com sucesso via API |
| `confirmed_manual` | Atendente confirmou abertura manual no sistema externo |
| `registered_local` | Registrado localmente, repasse manual |
| `api_error` | Erro no envio via API (com detalhes em `api_response`) |
| `awaiting_review` | Aguardando revisão do atendente (modo assistido) |

#### Exemplo de `collected_data`

```json
{
  "condominium_name": "Edifício Aurora",
  "elevator_id": "ELV-002",
  "floor": "3º andar",
  "problem_description": "Elevador parado com porta aberta",
  "requester_name": "João Silva",
  "requester_phone": "11999887766"
}
```

---

### `conversation_messages` — Histórico de mensagens

Registra toda troca de mensagens de um atendimento (WhatsApp ou chat interno).

| Campo | Tipo | Descrição |
|---|---|---|
| `id` | UUID, PK | Identificador |
| `service_request_id` | UUID, FK | Solicitação associada |
| `direction` | ENUM('inbound', 'outbound') | Direção da mensagem |
| `sender_type` | ENUM('contact', 'ai', 'attendant') | Quem enviou |
| `content` | TEXT | Conteúdo da mensagem |
| `media_url` | VARCHAR(500), nullable | URL de mídia (foto, áudio) |
| `created_at` | TIMESTAMP | Timestamp da mensagem |

---

### `urgency_keywords` — Palavras-chave de urgência

| Campo | Tipo | Descrição |
|---|---|---|
| `id` | UUID, PK | Identificador |
| `keyword` | VARCHAR(100) | Palavra ou frase |
| `scope` | ENUM('global', 'company') | Se é global ou específica de empresa |
| `company_id` | UUID, FK, nullable | Empresa (se scope = company) |
| `is_active` | BOOLEAN, DEFAULT true | Se está ativa |

#### Keywords padrão (seed)

| Keyword | Scope |
|---|---|
| `preso` | global |
| `fumaca` | global |
| `queda` | global |
| `socorro` | global |
| `fogo` | global |
| `travado com pessoa` | global |
| `caiu` | global |
| `incendio` | global |
| `ajuda` | global |
| `emergencia` | global |

> As palavras-chave são configuráveis via interface admin. Os padrões acima são carregados via seed e podem ser ativados/desativados.

---

## Índices Recomendados

```sql
-- service_requests: queries mais frequentes
CREATE INDEX idx_service_requests_company_id ON service_requests(company_id);
CREATE INDEX idx_service_requests_status ON service_requests(status);
CREATE INDEX idx_service_requests_created_at ON service_requests(created_at DESC);
CREATE INDEX idx_service_requests_channel ON service_requests(channel);
CREATE INDEX idx_service_requests_is_emergency ON service_requests(is_emergency);
CREATE INDEX idx_service_requests_contact_phone ON service_requests(contact_phone);

-- conversation_messages: busca por solicitação
CREATE INDEX idx_conversation_messages_request ON conversation_messages(service_request_id);

-- urgency_keywords: lookup rápido
CREATE INDEX idx_urgency_keywords_scope ON urgency_keywords(scope, is_active);
CREATE INDEX idx_urgency_keywords_company ON urgency_keywords(company_id) WHERE company_id IS NOT NULL;

-- companies: busca por identificadores
CREATE INDEX idx_companies_identifiers ON companies USING GIN(identifiers);

-- GIN index para busca em collected_data
CREATE INDEX idx_service_requests_collected_data ON service_requests USING GIN(collected_data);
```

---

## Migrations (ordem)

1. `create_users_table`
2. `create_companies_table`
3. `create_urgency_keywords_table`
4. `create_service_requests_table`
5. `create_conversation_messages_table`
6. `create_whatsapp_instances_table`
7. `create_call_recordings_table` (fase 2)
8. `create_activity_logs_table`

---

## Seeds

1. **Usuário admin padrão**
2. **Keywords globais de urgência** (preso, fumaca, queda, socorro, fogo, etc.)
3. **Empresa de teste** (para desenvolvimento e piloto)
