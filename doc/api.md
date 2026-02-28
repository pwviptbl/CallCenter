# API e Integrações

## APIs do Sistema

O sistema expõe APIs REST para o frontend (SPA) e recebe webhooks dos canais (WhatsApp/VOIP).

### Autenticação

- **Frontend ↔ Backend**: Laravel Sanctum (token SPA)
- **Webhooks (Evolution API)**: Secret compartilhado no header
- **APIs externas dos clientes**: Token/Key configurado por empresa (cifrado AES-256)

---

## Endpoints Internos (Backend → Frontend)

### Autenticação

| Método | Endpoint | Descrição |
|---|---|---|
| POST | `/api/auth/login` | Login do operador |
| POST | `/api/auth/logout` | Logout |
| GET | `/api/auth/me` | Dados do usuário logado |

### Empresas (Companies)

| Método | Endpoint | Descrição |
|---|---|---|
| GET | `/api/companies` | Listar empresas (paginado, filtros) |
| POST | `/api/companies` | Criar empresa |
| GET | `/api/companies/{id}` | Detalhes da empresa |
| PUT | `/api/companies/{id}` | Atualizar empresa |
| DELETE | `/api/companies/{id}` | Soft delete empresa |
| POST | `/api/companies/{id}/test-api` | Testar conexão com API externa |

### Solicitações (Service Requests)

| Método | Endpoint | Descrição |
|---|---|---|
| GET | `/api/service-requests` | Listar solicitações (paginado, filtros) |
| GET | `/api/service-requests/{id}` | Detalhes da solicitação |
| PUT | `/api/service-requests/{id}/assign` | Assumir atendimento (atendente) |
| PUT | `/api/service-requests/{id}/status` | Atualizar status |
| POST | `/api/service-requests/{id}/retry-api` | Retentar envio via API |
| GET | `/api/service-requests/{id}/messages` | Histórico de mensagens |
| POST | `/api/service-requests/{id}/messages` | Enviar mensagem (atendente) |

### Keywords de Urgência

| Método | Endpoint | Descrição |
|---|---|---|
| GET | `/api/urgency-keywords` | Listar keywords (global + por empresa) |
| POST | `/api/urgency-keywords` | Criar keyword |
| PUT | `/api/urgency-keywords/{id}` | Atualizar keyword |
| DELETE | `/api/urgency-keywords/{id}` | Desativar keyword |
| POST | `/api/urgency-keywords/test` | Testar mensagem contra keywords |

### WhatsApp Instances

| Método | Endpoint | Descrição |
|---|---|---|
| GET | `/api/whatsapp-instances` | Listar instâncias |
| POST | `/api/whatsapp-instances` | Criar instância |
| GET | `/api/whatsapp-instances/{id}/qrcode` | Obter QR code para parear |
| GET | `/api/whatsapp-instances/{id}/status` | Status da conexão |

### Usuários

| Método | Endpoint | Descrição |
|---|---|---|
| GET | `/api/users` | Listar operadores |
| POST | `/api/users` | Criar operador |
| PUT | `/api/users/{id}` | Atualizar operador |
| DELETE | `/api/users/{id}` | Desativar operador |

### Dashboard / Relatórios (fase 2)

| Método | Endpoint | Descrição |
|---|---|---|
| GET | `/api/dashboard/stats` | Estatísticas gerais |
| GET | `/api/dashboard/stats/company/{id}` | Estatísticas por empresa |

---

## Webhooks Recebidos

### WhatsApp (Evolution API)

```http
POST /api/webhooks/whatsapp
X-Webhook-Secret: {secret_configurado}
Content-Type: application/json

{
  "event": "messages.upsert",
  "instance": "empresa-elevatech",
  "data": {
    "key": {
      "remoteJid": "5511999887766@s.whatsapp.net",
      "fromMe": false,
      "id": "MSG123"
    },
    "message": {
      "conversation": "tem gente presa no elevador"
    },
    "messageTimestamp": "1709150400"
  }
}
```

**Pipeline do webhook:**
1. Validar secret
2. Extrair número do remetente
3. Identificar empresa (lookup por número/identificadores)
4. Executar filtro de urgência (síncrono)
5. Criar ou atualizar `service_request`
6. Salvar mensagem em `conversation_messages`
7. Rotear: IA (job na fila) ou humano (evento WebSocket)

