# Security Documentation

## Overview

This document outlines the security measures implemented across the IPAM Microservices application, covering authentication, authorization, data protection, and infrastructure security.

---

## Authentication

### JWT Token Authentication

The system uses **Laravel Passport** with RSA-signed JSON Web Tokens (JWT).

| Feature | Implementation |
|---------|----------------|
| Token Type | RSA-signed JWT (RS256) |
| Token Lifetime | 1 hour (configurable) |
| Token Storage | Client-side `localStorage` |
| Token Refresh | Automatic renewal before expiration |

**Token Structure:**
```
Header: { "alg": "RS256", "typ": "JWT" }
Payload: {
  "aud": "client-id",
  "jti": "unique-token-id",  // Session ID for audit
  "iat": 1234567890,
  "exp": 1234571490,
  "sub": "user-id",
  "scopes": ["admin" | "user"]
}
Signature: RSA-SHA256(header.payload, private_key)
```

### Token Lifecycle

```
┌─────────────────────────────────────────────────────────────────┐
│  LOGIN                                                          │
│  1. User submits credentials                                    │
│  2. Server validates against bcrypt hash                        │
│  3. Previous tokens revoked                                     │
│  4. New JWT issued with unique jti (session ID)                 │
│  5. Login event logged with session ID                          │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  ACTIVE SESSION                                                 │
│  - Token sent in Authorization header                           │
│  - Gateway extracts jti for audit tracking                      │
│  - Background refresh before expiration (< 5 min remaining)     │
│  - All actions logged with session ID                           │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  LOGOUT                                                         │
│  1. Token revoked in database                                   │
│  2. Client clears localStorage                                  │
│  3. Logout event logged                                         │
└─────────────────────────────────────────────────────────────────┘
```

### Password Security

| Measure | Implementation |
|---------|----------------|
| Hashing Algorithm | bcrypt (Laravel default) |
| Hash Rounds | 12 (configurable) |
| Storage | `password` field with `hashed` cast |
| Validation | `Hash::check()` for verification |

---

## Authorization

### Role-Based Access Control (RBAC)

Two roles are supported:

| Role | Capabilities |
|------|-------------|
| `admin` | Full access: create, read, update, delete all resources |
| `user` | Limited access: create own IPs, read all, update own labels only |

### Passport Scopes

```php
// Auth Service - Route Protection
Route::middleware('auth:api')->group(function () {
    // All authenticated users
    Route::get('/user', ...);
    
    Route::middleware('scope:admin')->group(function () {
        // Admin only
        Route::get('/users', ...);
        Route::get('/audit-logs', ...);
    });
});
```

### IP Address Permissions

| Action | User | Admin |
|--------|------|-------|
| View all IPs | ✅ | ✅ |
| Create IP | ✅ | ✅ |
| Update own IP (label) | ✅ | ✅ |
| Update any IP (all fields) | ❌ | ✅ |
| Delete any IP | ❌ | ✅ |

---

## Rate Limiting

### Tiered Rate Limits

Different endpoints have different rate limits based on security sensitivity:

| Tier | Limit | Endpoints | Rationale |
|------|-------|-----------|-----------|
| **High** | 300/min | `/auth/user`, `/auth/refresh` | Called on every page load |
| **Medium** | 120/min | GET endpoints (list, view) | Normal browsing |
| **Strict** | 30/min | POST/PUT/DELETE (login, write) | Prevents brute force |

### Protection Against

- **Brute Force Attacks**: 30 login attempts/min limits password guessing
- **Credential Stuffing**: Rate limit slows automated attacks
- **DDoS**: Prevents resource exhaustion from single source
- **API Abuse**: Limits data scraping and spam

### Response When Limited

```json
HTTP/1.1 429 Too Many Requests
{
    "message": "Too Many Attempts."
}
```

---

## CORS (Cross-Origin Resource Sharing)

### Configuration

```php
// services/gateway/config/cors.php
'allowed_origins' => env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000'),
'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With'],
```

### Security Measures

| Setting | Value | Purpose |
|---------|-------|---------|
| `allowed_origins` | Configurable (default: localhost:3000) | Prevents unauthorized domains |
| `allowed_methods` | Specific verbs only | Limits attack surface |
| `allowed_headers` | Required headers only | Prevents header injection |
| `supports_credentials` | false | No cookie-based auth |

### Production Configuration

```bash
# .env or docker-compose.yml
CORS_ALLOWED_ORIGINS=https://your-domain.com,https://admin.your-domain.com
```

---

## Input Validation

### Form Request Validation

All inputs are validated using Laravel Form Requests:

```php
// LoginRequest
'email' => 'required|string|email',
'password' => 'required|string',

// RegisterRequest
'name' => 'required|string|max:255',
'email' => 'required|string|email|unique:users',
'password' => 'required|string|min:8|confirmed',
'role' => 'nullable|in:admin,user',

// StoreIpAddressRequest
'ip_address' => 'required|ip|unique:ip_addresses',
'label' => 'required|string|max:255',
'comment' => 'nullable|string',
```

### SQL Injection Prevention

- **Eloquent ORM**: All database queries use parameterized statements
- **No raw SQL**: No `DB::raw()` or `whereRaw()` in codebase
- **Mass assignment protection**: Models define `$fillable` arrays

