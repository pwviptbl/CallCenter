# MVP e Roadmap

## Filosofia do MVP

O MVP valida o **fluxo central**: receber contato → identificar empresa → coletar campos → registrar → integrar. Tudo além disso é segunda fase.

---

## Escopo do MVP

### O que entra

| # | Funcionalidade | Observação |
|---|---|---|
| ✅ | Cadastro de empresas com campos configurados | Interface web simples, sem sofisticação visual |
| ✅ | Filtro de urgência (keyword match) | Regex puro, zero IA, zero latência |
| ✅ | Atendimento via WhatsApp (Evolution API) | Self-hosted, sem custo por mensagem |
| ✅ | IA de triagem rotina (LLM) | GPT-4o-mini: custo baixo por token |
| ✅ | Painel do atendente em tempo real | Fila de atendimentos, alertas, dados do contato |
| ✅ | Registro de todas as solicitações | Com dados coletados, canal, empresa, status |
| ✅ | Integração via API (Modo 1) | POST para endpoint configurado, registro de resposta |
| ✅ | Tela de atendimento assistido (Modo 2) | Dados organizados em tela para abertura manual |
| ✅ | Autenticação e permissões | Não é opcional — é o dia zero |
| ✅ | Keywords de urgência configuráveis | Padrões: preso, fumaca, queda, socorro, fogo |

### O que fica para fase 2

| Funcionalidade | Motivo de adiar |
|---|---|
| Atendimento via VOIP | Custo e complexidade maiores; WhatsApp valida o conceito primeiro |
| Transcrição de áudio (Whisper) | Depende do VOIP estar funcionando |
| Relatórios e dashboards avançados | Dados precisam existir antes de visualizar |
| App mobile dedicado | PWA web já serve no MVP |
| Múltiplos números de WhatsApp por empresa | Um número por empresa já valida o fluxo |

---

## Custo do MVP

| Componente | Custo mensal |
|---|---|
| VPS (Coolify + containers) | R$ 200–400 |
| Evolution API (infra do container) | ~R$ 50 |
| GPT-4o-mini (70 clientes) | R$ 80–200 |
| **TOTAL** | **R$ 330–650/mês** |

Sem custos de licença de software. Tudo open-source exceto a API da OpenAI.

---

## Roadmap de Entrega

### Fase 1 — MVP

| # | Etapa | Entregável | Dependência | Status |
|---|---|---|---|---|
| 1 | **Setup do projeto** | Laravel + Vue + PostgreSQL + Coolify + CI/CD + SAST configurados | — | ✅ Concluído |
| 2 | **Cadastro de empresas** | CRUD completo com configuração de campos e API | Etapa 1 | ⬜ Pendente |
| 3 | **Filtro de urgência** | Keyword match testado para todos os cenários de emergência | Etapa 1 | ⬜ Pendente |
| 4 | **Atendimento WhatsApp + IA** | Evolution API + LLM coletando campos configurados por empresa | Etapas 2, 3 | ⬜ Pendente |
| 5 | **Painel do atendente** | Fila em tempo real, alertas, tela de atendimento assistido | Etapas 2, 3, 4 | ⬜ Pendente |
| 6 | **Registro de solicitações** | Histórico completo, filtros, busca por empresa e período | Etapas 4, 5 | ⬜ Pendente |
| 7 | **Integração via API (Modo 1)** | Envio automático + registro de resposta + tratamento de erros | Etapas 2, 6 | ⬜ Pendente |
| 8 | **Testes automatizados** | Cobertura de filtro, integração, fluxo completo | Etapas 3, 7 | ⬜ Pendente |
| 9 | **Piloto com 1 cliente** | Atendimento real, feedback, ajustes finos | Etapa 8 | ⬜ Pendente |
| 10 | **Rollout geral** | Onboarding dos 70 clientes em ondas de 10 por semana | Etapa 9 | ⬜ Pendente |

### Fase 2 — Expansão

| # | Etapa | Entregável |
|---|---|---|
| 11 | VOIP (Asterisk) | Receber ligações, integrar com filtro e painel |
| 12 | STT (Whisper) | Transcrição automática de áudio das ligações |
| 13 | TTS (Voz sintética) | IA atende por voz, conduz coleta via VOIP |
| 14 | Relatórios e dashboards | Gráficos, métricas, exportação |
| 15 | Múltiplos números WhatsApp | Mais de um número por empresa |
| 16 | App mobile (PWA avançado) | Notificações push, acesso offline para supervisores |

---

## Ordem de Implementação Recomendada (Etapas 1–3)

### Etapa 1 — Setup (~1 semana)

```
1. Criar repositório GitHub com branch protection
2. Scaffold Laravel 11 com PHP 8.3
3. Configurar PostgreSQL (sem RLS)
4. Configurar Redis (cache + queue)
5. Scaffold Vue 3 + Tailwind (SPA)
6. Configurar Coolify para deploy automático
7. Pipeline CI/CD (GitHub Actions):
   - PHPStan
   - Pest
   - Enlightn
   - ESLint
   - Vitest
8. Configurar Evolution API (container Docker)
9. Documentar variáveis de ambiente (.env.example)
```

### Etapa 2 — Cadastro de Empresas (~1 semana)

```
1. Migration: companies table (com JSONB fields)
2. Model Company com casts (encrypted, json)
3. API CRUD: /api/companies
4. Form Requests (validação)
5. Frontend: tela de listagem + formulário
6. Tabs: dados gerais, campos obrigatórios, API, IA, urgência
7. Seed: empresa de teste
8. Testes: CRUD + validação + criptografia de API key
```

### Etapa 3 — Filtro de Urgência (~3 dias)

```
1. Migration: urgency_keywords table
2. Model UrgencyKeyword
3. Service: UrgencyFilter (lógica de match)
4. Config: config/urgency.php (fallback)
5. Seed: keywords padrão (preso, fumaca, queda, socorro, fogo)
6. API CRUD: /api/urgency-keywords
7. API teste: /api/urgency-keywords/test
8. Frontend: tela de gerenciamento + testador
9. Cache: Redis para keywords ativas
10. Testes: 100% de cobertura do filtro
```

---

## Perguntas Pendentes (para reunião de levantamento)

### Integração e Clientes
- Quantos dos 70 clientes já têm sistema com API disponível?
- Formato mais comum de API? (REST, SOAP, webhook?)
- Algum cliente já pediu integração automática?

### Atendimento Atual
- Volume médio de chamados por dia? E nos picos?
- Quantos atendentes trabalham simultaneamente?
- Já existe alguma triagem de urgência?
- Condomínios já usam WhatsApp para abrir chamados?

### Empresas Clientes
- Variação grande no que cada empresa precisa saber para abrir chamado?
- Restrições de horário de atendimento específicas?
- Empresas que não querem integração e preferem outro canal?