---

## Integração com APIs Externas (Sistemas dos Clientes)

### Três Modos de Operação

Cada empresa configura seu modo. Pode ser alterado a qualquer momento sem afetar outros clientes.

### Modo 1 — API Automática

```
Backend                                API Externa do Cliente
   │                                         │
   │  POST {api_endpoint}                    │
   │  Authorization: Bearer {api_key}        │
   │  Content-Type: application/json         │
   │  Body: payload mapeado                  │
   │─────────────────────────────────────────▶│
   │                                         │
   │  200 OK                                 │
   │  { "chamado_id": "OS-2024-001" }       │
   │◀─────────────────────────────────────────│
```

#### Construção do Payload

O payload é construído usando `api_field_mapping` da empresa:

```php
// Dados coletados
$collectedData = [
    'condominium_name' => 'Edifício Aurora',
    'elevator_id' => 'ELV-002',
    'problem_description' => 'Porta não fecha',
    'requester_name' => 'João Silva',
    'requester_phone' => '11999887766',
];

// Mapeamento configurado na empresa
$mapping = [
    'condominium_name' => 'cliente_nome',
    'elevator_id' => 'equipamento_codigo',
    'problem_description' => 'descricao',
    'requester_name' => 'solicitante',
    'requester_phone' => 'telefone',
];

// Payload resultante (enviado para API do cliente)
$payload = [
    'cliente_nome' => 'Edifício Aurora',
    'equipamento_codigo' => 'ELV-002',
    'descricao' => 'Porta não fecha',
    'solicitante' => 'João Silva',
    'telefone' => '11999887766',
];
```

#### Tratamento de Erro

| Cenário | Ação |
|---|---|
| Sucesso (2xx) | Salvar `external_id`, status = `sent_api` |
| Erro 4xx | Salvar resposta, status = `api_error`, alertar atendente |
| Erro 5xx | Retry (3x com exponential backoff), depois `api_error` |
| Timeout | Retry, depois `api_error` |
| Erro de rede | Retry, depois `api_error` |

### Modo 2 — Assistido

Sem chamada a API externa. O atendente recebe os dados organizados em tela e abre o chamado manualmente no sistema do cliente.

### Modo 3 — Local

Sem chamada a API externa. Chamado registrado localmente. Repasse via canal configurado (telefone, e-mail, WhatsApp direto).

---

## Eventos WebSocket (Laravel Reverb)

### Canais

| Canal | Eventos | Quem escuta |
|---|---|---|
| `emergency-alerts` | Novo atendimento de emergência | Todos os atendentes |
| `service-requests` | Novo atendimento, mudança de status | Painel de solicitações |
| `attendant.{userId}` | Atendimento atribuído ao atendente | Atendente específico |

### Eventos

```typescript
// Novo atendimento de emergência
interface EmergencyAlert {
  service_request_id: string;
  company_name: string;
  contact_phone: string;
  message: string;
  matched_keyword: string;
  timestamp: string;
}

// Novo atendimento pendente
interface ServiceRequestCreated {
  service_request_id: string;
  company_name: string;
  channel: 'whatsapp' | 'voip';
  is_emergency: boolean;
  attended_by: 'ai' | 'human';
}

// Status atualizado
interface ServiceRequestUpdated {
  service_request_id: string;
  status: string;
  external_id?: string;
}

// Nova mensagem na conversa
interface NewMessage {
  service_request_id: string;
  direction: 'inbound' | 'outbound';
  sender_type: 'contact' | 'ai' | 'attendant';
  content: string;
  timestamp: string;
}
```

---

## Segurança nas APIs

| Aspecto | Controle |
|---|---|
| Autenticação frontend | Sanctum (token SPA com CSRF) |
| Autenticação webhook | Secret no header |
| Credenciais externas | AES-256, nunca expostas em logs ou respostas |
| Rate limiting | Throttle por IP e por usuário |
| Validação | Form Requests em todos os endpoints |
| CORS | Configurado apenas para domínios do frontend |
| HTTPS | Obrigatório em todos os canais |