---

## Audit Logging

### Tamper-Proof Design

Audit logs use a **SHA256 hash chain** to detect tampering:

```
Entry #1: hash = SHA256(data + timestamp + null)
Entry #2: hash = SHA256(data + timestamp + hash_of_entry_1)
Entry #3: hash = SHA256(data + timestamp + hash_of_entry_2)
```

### Verification

```bash
GET /api/audit-logs/verify
```

Response:
```json
{
    "valid": true,
    "total_entries": 150,
    "verified_at": "2026-02-03T12:00:00Z"
}
```

If tampered:
```json
{
    "valid": false,
    "error": "Hash mismatch at entry #42",
    "expected_hash": "abc123...",
    "actual_hash": "def456..."
}
```

### What's Logged

| Service | Events |
|---------|--------|
| Auth | `register`, `login`, `login_failed`, `logout`, `token_refresh` |
| IP | `create`, `update`, `delete` (with old/new values) |

### Tracking Dimensions

| Dimension | Field | Use Case |
|-----------|-------|----------|
| Session | `session_id` (JWT jti) | Track actions within a login session |
| User Lifetime | `user_id` | Track all actions by a user |
| IP Lifetime | `ip_address_id` | Track all changes to an IP record |

### Non-Deletable

- No DELETE endpoint exists for audit logs
- Not exposed in admin UI
- Hash chain makes silent deletion detectable

---

## Infrastructure Security

### Docker Network Isolation

```
┌─────────────────────────────────────────────────────────────────┐
│                     Docker Network (ipam-network)                │
│                                                                  │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐        │
│  │ Frontend │  │ Gateway  │  │   Auth   │  │    IP    │        │
│  │  :3000   │  │  :8000   │  │ (internal)│  │(internal)│        │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘        │
│       │             │             │             │               │
│       │      ┌──────┴─────────────┴─────────────┴──────┐       │
│       │      │           Internal Network Only          │       │
│       │      └──────┬─────────────┬─────────────┬──────┘       │
│       │             │             │             │               │
│       │      ┌──────┴─────┐ ┌─────┴──────┐                     │
│       │      │ MySQL Auth │ │  MySQL IP  │                     │
│       │      │ (internal) │ │ (internal) │                     │
│       │      └────────────┘ └────────────┘                     │
└───────┼─────────────┼───────────────────────────────────────────┘
        │             │
   External Access Only:
   - Frontend: localhost:3000
   - Gateway API: localhost:8000
```

### Port Exposure

| Service | Internal Port | External Port | Exposed? |
|---------|---------------|---------------|----------|
| Frontend | 80 | 3000 | ✅ Yes |
| Gateway | 80 | 8000 | ✅ Yes |
| Auth Service | 80 | - | ❌ No |
| IP Service | 80 | - | ❌ No |
| MySQL (Auth) | 3306 | - | ❌ No |
| MySQL (IP) | 3306 | - | ❌ No |

### Database Isolation

Each service has its own database:
- `auth_service` database for Auth Service
- `ip_service` database for IP Service

Services cannot access each other's databases directly.

---

## Security Headers

The following headers are set by Laravel/nginx:

| Header | Value | Purpose |
|--------|-------|---------|
| `X-Frame-Options` | SAMEORIGIN | Prevents clickjacking |
| `X-Content-Type-Options` | nosniff | Prevents MIME sniffing |
| `X-XSS-Protection` | 1; mode=block | XSS filter (legacy browsers) |

---

## Production Recommendations

### Not Yet Implemented (Future Enhancements)

| Feature | Priority | Description |
|---------|----------|-------------|
| HTTPS/TLS | **High** | Add SSL certificates in production |
| Account Lockout | Medium | Lock after N failed login attempts |
| Exponential Backoff | Medium | Increasing delays on failed logins |
| API Key for Services | Medium | Service-to-service authentication |
| CSP Headers | Medium | Content Security Policy |
| Audit Log Encryption | Low | Encrypt sensitive data at rest |
| 2FA/MFA | Low | Two-factor authentication |

### Environment Variables for Production

```bash
# .env
APP_ENV=production
APP_DEBUG=false

# CORS - restrict to your domain
CORS_ALLOWED_ORIGINS=https://ipam.yourcompany.com

# Strong database passwords
AUTH_DB_PASSWORD=<strong-random-password>
IP_DB_PASSWORD=<strong-random-password>
```

---

## Security Checklist

### ✅ Implemented

- [x] RSA-signed JWT authentication
- [x] Bcrypt password hashing
- [x] Token revocation on logout
- [x] Role-based access control
- [x] Tiered rate limiting
- [x] CORS origin restriction
- [x] Input validation (Form Requests)
- [x] SQL injection prevention (Eloquent ORM)
- [x] Tamper-proof audit logs (hash chain)
- [x] Database port isolation
- [x] Service network isolation
- [x] Automatic token refresh

### ⏳ Recommended for Production

- [ ] HTTPS/TLS encryption
- [ ] Account lockout policy
- [ ] Content Security Policy headers
- [ ] Service-to-service API keys
- [ ] Log monitoring and alerting
- [ ] Regular security audits

---

*Last updated: February 3, 2026*
