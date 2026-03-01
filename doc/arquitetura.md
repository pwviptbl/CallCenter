# Arquitetura do Sistema

## Visão Geral

O sistema é uma aplicação **single-tenant** operada por uma empresa de callcenter que atende ~70 empresas clientes de manutenção de elevadores. A arquitetura é monolítica modular com comunicação em tempo real via WebSocket.

```
┌─────────────────────────────────────────────────────────────────┐
│                        INTERNET                                 │
│   Condomínios (WhatsApp / VOIP)                                 │
└──────────┬───────────────────────────────┬──────────────────────┘
           │                               │
           ▼                               ▼
┌─────────────────────┐       ┌──────────────────────┐
│   Evolution API     │       │   Asterisk (fase 2)  │
│   (WhatsApp)        │       │   (VOIP)             │
│   Self-hosted       │       │   Self-hosted         │
└────────┬────────────┘       └──────────┬───────────┘
         │                               │
         ▼                               ▼
┌─────────────────────────────────────────────────────────────────┐
│                      BACKEND (Laravel 11)                       │
│                                                                 │
│  ┌─────────────┐  ┌──────────────┐  ┌────────────────────┐     │
│  │ Filtro de   │  │ Motor IA     │  │ Serviço de         │     │
│  │ Urgência    │  │ (LLM)        │  │ Integração API     │     │
│  │ (síncrono)  │  │ GPT-4o-mini  │  │ Externa            │     │
│  └──────┬──────┘  └──────┬───────┘  └────────┬───────────┘     │
│         │                │                    │                 │
│  ┌──────┴────────────────┴────────────────────┴──────────┐     │
│  │              Camada de Domínio / Serviços              │     │
│  │  Empresas | Solicitações | Atendimentos | Canais       │     │
│  └───────────────────────┬───────────────────────────────┘     │
│                          │                                      │
│  ┌───────────────────────┴───────────────────────────────┐     │
│  │              Laravel Queue + Redis                     │     │
│  │  Jobs: enviar API externa, processar IA, notificações  │     │
│  └───────────────────────────────────────────────────────┘     │
│                                                                 │
│  ┌───────────────────────────────────────────────────────┐     │
│  │              Laravel Reverb (WebSocket)                │     │
│  │  Eventos: novo atendimento, alerta urgência, status    │     │
│  └───────────────────────────────────────────────────────┘     │
└──────────┬──────────────────────────────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────────────────────────────┐
│                      BANCO DE DADOS                             │
│                      PostgreSQL                                 │
│  (sem RLS — single-tenant, isolamento lógico por company_id)    │
└─────────────────────────────────────────────────────────────────┘

           ▲
           │ API REST + WebSocket
           │
┌─────────────────────────────────────────────────────────────────┐
│                      FRONTEND (Vue 3 + Tailwind)                │
│                                                                 │
│  ┌──────────────┐  ┌───────────────┐  ┌──────────────────┐     │
│  │ Painel do    │  │ Cadastro de   │  │ Painel de        │     │
│  │ Atendente    │  │ Empresas      │  │ Solicitações     │     │
│  └──────────────┘  └───────────────┘  └──────────────────┘     │
└─────────────────────────────────────────────────────────────────┘
```

## Princípios Arquiteturais

### 1. Monolito Modular

Não microserviços. O MVP é um monolito Laravel bem estruturado com módulos lógicos separados. Motivos:

- Equipe enxuta, deploy simplificado
- Coolify gerencia um único container principal
- Extração futura de módulos (ex: VOIP) pode ser feita sem refatoração massiva

### 2. Single-Tenant (sem multi-tenant)

O sistema é operado por **uma única empresa** (o callcenter). As empresas clientes são **dados dentro do sistema**, não tenants separados.

**Consequências:**
- Não precisa de RLS (Row-Level Security) no PostgreSQL
- Não precisa de database-per-tenant ou schema-per-tenant
- Filtro por `company_id` nas queries é suficiente
- Um único pool de conexões, um único banco
- Todos os atendentes veem todas as empresas (é o esperado — eles atendem todas)

### 3. Filtro de Urgência Determinístico

