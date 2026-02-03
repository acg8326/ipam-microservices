# Docker Documentation

## Overview

The IPAM Microservices project is fully containerized using Docker and Docker Compose. Each service runs in its own container with nginx + PHP-FPM managed by supervisord.

## Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           Docker Network                                 │
│                            (ipam-network)                                │
│                                                                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │   Frontend   │  │   Gateway    │  │ Auth Service │  │  IP Service  │ │
│  │    :3000     │  │   :8000      │  │  (internal)  │  │  (internal)  │ │
│  │              │  │              │  │              │  │              │ │
│  │    nginx     │  │ nginx + fpm  │  │ nginx + fpm  │  │ nginx + fpm  │ │
│  └──────────────┘  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘ │
│                           │                 │                 │          │
│                           │          ┌──────┴───────┐  ┌──────┴───────┐ │
│                           │          │  MySQL Auth  │  │  MySQL IP    │ │
│                           │          │    :3306     │  │    :3307     │ │
│                           │          │  (internal)  │  │  (internal)  │ │
│                           │          └──────────────┘  └──────────────┘ │
└───────────────────────────┼──────────────────────────────────────────────┘
                            │
                     External Access
              Frontend: localhost:3000
              API: localhost:8000
```

## Quick Start

### Prerequisites
- Docker Engine 20.10+
- Docker Compose v2+
- (Optional) Make - for simplified commands

### Option 1: Using Make (Linux/macOS)

```bash
cd ipam-microservices
cp .env.example .env
make fresh
```

### Option 2: Without Make (Windows/Linux/macOS)

```bash
cd ipam-microservices

# Windows: use 'copy .env.example .env'
cp .env.example .env

# Build and start all containers
docker compose up -d --build

# Wait 10-15 seconds for MySQL to initialize, then run:
docker compose exec auth-service php artisan migrate --force
docker compose exec ip-service php artisan migrate --force
docker compose exec auth-service php artisan db:seed --force
docker compose exec auth-service php artisan passport:install --force
```

### Default Login Credentials

After setup, the following users are available:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@example.com | password123 |
| User | user@example.com | password123 |

> **Note:** Change these credentials immediately in production environments.

### Verify Services

```bash
# Check health endpoint
curl http://localhost:8000/api/health

# Expected response:
{
    "gateway": "healthy",
    "services": {
        "auth": {"status": "healthy", "latency_ms": 50},
        "ip": {"status": "healthy", "latency_ms": 45}
    }
}
```

## Docker Compose Files

### docker-compose.yml (Production)

Main orchestration file with:
- MySQL containers for Auth and IP services
- PHP application containers with nginx
- Named volumes for data persistence
- Health checks for all services
- Restart policies for production

### docker-compose.dev.yml (Development)

Override file for development with:
- Volume mounts for live code reloading
- Exposed database ports for debugging
- No restart policies (manual control)

**Usage:**
```bash
# Development mode
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d

# Or using Make
make dev
```

## Container Details

### Base Image

All services use `php:8.2-fpm-alpine`:
- PHP 8.2 with FastCGI Process Manager
- Alpine Linux (~50MB base)
- Minimal attack surface

### Installed Extensions

| Extension | Purpose |
|-----------|---------|
| pdo_mysql | MySQL database connectivity |
| bcmath | Arbitrary precision mathematics |
| gd | Image processing |

### Process Management

Each container runs **supervisord** managing:
- `nginx` - Web server (port 80)
- `php-fpm` - PHP FastCGI processor

### Directory Structure

```
services/{service}/
├── Dockerfile              # Multi-stage build definition
├── .dockerignore           # Files excluded from build
└── docker/
    ├── nginx.conf          # Nginx server configuration
    ├── supervisord.conf    # Process manager configuration
    └── entrypoint.sh       # Container initialization script
```

## Environment Configuration

### Root .env File

```bash
# MySQL Auth Database
MYSQL_AUTH_ROOT_PASSWORD=secret
MYSQL_AUTH_DATABASE=auth_db
MYSQL_AUTH_USER=auth_user
MYSQL_AUTH_PASSWORD=auth_password

# MySQL IP Database
MYSQL_IP_ROOT_PASSWORD=secret
MYSQL_IP_DATABASE=ip_db
MYSQL_IP_USER=ip_user
MYSQL_IP_PASSWORD=ip_password

# Application
APP_ENV=local
APP_DEBUG=true
```

### Service Environment Variables

Set in docker-compose.yml per service:

| Variable | Description |
|----------|-------------|
| APP_KEY | Laravel encryption key (auto-generated) |
| APP_URL | Service URL |
| DB_CONNECTION | Database driver (mysql) |
| DB_HOST | Database container name |
| DB_DATABASE | Database name |
| DB_USERNAME | Database user |
| DB_PASSWORD | Database password |
| AUTH_SERVICE_URL | Auth service endpoint (gateway only) |
| IP_SERVICE_URL | IP service endpoint (gateway only) |

## Makefile Commands

| Command | Description |
|---------|-------------|
| `make up` | Start all services |
| `make up-build` | Build and start all services |
| `make down` | Stop and remove containers |
| `make build` | Build all images |
| `make logs` | View all logs |
| `make logs-auth` | View auth service logs |
| `make logs-ip` | View IP service logs |
| `make logs-gw` | View gateway logs |
| `make shell-auth` | Shell into auth container |
| `make shell-ip` | Shell into IP container |
| `make shell-gw` | Shell into gateway container |
| `make migrate` | Run migrations in all services |
| `make fresh` | Fresh migrate + seed all services |
| `make clean` | Remove all containers, volumes, images |
| `make ps` | Show running containers |
| `make health` | Check service health |
| `make dev` | Start in development mode |

## Entrypoint Script

Each service's `entrypoint.sh` runs on container start:

```bash
#!/bin/sh
set -e

