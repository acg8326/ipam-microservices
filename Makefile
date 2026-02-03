.PHONY: help build up down restart logs shell migrate seed fresh test

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
	@echo "  seed       Seed database with default users"
	@echo "  fresh      Fresh install (rebuild + migrate + seed)"
	@echo "  test       Run all tests (backend + frontend)"
	@echo "  test-be    Run backend tests only"
	@echo "  test-fe    Run frontend tests only"
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

# Seed database with default users
seed:
	docker compose exec auth-service php artisan db:seed --force
	@echo "Database seeded with default users:"
	@echo "  Admin: admin@example.com / password123"
	@echo "  User:  user@example.com / password123"

# Fresh install
fresh: down
	docker compose down -v
	docker compose build --no-cache
	docker compose up -d
	@echo "Waiting for services to start..."
	sleep 10
	$(MAKE) migrate
	$(MAKE) seed
	docker compose exec auth-service php artisan passport:keys --force
	docker compose exec auth-service php artisan passport:client --personal --no-interaction
	@echo ""
	@echo "Fresh install complete!"
	@echo ""
	@echo "Default login credentials:"
	@echo "  Admin: admin@example.com / password123"
	@echo "  User:  user@example.com / password123"

# Run all tests
test: test-be test-fe

# Run backend tests
test-be:
	@echo "Running Auth Service tests..."
	docker compose exec auth-service php artisan test
	@echo "Running IP Service tests..."
	docker compose exec ip-service php artisan test
	@echo "Running Gateway tests..."
	docker compose exec gateway php artisan test

# Run frontend tests
test-fe:
	@echo "Running Frontend tests..."
	cd frontend && npm run test:run

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
