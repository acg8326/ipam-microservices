# Auth Service Documentation

## Overview
Authentication and authorization service using Laravel Passport (OAuth2).

**Port:** 8001

## Endpoints

### Register
```
POST /api/register
```

**Request:**
```json
{
    "name": "Juan Dela Cruz",
    "email": "juandelacruz@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "user"
}
```

**Response:** `201 Created`
```json
{
    "user": {
        "id": 1,
        "name": "Juan Dela Cruz",
        "email": "juandelacruz@example.com",
        "role": "user"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "session_id": "c9dcc60dfaecb93dc9c3a55fd78659a10afaea306e2a538fefe597856d5e7c08"
}
```

> **Note:** The `role` field is optional and defaults to `user` if not provided.

> **Session ID:** The `session_id` is the JWT's `jti` claim - cryptographically signed and cannot be forged. Used for audit tracking.

### Login
```
POST /api/login
```

**Request:**
```json
{
    "email": "juandelacruz@example.com",
    "password": "password123"
}
```

**Response:** `200 OK`
```json
{
    "user": {
        "id": 1,
        "name": "Juan Dela Cruz",
        "email": "juandelacruz@example.com",
        "role": "user"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "session_id": "c9dcc60dfaecb93dc9c3a55fd78659a10afaea306e2a538fefe597856d5e7c08"
}
```

### Get Current User
```
GET /api/user
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
    "id": 1,
    "name": "Juan Dela Cruz",
    "email": "juandelacruz@example.com",
    "role": "user"
}
```

### Refresh Token
```
POST /api/refresh
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
    "user": {
        "id": 1,
        "name": "Juan Dela Cruz",
        "email": "juandelacruz@example.com",
        "role": "user"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "session_id": "new_session_id_after_refresh..."
}
```

> **Note:** The `expires_in` value is in seconds. Frontend should refresh the token before expiry for seamless user experience. A new `session_id` is generated on refresh.

### Logout
```
POST /api/logout
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
    "message": "Successfully logged out"
}
```

### List Users (Admin Only)
```
GET /api/users
Authorization: Bearer {admin_token}
```

**Response:** `200 OK`
```json
[
    {
        "id": 1,
        "name": "Juan Dela Cruz",
        "email": "juandelacruz@example.com",
        "role": "user"
    },
    {
        "id": 2,
        "name": "Admin User",
        "email": "admin@example.com",
        "role": "admin"
    }
]
```

## Error Responses

### Validation Errors `422 Unprocessable Entity`

**Email Already Taken:**
```json
{
    "message": "The email has already been taken.",
    "errors": {
        "email": ["The email has already been taken."]
    }
}
```

**Required Fields Missing:**
```json
{
    "message": "The name field is required. (and 2 more errors)",
    "errors": {
        "name": ["The name field is required."],
        "email": ["The email field is required."],
        "password": ["The password field is required."]
    }
}
```

**Invalid Email Format:**
```json
{
    "message": "The email field must be a valid email address.",
    "errors": {
        "email": ["The email field must be a valid email address."]
    }
}
```

**Password Too Short:**
```json
{
    "message": "The password field must be at least 8 characters.",
    "errors": {
        "password": ["The password field must be at least 8 characters."]
    }
}
```

**Password Confirmation Mismatch:**
```json
{
    "message": "The password field confirmation does not match.",
    "errors": {
        "password": ["The password field confirmation does not match."]
    }
}
```

**Invalid Role:**
```json
{
    "message": "The selected role is invalid.",
    "errors": {
        "role": ["The selected role is invalid."]
    }
}
```

**Invalid Credentials (Login):**
```json
{
    "message": "The provided credentials are incorrect.",
    "errors": {
        "email": ["The provided credentials are incorrect."]
    }
}
```

### Authentication Errors

**Unauthenticated (Missing or Invalid Token):** `401 Unauthorized`
```json
{
    "message": "Unauthenticated."
}
```

**Token Expired:** `401 Unauthorized`
```json
{
    "message": "Unauthenticated."
}
```

### Authorization Errors

**Insufficient Permissions (Scope):** `403 Forbidden`
```json
{
    "message": "Invalid scope(s) provided."
}
```

## Roles & Scopes

| Role | Scope | Permissions |
|------|-------|-------------|
| admin | admin | Full access to all resources |
| user | user | Limited access, cannot list all users |

## Request Headers

All requests should include:
```
Accept: application/json
Content-Type: application/json
```

Protected routes (marked with `Authorization: Bearer {token}`) additionally require:
```
Authorization: Bearer {your_access_token}
```

You get the access token from the `/api/login` or `/api/register` response.

## Audit Logging

All authentication events are logged:
- `register` - New user registration
- `login` - Successful login
- `login_failed` - Failed login attempt
- `logout` - User logout
- `token_refresh` - Token refresh

### List Auth Audit Logs (Admin Only)
```
GET /api/audit-logs
Authorization: Bearer {admin_token}
```

**Query Parameters:**
- `session_id` (optional): Filter by session ID
- `user_id` (optional): Filter by user ID

**Response:** `200 OK`
```json
{
    "current_page": 1,
    "data": [
        {
            "id": 1,
            "action": "login",
            "entity_type": "user",
            "entity_id": 1,
            "old_values": null,
            "new_values": {"email": "admin@example.com"},
            "user_id": 1,
            "user_email": "admin@example.com",
            "session_id": "c9dcc60dfaecb93d...",
            "ip_address": "127.0.0.1",
            "hash": "a1b2c3d4...",
            "previous_hash": null,
            "created_at": "2026-02-02T07:16:45.000000Z"
        }
    ],
    "per_page": 20,
    "total": 1
}
```

### Verify Auth Audit Log Integrity (Admin Only)
```
GET /api/audit-logs/verify
Authorization: Bearer {admin_token}
```

**Response (Valid):** `200 OK`
```json
{
    "valid": true,
    "errors": []
}
```

## Security

### Session Tracking
The `session_id` returned in token responses is the JWT's `jti` (JWT ID) claim:
- Cryptographically signed with the server's RSA private key
- Cannot be forged or spoofed
- Each token = unique session
- Tracked across all services via gateway

### Tamper-proof Audit Logs
Audit logs use SHA256 hash chain:
- Each log entry includes hash of previous entry
- Any modification breaks the chain
- Verify endpoint detects tampering

---

## Docker

### Container Info
| Property | Value |
|----------|-------|
| Image | `php:8.2-fpm-alpine` |
| Internal Port | 80 |
| External Port | 8001 |
| Database | mysql-auth:3306 |
| Container Name | auth-service |

### Quick Commands
```bash
# View logs
make logs-auth

# Shell into container
make shell-auth

# Run migrations
docker compose exec auth-service php artisan migrate

# Regenerate Passport keys
docker compose exec auth-service php artisan passport:keys --force
```

### Environment Variables
Set in `docker-compose.yml`:
```yaml
environment:
  APP_KEY: base64:...
  APP_URL: http://auth-service
  DB_CONNECTION: mysql
  DB_HOST: mysql-auth
  DB_DATABASE: auth_db
  DB_USERNAME: auth_user
  DB_PASSWORD: auth_password
```

See [Docker Documentation](docker.md) for full setup instructions.