# Wait for MySQL to be ready
until nc -z -v -w30 $DB_HOST 3306; do
    echo "Waiting for MySQL..."
    sleep 5
done

# Generate app key if not set
php artisan key:generate --force

# Run database migrations
php artisan migrate --force

# Cache configuration
php artisan config:cache

# Service-specific setup (Auth only)
php artisan passport:keys --force
chmod 600 storage/oauth-private.key storage/oauth-public.key
php artisan passport:client --personal --no-interaction

exec "$@"
```

## Volumes

### Named Volumes (Persistent)

| Volume | Container | Path |
|--------|-----------|------|
| mysql_auth_data | mysql-auth | /var/lib/mysql |
| mysql_ip_data | mysql-ip | /var/lib/mysql |

### Development Volumes (Bind Mounts)

In `docker-compose.dev.yml`:
```yaml
volumes:
  - ./services/auth-service:/var/www/html
  - ./services/ip-service:/var/www/html
  - ./services/gateway:/var/www/html
```

## Networking

### Network: ipam-network

All services connect to `ipam-network` (bridge driver):

| Service | Internal Hostname | Internal Port | External Port |
|---------|-------------------|---------------|---------------|
| gateway | gateway | 80 | 8000 |
| auth-service | auth-service | 80 | - |
| ip-service | ip-service | 80 | - |
| frontend | frontend | 80 | 3000 |
| mysql-auth | mysql-auth | 3306 | - |
| mysql-ip | mysql-ip | 3306 | - |

### Service Discovery

Services communicate using container names:
- Frontend → `http://localhost:8000/api/...` (via browser)
- Gateway → `http://auth-service/api/...`
- Gateway → `http://ip-service/api/...`
- Auth Service → `mysql-auth:3306`
- IP Service → `mysql-ip:3306`

## Frontend Container

### Build Strategy
Multi-stage build for optimal production image:

1. **Build Stage** (node:20-alpine)
   - Install npm dependencies
   - Build production bundle with Vite
   
2. **Serve Stage** (nginx:alpine)
   - Copy built assets
   - Serve via nginx with SPA routing

### Configuration
```yaml
frontend:
  build: ./frontend
  container_name: ipam-frontend
  ports:
    - "3000:80"
  environment:
    - VITE_API_URL=http://localhost:8000/api
  networks:
    - ipam-network
```

### Development Mode
For development with hot module replacement:
```yaml
# docker-compose.dev.yml
frontend:
  build:
    context: ./frontend
    dockerfile: Dockerfile.dev
  volumes:
    - ./frontend:/app
    - /app/node_modules
  ports:
    - "3000:5173"
```

## Health Checks

### MySQL Health Check
```yaml
healthcheck:
  test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
  interval: 10s
  timeout: 5s
  retries: 5
```

### Application Health Check
```yaml
healthcheck:
  test: ["CMD", "curl", "-f", "http://localhost/api/health"]
  interval: 30s
  timeout: 10s
  retries: 3
  start_period: 40s
```

## Troubleshooting

### Container Won't Start

```bash
# Check logs
docker compose logs auth-service

# Common issues:
# 1. MySQL not ready - entrypoint waits automatically
# 2. Port conflict - check if ports 8000-8002 are free
# 3. Missing .env - ensure cp .env.example .env was run
```

### Database Connection Failed

```bash
# Check MySQL is running
docker compose ps mysql-auth

# Access MySQL directly
docker compose exec mysql-auth mysql -u auth_user -p

# Check environment variables
docker compose exec auth-service env | grep DB_
```

### Permission Errors

```bash
# Storage directory permissions
docker compose exec auth-service chmod -R 775 storage bootstrap/cache

# OAuth key permissions (auth-service only)
docker compose exec auth-service chmod 600 storage/oauth-*.key
```

### Reset Everything

```bash
# Nuclear option - removes all data
make clean

# Rebuild from scratch
make up-build
make fresh
```

## Production Considerations

### Security
- [ ] Change default MySQL passwords in `.env`
- [ ] Set `APP_DEBUG=false`
- [ ] Set `APP_ENV=production`
- [ ] Use Docker secrets for sensitive data
- [ ] Enable HTTPS via reverse proxy

### Performance
- [ ] Add Redis for caching/sessions
- [ ] Configure PHP-FPM pool settings
- [ ] Add nginx caching headers
- [ ] Use multi-stage builds to reduce image size

### Scaling
- [ ] Use Docker Swarm or Kubernetes for orchestration
- [ ] Add load balancer in front of services
- [ ] Configure MySQL replication
- [ ] Implement horizontal pod autoscaling

---

*Last updated: February 3, 2026*
