# CallCenter — Sistema de Atendimento para Manutenção de Elevadores

Plataforma independente operada pelo callcenter. Não substitui nem replica o software de gestão de manutenção que as empresas clientes já possuem ou venham a contratar separadamente. Os dois sistemas são independentes e se comunicam opcionalmente via API.

## Estrutura do Projeto

```
CallCenter/
├── backend/          # Laravel 11 (PHP 8.3)
├── frontend/         # Vue 3 + TypeScript + Tailwind
├── docker/          # Dockerfiles e configs
├── doc/             # Documentação técnica
└── docker-compose.yml
```

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 11 (PHP 8.3) |
| Frontend | Vue 3 + TypeScript + Tailwind |
| Banco | PostgreSQL 16 |
| Cache/Filas | Redis |
| WebSocket | Laravel Reverb |
| WhatsApp | Evolution API (self-hosted) |
| IA | OpenAI GPT-4o-mini |
| Deploy | Coolify em VPS |
| CI/CD | GitHub Actions + Enlightn |

## Início Rápido com Docker

### Pré-requisitos

- Docker e Docker Compose
- Make (opcional, facilita comandos)

### Setup Inicial

```bash
# Clonar repositório
git clone https://github.com/pwviptbl/CallCenter.git
cd CallCenter

# Setup completo (build, up, install, migrate)
make setup

# Ou manualmente:
docker-compose build
docker-compose up -d
docker-compose exec backend composer install
docker-compose exec backend cp .env.example .env
docker-compose exec backend php artisan key:generate
docker-compose exec backend php artisan migrate
docker-compose exec frontend npm install
```

### URLs

- **Frontend**: http://localhost:5173
- **Backend API**: http://localhost:8000
- **PostgreSQL**: localhost:5432
- **Redis**: localhost:6379

### Comandos Úteis

```bash
make up              # Iniciar containers
make down            # Parar containers
make logs            # Ver logs
make backend-shell   # Shell do backend
make frontend-shell  # Shell do frontend
make migrate         # Executar migrations
make test            # Executar testes
```

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

## Licença

Proprietário - Todos os direitos reservados
