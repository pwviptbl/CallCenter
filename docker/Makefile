# Makefile para facilitar comandos do projeto

.PHONY: help build up down restart logs shell composer npm artisan test

help: ## Mostrar este help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

build: ## Build dos containers Docker
	docker-compose build --no-cache

up: ## Subir containers
	docker-compose up -d

down: ## Parar containers
	docker-composer down

restart: ## Restart dos containers
	docker-compose restart

logs: ## Ver logs dos containers
	docker-compose logs -f

shell: ## Abrir shell no container app
	docker-compose exec app sh

composer-install: ## Instalar dependências PHP
	docker-compose exec app composer install

npm-install: ## Instalar dependências Node
	docker-compose exec app npm install

npm-dev: ## Rodar Vite em modo dev
	docker-compose exec app npm run dev

artisan: ## Executar comando artisan (ex: make artisan cmd="migrate")
	docker-compose exec app php artisan $(cmd)

migrate: ## Rodar migrations
	docker-compose exec app php artisan migrate

migrate-fresh: ## Resetar e rodar migrations
	docker-compose exec app php artisan migrate:fresh --seed

test: ## Rodar tests
	docker-compose exec app php artisan test

setup: build up composer-install npm-install key-generate migrate ## Setup inicial completo

key-generate: ## Gerar APP_KEY
	docker-compose exec app php artisan key:generate

cache-clear: ## Limpar cache
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear
