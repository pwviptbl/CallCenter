# Canais de Atendimento

## Visão Geral

| Canal | MVP | Produção | Status |
|---|---|---|---|
| WhatsApp | Evolution API (self-hosted) | Meta Cloud API (oficial) | MVP |
| VOIP | — | Asterisk self-hosted | Fase 2 |

---

## WhatsApp — Evolution API

### Arquitetura

```
Condomínio (WhatsApp)
       │
       ▼
┌──────────────────────┐
│  Evolution API        │  Container Docker self-hosted
│  (instância WhatsApp) │  Conecta via WhatsApp Web
│                       │  REST API + Webhooks
└──────────┬───────────┘
           │ Webhook (POST)
           ▼
┌──────────────────────┐
│  Backend Laravel      │  Endpoint: POST /api/webhooks/whatsapp
│  WebhookController    │  Valida, identifica empresa, roda filtro
└──────────────────────┘
```

### Configuração por Empresa

Cada empresa cliente pode ter seu próprio número de WhatsApp no sistema. Uma instância do Evolution API por número.

| Configuração | Descrição |
|---|---|
| Número WhatsApp | Número dedicado da empresa no sistema |
| Instance ID | ID da instância no Evolution API |
| Status | Conectado / Desconectado |
| QR Code | Gerado na interface admin para parear |

### Identificação do Condomínio

Quando uma mensagem chega:
1. Extrair número do remetente
2. Cruzar com `companies.identifiers` (telefones cadastrados dos condomínios)
3. Se identificar → seguir fluxo normal
4. Se não identificar → IA tenta coletar informação / humano assume

### Funcionalidades

| Feature | Descrição |
|---|---|
| Receber mensagens | Webhook do Evolution API → endpoint Laravel |
| Enviar mensagens | REST API do Evolution API (texto, mídia) |
| Histórico | Toda mensagem salva em `conversation_messages` |
| Múltiplas instâncias | Um número por empresa, cada um em instância separada |
| Reconexão | Monitorar status da instância, alertar se desconectar |

### Webhook Payload (Evolution API)

```json
{
  "event": "messages.upsert",
  "instance": "empresa-elevatech",
  "data": {
    "key": {
      "remoteJid": "5511999887766@s.whatsapp.net",
      "fromMe": false,
      "id": "ABC123"
    },
    "message": {
      "conversation": "O elevador do bloco B parou no 3o andar"
    },
    "messageTimestamp": "1709150400"
  }
}
```

### Envio de Mensagem (Evolution API)

```http
POST /message/sendText/{instance}
Content-Type: application/json

{
  "number": "5511999887766",
  "text": "Olá! Sou o assistente da ElevaTech. Vou registrar sua solicitação."
}
```

### Evolução para Produção

Quando o volume justificar:
- Migrar para **Meta Cloud API** (oficial)
- Custo por mensagem, mas com SLA e estabilidade garantida
- Adapter pattern no backend: trocar implementação sem mudar lógica

```php
interface WhatsAppGateway
{
    public function sendText(string $phone, string $message): void;
    public function sendMedia(string $phone, string $mediaUrl, string $caption): void;
    public function getInstanceStatus(string $instanceId): string;
}

class EvolutionApiGateway implements WhatsAppGateway { /* MVP */ }
class MetaCloudApiGateway implements WhatsAppGateway { /* Produção */ }
```

---

## VOIP — Asterisk (Fase 2)

> **Não entra no MVP.** WhatsApp valida o conceito primeiro. VOIP será implementado na segunda fase.

### Arquitetura Planejada

```
Condomínio (Ligação)
       │
       ▼
┌──────────────────────┐
│  Asterisk             │  Self-hosted
│  (PBX)               │  Recebe ligações, grava, encaminha
└──────────┬───────────┘
           │ AMI/ARI
           ▼
┌──────────────────────┐
│  Backend Laravel      │  Integração com Asterisk via ARI
│                       │  Controla fluxo de chamada
└──────────────────────┘
```

### Funcionalidades Planejadas

| Feature | Descrição |
|---|---|
| Receber ligações | Asterisk como PBX, identifica caller ID |
| Atendimento IA | TTS (voz sintética) conduz coleta; Whisper (STT) transcreve |
| Transferência | Ligação transferida para atendente com popup de dados |
| Gravação | Áudio da ligação salvo e vinculado à solicitação |
| Transcrição | Whisper gera transcrição automática |

### Integração com Filtro de Urgência

No caso VOIP, o filtro opera sobre:
1. **Transcrição em tempo real** (streaming STT) da fala do condomínio
2. Se keyword detectada durante a ligação → transferência imediata para humano
3. Trabalha com latência de ~1-2 segundos (tempo do STT)

---

## Abstração de Canal

Para manter o código limpo, os canais são abstraídos:

```php
interface ChannelHandler
{
    public function receiveMessage(array $payload): IncomingMessage;
    public function sendMessage(string $recipient, string $content): void;
    public function getChannelType(): string; // 'whatsapp' | 'voip'
}

class WhatsAppChannelHandler implements ChannelHandler { /* ... */ }
class VoipChannelHandler implements ChannelHandler { /* Fase 2 */ }
```

Isso permite:
- Adicionar novos canais sem alterar lógica de negócio
- Filtro de urgência funciona igual para qualquer canal
- Registro unificado em `service_requests` independente do canal
