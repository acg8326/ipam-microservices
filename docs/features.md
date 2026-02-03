# IPAM Microservices - Features

## Architecture

- **Microservices Pattern**: Three independent services (Auth, IP, Gateway)
- **Service Communication**: REST API via HTTP
- **Independent Databases**: Each service has its own database
- **Token-based Authentication**: JWT via Laravel Passport

---

## Auth Service

### Authentication
- User registration with email/password
- User login with JWT token response
- Token refresh for session continuity
- Logout with token revocation
- `expires_in` included in token responses for frontend auto-renewal
- `session_id` (JWT `jti`) returned for audit tracking

### Authorization
- Role-based access control (RBAC)
- Two roles: `admin` and `user`
- Passport scopes for role-based route protection
- Admin-only endpoints protected by `scope:admin` middleware

### Audit Logging
- Logs: `register`, `login`, `login_failed`, `logout`, `token_refresh`
- Session ID tracking for within-session audit trails
- User ID tracking for lifetime audit trails
- Tamper-proof SHA256 hash chain
- Verify endpoint to detect tampering

### Security
- Password hashing via Laravel's `hashed` cast
- Bearer token authentication (RSA-signed JWT)
- Token revocation on logout
- Previous tokens revoked on new login
- Session ID cryptographically tied to JWT (cannot be spoofed)

### Code Structure
- Service layer pattern (`AuthService`)
- Form Request validation (`RegisterRequest`, `LoginRequest`)
- Thin controllers with dependency injection
- Custom JSON error responses for 401/403

---

## IP Service

### IP Address Management
- Create IP address with label and optional comment
- Support for both IPv4 and IPv6 addresses
- View all IP addresses (paginated)
- Search by IP address or label
- Update IP address records
- Delete IP address records (admin only)

### Role-based Permissions
| Action | Regular User | Admin |
|--------|--------------|-------|
| View all IPs | Yes | Yes |
| Create IP | Yes | Yes |
| Update own IP (label only) | Yes | Yes |
| Update any IP (all fields) | No | Yes |
| Delete any IP | No | Yes |
| View audit logs | No | Yes |

### Audit Logging
- Automatic logging of all CRUD operations
- Captures: action, old values, new values, user info, timestamp
- Session ID tracking (from JWT `jti` via gateway)
- Client IP address tracking
- Tamper-proof hash chain verification
- Each log entry includes hash of previous entry
- Verify endpoint to check audit log integrity
- Filter by `session_id` for within-session history
- Filter by `user_id` for lifetime history

### Code Structure
- Service layer pattern (`IpAddressService`)
- Form Request validation (`StoreIpAddressRequest`, `UpdateIpAddressRequest`)
- Thin controllers with dependency injection
- Model scopes for search functionality
- Token validation middleware for auth-service integration

---

## API Features

### Request/Response
- JSON API format
- Consistent error response structure
- Pagination on list endpoints
- Validation error messages with field-level details

### Endpoints Summary

**Auth Service (Port 8001)**
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | /api/register | No | Register new user |
| POST | /api/login | No | Login and get token |
| GET | /api/user | Yes | Get current user |
| POST | /api/logout | Yes | Logout and revoke token |
| POST | /api/refresh | Yes | Refresh access token |
| GET | /api/users | Admin | List all users |
| GET | /api/audit-logs | Admin | List auth audit logs |
| GET | /api/audit-logs/verify | Admin | Verify auth audit integrity |

**IP Service (Port 8002)**
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | /api/ip-addresses | Yes | List all IP addresses |
| POST | /api/ip-addresses | Yes | Create IP address |
| GET | /api/ip-addresses/{id} | Yes | Get specific IP |
| PUT | /api/ip-addresses/{id} | Yes | Update IP address |
| DELETE | /api/ip-addresses/{id} | Admin | Delete IP address |
| GET | /api/audit-logs | Admin | List audit logs |
| GET | /api/audit-logs/verify | Admin | Verify audit integrity |

