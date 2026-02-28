# CallCenter — Software de Atendimento para Manutenção de Elevadores

Plataforma independente operada pelo callcenter. Não substitui nem replica o software de gestão de manutenção que as empresas clientes já possuem ou venham a contratar separadamente. Os dois sistemas são independentes e se comunicam opcionalmente via API.

## Visão Geral

Sistema de atendimento (callcenter) que gerencia solicitações de manutenção de elevadores para ~70 empresas clientes. Recebe contatos via WhatsApp (e futuramente VOIP), aplica filtro de urgência determinístico, roteia para IA ou humano, e registra/integra chamados.

**Arquitetura**: single-tenant (uma empresa operadora, sem multi-tenant, sem RLS)

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 11 (PHP 8.3) |
| Frontend | Vue 3 + TailwindCSS |
| Banco | PostgreSQL 16 |
| Cache/Filas | Redis |
| WebSocket | Laravel Reverb |
| WhatsApp | Evolution API (self-hosted) |
| IA | OpenAI GPT-4o-mini |
| Deploy | Coolify em VPS |
| CI/CD | GitHub Actions + Enlightn |

## Documentação

Toda a documentação técnica está em [`doc/`](doc/README.md):

- [Arquitetura](doc/arquitetura.md) — Visão geral, camadas, decisões
- [Stacks](doc/stacks.md) — Tecnologias, justificativas, custos
- [Modelagem](doc/modelagem.md) — Banco de dados, entidades, relacionamentos
- [Filtro de Urgência](doc/filtro-urgencia.md) — Keywords configuráveis, lógica síncrona
- [Fluxos](doc/fluxos.md) — Fluxos completos de atendimento
- [Canais](doc/canais.md) — WhatsApp, VOIP
- [API e Integrações](doc/api.md) — Endpoints, webhooks, modos de integração
- [Painel](doc/painel.md) — Interface do atendente
- [Segurança](doc/seguranca.md) — Auth, criptografia, sem RLS
- [Testes](doc/testes.md) — Estratégia de testes
- [MVP e Roadmap](doc/mvp-roadmap.md) — Escopo, fases, cronograma
