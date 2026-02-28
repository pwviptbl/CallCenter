# Stack Tecnológica

## Visão Geral

| Camada | Tecnologia | Versão | Custo MVP |
|---|---|---|---|
| Backend | Laravel | 11.x (PHP 8.3) | Gratuito |
| Frontend | Vue 3 + TailwindCSS | Vue 3.4+ / Tailwind 3.4+ | Gratuito |
| Banco de Dados | PostgreSQL | 16+ | Gratuito |
| Cache / Filas | Redis | 7+ | Gratuito |
| WebSocket | Laravel Reverb | Nativo Laravel 11 | Gratuito |
| WhatsApp | Evolution API | Self-hosted | ~R$ 50/mês (container) |
| LLM (IA) | OpenAI GPT-4o-mini | API | ~R$ 80–200/mês |
| VOIP (fase 2) | Asterisk | Self-hosted | Fase 2 |
| STT (fase 2) | OpenAI Whisper | API/Self-hosted | Fase 2 |
| Hospedagem | Coolify em VPS | Open-source | ~R$ 200–400/mês |
| CI/CD | GitHub Actions | - | Gratuito (repos privados) |
| SAST | Enlightn | - | Gratuito |

**Total infraestrutura MVP: R$ 330–650/mês**

---

## Backend — Laravel 11 (PHP 8.3)

### Por que Laravel?

- Ecossistema maduro para aplicações web com filas, WebSocket, autenticação
- Laravel Reverb (WebSocket nativo) elimina dependência de Pusher/Socket.io
- Laravel Queue + Redis para jobs assíncronos (IA, API externa)
- Eloquent ORM com suporte completo a PostgreSQL
- Comunidade ativa, documentação extensa, fácil contratação

### Pacotes Essenciais

| Pacote | Função |
|---|---|
| `laravel/reverb` | WebSocket para tempo real no painel |
| `laravel/sanctum` | Autenticação via token (SPA + API) |
| `openai-php/laravel` | SDK oficial OpenAI para GPT-4o-mini |
| `enlightn/enlightn` | Análise estática de segurança (SAST) |
| `spatie/laravel-permission` | Roles e permissões (admin, atendente, supervisor) |
| `spatie/laravel-activitylog` | Auditoria de ações no sistema |

---

## Frontend — Vue 3 + TailwindCSS

### Por que Vue 3?

- Reatividade nativa, ideal para painel em tempo real
- Composition API para lógica complexa de estado
- Integração natural com Laravel (Inertia.js ou SPA puro)
- TailwindCSS para UI rápida sem framework de componentes pesado

### Bibliotecas Frontend

| Biblioteca | Função |
|---|---|
| `vue-router` | Navegação SPA |
| `pinia` | Gerenciamento de estado |
| `laravel-echo` + `pusher-js` | Cliente WebSocket (conecta no Reverb) |
| `@headlessui/vue` | Componentes acessíveis (modais, dropdowns) |
| `dayjs` | Manipulação de datas |
| `vitest` | Testes unitários de componentes |

### Abordagem de Integração

**Opção escolhida: SPA puro (Vue 3) + API REST Laravel**

- Frontend separado, comunicação exclusivamente via API
- Mais flexível para futuro app mobile (mesma API)
- Deploy independente possível (CDN ou mesmo container)

---

## Banco de Dados — PostgreSQL 16

### Por que PostgreSQL?

- Suporte robusto a JSON/JSONB (campos dinâmicos por empresa)
- Performance excelente para queries com indexação adequada
- Gratuito, open-source, amplamente suportado
- Extensões úteis: `pg_trgm` (busca por similaridade), `uuid-ossp`

### Decisão: Sem RLS

O PostgreSQL suporta Row-Level Security, mas **não será utilizado** neste projeto:

- **Motivo**: o sistema é single-tenant. Uma empresa opera o callcenter e todos os atendentes acessam dados de todas as empresas clientes.
- **Isolamento**: lógico via `company_id` nas queries, enforced na camada de aplicação.
- **Vantagem**: queries mais simples, menos overhead, debugging facilitado.

Ver [seguranca.md](seguranca.md) para detalhes.

---

## Cache e Filas — Redis

### Funções

| Uso | Descrição |
|---|---|
| **Queue driver** | Jobs assíncronos: chamadas IA, envio API externa, notificações |
| **Cache** | Configurações de empresas, resultados de lookup frequentes |
| **Session** | Sessões dos atendentes |
| **Rate limiting** | Limitar requests por IP/usuário |
| **Broadcasting** | Backend do Laravel Reverb para WebSocket |

---

## WhatsApp — Evolution API

### MVP

- **Self-hosted** (Docker)
- Sem custo por mensagem (usa conexão WhatsApp Web)
- API REST para enviar/receber mensagens
- Webhook para receber mensagens dos condomínios
- Suporte a múltiplas instâncias (uma por número/empresa)

### Produção (evolução futura)

- **Meta Cloud API** (oficial) quando houver volume e necessidade de SLA
- Custo por mensagem, mas com garantia de estabilidade
- Mesma interface no backend (adapter pattern)

---

## IA — OpenAI GPT-4o-mini

### Função

- Conduzir coleta de campos obrigatórios em linguagem natural via WhatsApp
- Cada empresa tem campos diferentes configurados — a IA adapta a conversa automaticamente
- Script de abertura configurável por empresa (tom, saudação)

### Custo Estimado

- GPT-4o-mini: ~$0.15/1M tokens input, ~$0.60/1M tokens output
- Estimativa para 70 clientes com volume médio: **R$ 80–200/mês**

### Importante

- A IA **nunca** toma decisão de urgência — isso é do filtro determinístico
- A IA **nunca** bloqueia acesso ao humano
- Se a IA falhar na coleta após N tentativas, transfere para humano

---

## VOIP — Asterisk (Fase 2)

| Aspecto | Detalhe |
|---|---|
| Tecnologia | Asterisk self-hosted |
| STT | OpenAI Whisper (transcrição de áudio) |
| TTS | Voz sintética para atendimento por IA |
| Gravação | Áudio salvo e vinculado à solicitação |
| Identificação | Caller ID cruzado com base de condomínios |

**Não entra no MVP** — WhatsApp valida o conceito primeiro.

---

## Hospedagem — Coolify em VPS

### Coolify

- PaaS open-source (alternativa ao Vercel/Heroku)
- Deploy via Git push
- Gerencia containers Docker
- SSL automático (Let's Encrypt)
- Monitoramento básico incluso

### Containers Docker no Coolify

| Container | Serviço |
|---|---|
| `app` | Laravel (PHP-FPM + Nginx) |
| `worker` | Laravel Queue Worker |
| `reverb` | Laravel Reverb (WebSocket) |
| `postgres` | PostgreSQL 16 |
| `redis` | Redis 7 |
| `evolution` | Evolution API (WhatsApp) |

### VPS Recomendada (MVP)

- **4 vCPU, 8GB RAM, 80GB SSD**
- Provedores: Hetzner, Contabo, DigitalOcean
- Custo: R$ 200–400/mês

---

## CI/CD — GitHub Actions

### Pipeline

```yaml
# .github/workflows/ci.yml
on: [push, pull_request]

jobs:
  test:
    - PHP lint (phpstan)
    - Testes unitários (Pest)
    - Testes de integração (Pest)
    - SAST (Enlightn)
    - Frontend lint (eslint)
    - Frontend tests (vitest)

  deploy:
    - Build Docker image
    - Push para Coolify (webhook)
```

### SAST — Enlightn

- Análise estática de segurança específica para Laravel
- Verifica vulnerabilidades comuns: SQL injection, XSS, CSRF, mass assignment
- Roda automaticamente no pipeline CI/CD
