# Fluxos de Atendimento

## Fluxo Principal

Todo contato — WhatsApp ou VOIP — passa pelo mesmo pipeline:

```
ENTRADA DO CONTATO
(WhatsApp | VOIP)
       │
       ▼
┌──────────────────────────┐
│  1. IDENTIFICAR EMPRESA  │  Cruzar número/palavras com base de companies.identifiers
│     (lookup rápido)      │  Se não identificar → humano (com dados do contato)
└──────────┬───────────────┘
           │
           ▼
┌──────────────────────────┐
│  2. FILTRO DE URGÊNCIA   │  Síncrono, sem IA, regex/keyword match
│     (determinístico)     │  Verifica: keywords globais + empresa + horário + reincidência
└─────┬──────────┬─────────┘
      │          │
  URGÊNCIA    ROTINA
      │          │
      ▼          ▼
┌───────────┐  ┌────────────────┐
│  HUMANO   │  │  IA (LLM)      │
│           │  │  Coleta campos  │
│  Alerta   │  │  obrigatórios   │
│  visual + │  │  config. da     │
│  sonoro   │  │  empresa        │
└─────┬─────┘  └───────┬────────┘
      │                │
      │    ┌───────────┘
      │    │  (IA pode transferir para humano a qualquer momento)
      ▼    ▼
┌──────────────────────────┐
│  3. REGISTRO             │  TODA solicitação é registrada
│     (service_requests)   │  independente do modo
└──────────┬───────────────┘
           │
           ▼
┌──────────────────────────────────────────┐
│  4. INTEGRAÇÃO (depende do modo)         │
│                                          │
│  Modo 1 (API)    → POST automático       │
│  Modo 2 (Assist) → Tela para atendente   │
│  Modo 3 (Local)  → Repasse manual        │
└──────────────────────────────────────────┘
```

---

## Fluxo Detalhado: Atendimento WhatsApp (Rotina via IA)

```
1. Condomínio envia mensagem no WhatsApp
         │
         ▼
2. Evolution API recebe e envia webhook para o backend
         │
         ▼
3. Backend identifica a empresa pelo número/identificadores
         │
         ▼
4. Filtro de urgência analisa a mensagem
         │ (resultado: ROTINA)
         ▼
5. Sistema cria service_request (status: pending)
         │
         ▼
6. IA (GPT-4o-mini) inicia conversa com script da empresa
   "Olá! Sou o assistente da [Empresa]. Como posso ajudar?"
         │
         ▼
7. IA coleta campos obrigatórios configurados para a empresa
   Loop: pergunta → resposta → valida → próximo campo
         │
         │  (se contato pede "falar com atendente" → transfere)
         │  (se IA falha após N tentativas → transfere)
         ▼
8. Todos os campos coletados
         │
         ▼
9. Sistema atualiza collected_data no service_request
         │
         ▼
10. Executa integração conforme modo da empresa:
    - API: job na fila para POST no endpoint
    - Assistido: notifica atendente via WebSocket
    - Local: marca como registrado
```

---

## Fluxo Detalhado: Atendimento de Emergência

```
1. Condomínio envia "tem gente presa no elevador"
         │
         ▼
2. Filtro detecta keyword "presa" → EMERGÊNCIA
         │
         ▼
3. Sistema cria service_request (is_emergency: true, status: pending)
         │
         ▼
4. Evento WebSocket disparado IMEDIATAMENTE:
   - Canal: emergency-alerts
   - Dados: telefone, empresa, mensagem, timestamp
         │
         ▼
5. Painel do atendente:
   ┌─────────────────────────────────────────┐
   │  🔴 EMERGÊNCIA — Edifício Aurora        │
   │  "tem gente presa no elevador"          │
   │  Tel: (11) 99988-7766                    │
   │  Empresa: ElevaTech Manutenção          │
   │  Há 3 segundos                           │
   │  [ASSUMIR ATENDIMENTO]                   │
   └─────────────────────────────────────────┘
   + Alerta sonoro
         │
         ▼
6. Atendente clica em "Assumir"
   - service_request.attendant_id = user_id
   - service_request.attended_by = 'human'
         │
         ▼
7. Atendente conversa diretamente pelo painel (mensagens via WhatsApp)
   Todos os dados já em tela, campos preenchíveis
         │
         ▼
8. Atendente abre chamado manualmente no sistema externo
   (com todos os dados organizados em tela)
         │
         ▼
9. Atendente confirma -> status: confirmed_manual
```