O filtro roda **antes** de qualquer IA, é síncrono e baseado em regex/keyword match. Nunca depende de inferência probabilística para decisões críticas de segurança.

### 4. Comunicação em Tempo Real

- **Laravel Reverb** (WebSocket nativo do Laravel 11) para push de eventos ao frontend
- Alertas de emergência, novos atendimentos e mudanças de status chegam instantaneamente ao painel
- Sem polling

### 5. Filas para Processamento Assíncrono

- **Laravel Queue + Redis** para jobs que podem demorar: chamadas à IA, envio de chamados via API externa, transcrições
- O atendente nunca espera a API do cliente responder — o job processa em background e atualiza o status

## Camadas da Aplicação

```
├── app/
│   ├── Http/
│   │   ├── Controllers/     # Controllers REST API
│   │   ├── Middleware/       # Auth, rate limiting, role-based access
│   │   └── Requests/        # Form requests / validação
│   ├── Models/              # Eloquent models
│   ├── Services/
│   │   ├── UrgencyFilter/   # Filtro de urgência (keyword match)
│   │   ├── AI/              # Integração com LLM (GPT-4o-mini)
│   │   ├── Integration/     # Envio de chamados via API externa
│   │   ├── WhatsApp/        # Comunicação com Evolution API
│   │   └── Channel/         # Abstração de canais (WhatsApp/VOIP)
│   ├── Jobs/                # Queue jobs assíncronos
│   ├── Events/              # Eventos para WebSocket
│   ├── Listeners/           # Event listeners
│   └── Policies/            # Authorization policies
├── resources/
│   └── js/                  # Vue 3 + Tailwind frontend
├── database/
│   └── migrations/          # Migrations PostgreSQL
└── config/
    └── urgency.php          # Keywords padrão de urgência
```

## Autenticação e Perfis de Usuário

O sistema implementa dois perfis de usuário com permissões diferenciadas:

| Aspecto | Detalhe |
|---|---|
| **Método** | Laravel Sanctum (token-based SPA auth) |
| **Token** | API token com namespace, expiração configurable |
| **Perfis** | Admin (gerenciamento) e Atendente (operação) |
| **Middleware** | `RequireAdmin` para rotas admin-only; `RequireActiveUser` para bloquear inativos |
| **Frontend Guard** | Router meta `requiresAdmin` previne acesso não-autorizado |

**Admin**: Acesso completo a CRUD de empresas, usuários, keywords, configurações, painel de atendimento.

**Atendente**: Acesso ao painel de atendimento e funcionalidades de teste/análise. Sem acesso a gerenciamento.

**Mais detalhes em [Perfis de Usuário](perfis.md).**

## Deploy e Infraestrutura

| Componente | Tecnologia | Hospedagem |
|---|---|---|
| Backend | Laravel 11 (PHP 8.3) | Container Docker via Coolify |
| Frontend | Vue 3 + Tailwind (SPA) | Servido pelo mesmo container ou CDN |
| Banco | PostgreSQL 16 | Container Docker via Coolify |
| Cache/Filas | Redis | Container Docker via Coolify |
| WebSocket | Laravel Reverb | Mesmo container do backend |
| WhatsApp | Evolution API | Container Docker separado |
| CI/CD | GitHub Actions | GitHub (gratuito) |
| SAST | Enlightn | Pipeline CI/CD |

## Decisão: Por que sem RLS?

O documento original (v2.0) mencionava RLS. Após análise, a decisão é **não usar RLS** porque:

1. **Não é multi-tenant**: existe apenas um "tenant" — a empresa operadora do callcenter
2. **Todos os atendentes atendem todas as empresas**: não há necessidade de isolar dados entre operadores
3. **O isolamento entre empresas clientes é lógico**: filtro por `company_id` nas queries, não por política de banco
4. **Complexidade desnecessária**: RLS adiciona overhead de configuração e debugging sem benefício real neste cenário
5. **Performance**: queries sem RLS são mais simples e previsíveis

O que **é necessário**:
- Autenticação robusta (JWT) para os operadores do callcenter
- Permissões por role (admin, atendente, supervisor)
- Criptografia das credenciais de API dos clientes
- HTTPS em todos os canais
