# IPAM Microservices

A microservices-based IP Address Management System built with Laravel and Vue.js.

## Architecture

```mermaid
flowchart TB
    subgraph Frontend
        VUE[Vue.js Frontend<br/>Port 3000]
    end
    
    subgraph Gateway
        GW[Gateway Service<br/>Port 8000]
    end
    
    subgraph Services
        AUTH[Auth Service]
        IP[IP Service]
    end
    
    subgraph Databases
        DB1[(MySQL Auth)]
        DB2[(MySQL IP)]
    end
    
    VUE --> GW
    GW --> AUTH
    GW --> IP
    AUTH --> DB1
    IP --> DB2
```

| Service | Port | Description |
|---------|------|-------------|
| Frontend | 3000 | Vue.js 3.5 SPA with TypeScript |
| Gateway | 8000 | API routing, circuit breaker, rate limiting |
| Auth Service | internal | JWT authentication, RBAC, user management |
| IP Service | internal | IP address CRUD, tamper-proof audit logs |

## Tech Stack

- **Backend:** Laravel 12 / PHP 8.2
- **Frontend:** Vue.js 3.5 + TypeScript + Pinia + Vue Router
- **Database:** MySQL 8.0
- **Auth:** Laravel Passport (OAuth2/JWT)
- **Containerization:** Docker + Docker Compose
- **Testing:** PHPUnit (backend) + Vitest (frontend)

## Quick Start

```bash
# Clone and start
git clone https://github.com/acg8326/ipam-microservices.git
cd ipam-microservices
cp .env.example .env
make up-build
make fresh
```

Access the application:
- **Frontend:** http://localhost:3000
- **API Gateway:** http://localhost:8000

Verify services are running:
```bash
curl http://localhost:8000/api/health
```

See [Docker Documentation](docs/docker.md) for detailed setup, commands, and troubleshooting.

## Features

- **Authentication:** JWT-based login with automatic token refresh
- **Role-Based Access:** Admin and User roles with different permissions
- **IP Management:** Create, read, update, delete IP addresses (IPv4/IPv6)
- **Audit Logging:** Tamper-proof logs with SHA256 hash chain
- **Dashboard:** Overview statistics and recent activity
- **User Management:** Admin can create/manage users

## API Usage

All requests go through the Gateway at `http://localhost:8000`.

### Authentication

```bash
# Register (admin only via Settings page)
# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password123"}'
```

### Protected Requests

```bash
# Use the token from login response
curl http://localhost:8000/api/ip-addresses \
  -H "Authorization: Bearer {your_access_token}"
```

## Testing

```bash
# Run all tests
make test

# Frontend only
make test-fe

# Backend only  
make test-be
```

See [Testing Guide](docs/testing.md) for detailed documentation.

## Documentation

| Document | Description |
|----------|-------------|
| [Features Overview](docs/features.md) | Complete feature list, security model, endpoints |
| [Frontend Guide](docs/frontend.md) | Vue.js SPA architecture, components, state management |
| [Testing Guide](docs/testing.md) | PHPUnit and Vitest setup, running tests, writing tests |
| [Docker Setup](docs/docker.md) | Container configuration, commands, troubleshooting |
| [Gateway Service](docs/gateway.md) | Routing, circuit breaker, rate limiting |
| [Auth Service API](docs/auth-service.md) | Authentication endpoints, error responses |
| [IP Service API](docs/ip-service.md) | IP management endpoints, audit logs |

## License

MIT

---

*Last updated: February 3, 2026*