---

## Fluxo: Transferência IA → Humano (a qualquer momento)

```
1. IA está coletando dados normalmente
         │
         ▼
2. Contato digita "quero falar com atendente"
   OU IA falha na coleta (N tentativas)
   OU novo critério de urgência detectado
         │
         ▼
3. IA responde: "Transferindo para um atendente. Um momento."
         │
         ▼
4. Evento WebSocket: novo atendimento pendente para humano
   Com TODOS os dados já coletados pela IA até aquele ponto
         │
         ▼
5. Atendente assume com contexto completo:
   - Mensagens anteriores visíveis
   - Campos já preenchidos pela IA
   - Empresa identificada
         │
         ▼
6. Atendente continua coleta + abre chamado
```

---

## Fluxo: Modo 1 — Integração API Automática

```
1. Dados coletados (por IA ou humano)
         │
         ▼
2. Job na fila (Laravel Queue):
   - Monta payload conforme api_field_mapping da empresa
   - POST HTTP para api_endpoint da empresa
   - Headers: Authorization com api_key decifrada
         │
         ▼
3. Resposta da API:
   ┌─ Sucesso (2xx):
   │  - Extrai ID do chamado criado
   │  - service_request.external_id = ID
   │  - service_request.status = 'sent_api'
   │  - service_request.api_response = resposta completa
   │
   └─ Erro (4xx/5xx/timeout):
      - service_request.status = 'api_error'
      - service_request.api_response = detalhes do erro
      - Alerta no painel para atendente revisar
      - Retry automático (3 tentativas com backoff)
```

---

## Fluxo: Modo 2 — Atendimento Assistido

```
1. Dados coletados (por IA ou humano)
         │
         ▼
2. service_request.status = 'awaiting_review'
         │
         ▼
3. Notificação no painel do atendente:
   "Novo chamado pronto para abertura manual — [Empresa]"
         │
         ▼
4. Atendente abre tela de revisão:
   ┌─────────────────────────────────────────┐
   │  Empresa: ElevaTech                      │
   │  Condomínio: Edifício Aurora             │
   │  Elevador: ELV-002                       │
   │  Problema: Porta não fecha               │
   │  Solicitante: João Silva                 │
   │  Tel: (11) 99988-7766                    │
   │                                          │
   │  [COPIAR DADOS] [ABRIR SISTEMA EXTERNO]  │
   │  [CONFIRMAR ABERTURA]                     │
   └─────────────────────────────────────────┘
         │
         ▼
5. Atendente abre chamado no sistema externo manualmente
         │
         ▼
6. Clica "Confirmar Abertura" → status: confirmed_manual
```

---

## Fluxo: Modo 3 — Registro Local

```
1. Dados coletados (por IA ou humano)
         │
         ▼
2. service_request.status = 'registered_local'
         │
         ▼
3. Atendente é notificado que precisa repassar:
   - Canal de repasse configurado (telefone, e-mail, WhatsApp direto)
         │
         ▼
4. Atendente repassa manualmente usando os dados em tela
         │
         ▼
5. Registro fica salvo no histórico do sistema
```

---

## Regras Transversais

| Regra | Descrição |
|---|---|
| **Tudo é registrado** | Independente do modo, canal ou quem atendeu — toda solicitação fica no banco |
| **IA nunca bloqueia humano** | Contato pode pedir humano a qualquer momento |
| **Urgência sempre priorizada** | Filtro roda antes de qualquer processamento |
| **Transferência preserva contexto** | Ao mudar de IA para humano, todo histórico e dados coletados são mantidos |
| **IDs externos rastreados** | Se a API retorna ID do chamado criado, ele é salvo para referência |
