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
- 60 requests per minute per client
- Applied to all routes except health check
- Returns 429 when limit exceeded

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

## Technical Stack

- **Framework**: Laravel 12
- **PHP Version**: 8.2
- **Authentication**: Laravel Passport (OAuth2)
- **Database**: MySQL (configurable)
- **API Format**: RESTful JSON

---

## Pending Features

- [ ] Docker containerization (`docker-compose.yml`)
- [ ] Frontend (Vue.js 3.5 SPA)
- [ ] Unit tests (PHPUnit)
- [ ] Integration tests
- [ ] `.env.example` files for all services
- [ ] Per-user rate limiting

---

*Last updated: February 2, 2026*
