.PHONY: help up down build restart logs backend-shell frontend-shell migrate seed test

help: ## Mostrar este help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

up: ## Iniciar todos os containers
	docker-compose up -d

down: ## Parar todos os containers
	docker-compose down

build: ## Build de todos os containers
	docker-compose build

restart: ## Reiniciar todos os containers
	docker-compose restart

logs: ## Ver logs de todos os containers
	docker-compose logs -f

backend-shell: ## Acessar shell do backend
	docker-compose exec backend bash

frontend-shell: ## Acessar shell do frontend
	docker-compose exec frontend sh

migrate: ## Executar migrations
	docker-compose exec backend php artisan migrate

migrate-fresh: ## Executar migrations fresh
	docker-compose exec backend php artisan migrate:fresh --seed

seed: ## Executar seeders
	docker-compose exec backend php artisan db:seed

test: ## Executar testes do backend
	docker-compose exec backend php artisan test

install-backend: ## Instalar dependências do backend
	docker-compose exec backend composer install

install-frontend: ## Instalar dependências do frontend
	docker-compose exec frontend npm install

setup: build up ## Setup inicial completo
	@echo "Aguardando containers iniciarem..."
	@sleep 5
	@echo "Instalando dependências do backend..."
	@docker-compose exec backend composer install
	@echo "Copiando .env..."
	@docker-compose exec backend cp .env.example .env || true
	@echo "Gerando key..."
	@docker-compose exec backend php artisan key:generate
	@echo "Executando migrations..."
	@docker-compose exec backend php artisan migrate
	@echo "Instalando dependências do frontend..."
	@docker-compose exec frontend npm install
	@echo ""
	@echo "✅ Setup completo!"
	@echo ""
	@echo "Backend: http://localhost:8000"
	@echo "Frontend: http://localhost:5173"
