.PHONY: help build up down restart logs shell migrate fresh test

# Default target
help:
	@echo "IPAM Microservices - Docker Commands"
	@echo ""
	@echo "Usage: make [target]"
	@echo ""
	@echo "Targets:"
	@echo "  build      Build all Docker images"
	@echo "  up         Start all services"
	@echo "  down       Stop all services"
	@echo "  restart    Restart all services"
	@echo "  logs       View logs (all services)"
	@echo "  logs-auth  View auth service logs"
	@echo "  logs-ip    View IP service logs"
	@echo "  logs-gw    View gateway logs"
	@echo "  shell-auth Shell into auth service"
	@echo "  shell-ip   Shell into IP service"
	@echo "  shell-gw   Shell into gateway"
	@echo "  migrate    Run migrations on all services"
	@echo "  fresh      Fresh install (rebuild + migrate)"
	@echo "  test       Run tests"
	@echo "  clean      Remove all containers and volumes"

# Build all images
build:
	docker compose build

# Start all services
up:
	docker compose up -d

# Start with build
up-build:
	docker compose up -d --build

# Stop all services
down:
	docker compose down

# Restart all services
restart:
	docker compose restart

# View logs
logs:
	docker compose logs -f

logs-auth:
	docker compose logs -f auth-service

logs-ip:
	docker compose logs -f ip-service

logs-gw:
	docker compose logs -f gateway

# Shell access
shell-auth:
	docker compose exec auth-service sh

shell-ip:
	docker compose exec ip-service sh

shell-gw:
	docker compose exec gateway sh

# Run migrations
migrate:
	docker compose exec auth-service php artisan migrate --force
	docker compose exec ip-service php artisan migrate --force

# Fresh install
fresh: down
	docker compose down -v
	docker compose build --no-cache
	docker compose up -d
	@echo "Waiting for services to start..."
	sleep 10
	$(MAKE) migrate
	docker compose exec auth-service php artisan passport:install --force
	@echo "Fresh install complete!"

# Run tests
test:
	docker compose exec auth-service php artisan test
	docker compose exec ip-service php artisan test

# Clean everything
clean:
	docker compose down -v --rmi local
	docker system prune -f

# Health check
health:
	@curl -s http://localhost:8000/api/health | jq . || echo "Gateway not responding"

# Development mode (with local volumes)
dev:
	docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