**Gateway Service (Port 8000)**
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | /api/health | No | Health check + service status |
| POST | /api/auth/register | No | Proxy to auth register |
| POST | /api/auth/login | No | Proxy to auth login |
| GET | /api/auth/user | Yes | Proxy to auth user |
| POST | /api/auth/logout | Yes | Proxy to auth logout |
| POST | /api/auth/refresh | Yes | Proxy to auth refresh |
| GET | /api/auth/users | Admin | Proxy to auth users |
| GET | /api/auth/audit-logs | Admin | Proxy to auth audit logs |
| GET | /api/auth/audit-logs/verify | Admin | Proxy to auth audit verify |
| * | /api/ip-addresses/* | Yes | Proxy to IP service |
| * | /api/audit-logs/* | Admin | Proxy to IP service |

---

## Gateway Service

### Request Proxying
- Routes all requests to appropriate backend services
- Forwards Bearer tokens to backend services
- Extracts session ID from JWT `jti` claim
- Forwards session ID via `X-Session-Id` header
- Preserves query parameters and request body

### Circuit Breaker
- Failure threshold: 5 consecutive failures
- Recovery timeout: 30 seconds
- Automatic circuit recovery
- Returns `retry_after` when circuit is open

### Health Monitoring
- `/api/health` endpoint for service status
- Checks auth and IP service availability
- Reports latency per service
- Status values: healthy, degraded, unreachable, circuit_open

### Rate Limiting
- Tiered rate limits based on endpoint sensitivity:
  - Auth session endpoints: 300/min (high frequency)
  - Read operations: 120/min (moderate)
  - Write operations: 30/min (strict - prevents brute force)
- Returns 429 when limit exceeded
- File-based cache storage (no database lock issues)

### Request Logging
- Unique request ID for tracing
- Logs: service, method, path, status, duration
- Log levels: INFO (2xx), WARNING (4xx), ERROR (5xx)

### Code Structure
- Service layer pattern (`GatewayService`)
- Thin controller with dependency injection
- Centralized error handling
- Timeout configuration (10 seconds)

---

## Security Model

### Authentication Flow
```
1. User → POST /api/auth/login → Gateway → Auth Service
2. Auth Service validates credentials
3. Auth Service generates RSA-signed JWT with:
   - User ID (sub claim)
   - Role scope (admin/user)
   - Unique token ID (jti claim) ← Session ID
4. Auth Service logs "login" event with session_id
5. Response includes: access_token, expires_in, session_id
6. Gateway extracts jti from JWT on subsequent requests
7. Gateway forwards jti as X-Session-Id header
8. Backend services log all actions with session_id
```

### Audit Log Hash Chain
```
Entry #1: hash = SHA256(data + timestamp + null)
Entry #2: hash = SHA256(data + timestamp + hash#1)
Entry #3: hash = SHA256(data + timestamp + hash#2)

Verification: Recalculate all hashes, compare with stored
If mismatch → Tampering detected
```

### Session Security
- Session ID is JWT's `jti` claim (cryptographically signed)
- Cannot be spoofed - extracted from token at gateway
- Each login/refresh generates new session
- All actions within session share same session_id

---

## Docker Containerization

### Container Stack
- **Base Image**: `php:8.2-fpm-alpine` (~50MB)
- **Web Server**: nginx
- **Process Manager**: supervisord
- **Database**: MySQL 8.0
- **Frontend**: Node.js 20 (build) + nginx (serve)

### Services
| Service | Internal Port | External Port | Database |
|---------|---------------|---------------|----------|
| Frontend | 80 | 3000 | None |
| Gateway | 80 | 8000 | None |
| Auth Service | 80 | internal | mysql-auth |
| IP Service | 80 | internal | mysql-ip |

### Docker Compose Files
- `docker-compose.yml` - Production configuration
- `docker-compose.dev.yml` - Development with volume mounts

### Quick Commands
```bash
make up-build    # Build and start all services
make down        # Stop all services
make logs        # View all logs
make fresh       # Reset databases and migrate
make health      # Check service health
make dev         # Development mode with live reload
```

See [Docker Documentation](docker.md) for full details.

---

## Frontend Application

### Tech Stack
- **Framework**: Vue.js 3.5 with Composition API
- **Language**: TypeScript
- **State Management**: Pinia
- **Routing**: Vue Router 4
- **Build Tool**: Vite
- **Testing**: Vitest + Vue Test Utils

### Pages
| Page | Route | Access |
|------|-------|--------|
| Login | `/login` | Public |
| Dashboard | `/` | Authenticated |
| IP Addresses | `/ip-addresses` | Authenticated |
| Audit Logs | `/audit-logs` | Admin only |
| Settings | `/settings` | Admin only |

### Features
- Responsive design
- Role-based navigation
- Real-time form validation
- Search and pagination
- Modal dialogs for CRUD operations

See [Frontend Documentation](frontend.md) for full details.

---

## Testing

### Backend Tests (PHPUnit)
| Service | Tests | Coverage |
|---------|-------|----------|
| Auth Service | 14 | Authentication, User Management |
| IP Service | 23 | IP CRUD, Audit Logs, Permissions |
| Gateway | 2 | Health Check |

### Frontend Tests (Vitest)
| Category | Tests | Coverage |
|----------|-------|----------|
| Auth Store | 4 | State management |
| IP Store | 4 | State management |
| Validation | 8 | IP address validation |

### Running Tests
```bash
# Frontend
cd frontend && npm run test:run

# Backend (inside containers)
docker exec ipam-auth-service php artisan test
docker exec ipam-ip-service php artisan test
```

---

## Technical Stack

- **Framework**: Laravel 12 (Backend), Vue.js 3.5 (Frontend)
- **PHP Version**: 8.2
- **Node Version**: 20
- **Authentication**: Laravel Passport (OAuth2)
- **Database**: MySQL 8.0
- **Containerization**: Docker + Docker Compose
- **API Format**: RESTful JSON

---

## Completed Features

- [x] Microservices architecture (Gateway, Auth, IP)
- [x] Docker containerization (all services)
- [x] Frontend Vue.js SPA
- [x] JWT authentication with auto-refresh
- [x] Role-based access control (admin/user)
- [x] IP address CRUD operations
- [x] Tamper-proof audit logging
- [x] Dashboard with statistics
- [x] User management (admin)
- [x] PHPUnit tests (backend)
- [x] Vitest tests (frontend)

## Future Enhancements

- [ ] Per-user rate limiting
- [ ] Redis caching
- [ ] Email notifications
- [ ] IP address reservations
- [ ] Bulk import/export

---

*Last updated: February 3, 2026*
