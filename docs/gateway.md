# Gateway Service Documentation

## Overview
API Gateway service that routes requests to backend services with built-in resilience patterns.

**Port:** 8000

## Features

- Request proxying to auth and ip services
- Circuit breaker with automatic recovery
- Health check monitoring
- Request logging with tracing
- Rate limiting (60 requests/minute)
- Timeout handling (10 seconds)

## Endpoints

### Health Check
```
GET /api/health
```

**Response:** `200 OK`
```json
{
    "gateway": "healthy",
    "services": {
        "auth": {
            "status": "healthy",
            "latency_ms": 45,
            "url": "http://localhost:8001"
        },
        "ip": {
            "status": "healthy",
            "latency_ms": 38,
            "url": "http://localhost:8002"
        }
    },
    "timestamp": "2026-02-02T10:30:00+00:00"
}
```

**Service Status Values:**
- `healthy` - Service responding normally
- `degraded` - Service responding with errors
- `unreachable` - Cannot connect to service
- `circuit_open` - Circuit breaker is open

### Auth Routes

| Method | Gateway Endpoint | Backend Endpoint |
|--------|------------------|------------------|
| POST | /api/auth/register | Auth → /api/register |
| POST | /api/auth/login | Auth → /api/login |
| POST | /api/auth/logout | Auth → /api/logout |
| GET | /api/auth/user | Auth → /api/user |
| POST | /api/auth/refresh | Auth → /api/refresh |
| GET | /api/auth/users | Auth → /api/users |
| GET | /api/auth/audit-logs | Auth → /api/audit-logs |
| GET | /api/auth/audit-logs/verify | Auth → /api/audit-logs/verify |

### IP Address Routes

| Method | Gateway Endpoint | Backend Endpoint |
|--------|------------------|------------------|
| GET | /api/ip-addresses | IP → /api/ip-addresses |
| POST | /api/ip-addresses | IP → /api/ip-addresses |
| GET | /api/ip-addresses/{id} | IP → /api/ip-addresses/{id} |
| PUT | /api/ip-addresses/{id} | IP → /api/ip-addresses/{id} |
| DELETE | /api/ip-addresses/{id} | IP → /api/ip-addresses/{id} |

### Audit Log Routes

| Method | Gateway Endpoint | Backend Endpoint |
|--------|------------------|------------------|
| GET | /api/audit-logs | IP → /api/audit-logs |
| GET | /api/audit-logs/verify | IP → /api/audit-logs/verify |

## Circuit Breaker

The gateway implements a circuit breaker pattern to handle service failures gracefully.

**Configuration:**
- Failure threshold: 5 consecutive failures
- Recovery timeout: 30 seconds

**Behavior:**
1. After 5 consecutive failures to a service, the circuit opens
2. While open, requests return 503 immediately without attempting connection
3. After 30 seconds, the circuit closes and requests resume

**Response when circuit is open:**
```json
{
    "message": "Service temporarily unavailable",
    "retry_after": 25
}
```

## Rate Limiting

All routes (except health check) are rate limited to 60 requests per minute per client.

**Response when rate limited:** `429 Too Many Requests`
```json
{
    "message": "Too Many Attempts."
}
```

## Request Logging

Every request is logged with the following information:

```json
{
    "request_id": "gw_67a3f1e2b4c5d",
    "service": "auth",
    "method": "POST",
    "path": "/api/login",
    "status": 200,
    "duration_ms": 125,
    "client_ip": "192.168.1.100",
    "circuit_open": false
}
```

Log levels:
- `INFO` - Successful requests (2xx, 3xx)
- `WARNING` - Client errors (4xx)
- `ERROR` - Server errors (5xx) and connection failures

## Error Responses

### Service Unavailable (503)
```json
{
    "message": "Service temporarily unavailable"
}
```
Returned when the backend service is unreachable or circuit breaker is open.

### Bad Gateway (502)
```json
{
    "message": "Gateway error"
}
```
Returned for unexpected errors during request forwarding.

## Configuration

Environment variables:

| Variable | Description | Default |
|----------|-------------|---------|
| AUTH_SERVICE_URL | Auth service base URL | http://localhost:8001 |
| IP_SERVICE_URL | IP service base URL | http://localhost:8002 |

In `config/services.php`:
```php
'auth' => [
    'url' => env('AUTH_SERVICE_URL', 'http://localhost:8001'),
],
'ip' => [
    'url' => env('IP_SERVICE_URL', 'http://localhost:8002'),
],
```

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Gateway Service                       │
├─────────────────────────────────────────────────────────┤
│  Routes (api.php)                                       │
│    ├── Rate Limiting Middleware                         │
│    └── GatewayController                                │
│           └── GatewayService                            │
│                 ├── Circuit Breaker                     │
│                 ├── Session ID Extraction (from JWT)    │
│                 ├── Request Logging                     │
│                 ├── Timeout Handling                    │
│                 └── HTTP Client                         │
├─────────────────────────────────────────────────────────┤
│                         │                               │
│              ┌──────────┴──────────┐                    │
│              ▼                     ▼                    │
│      Auth Service            IP Service                 │
│      (port 8001)             (port 8002)                │
└─────────────────────────────────────────────────────────┘
```

## Session Tracking

The gateway extracts the session ID from the JWT's `jti` claim and forwards it to backend services via `X-Session-Id` header.

**Security:**
- Session ID is cryptographically tied to the JWT (RSA signed)
- Cannot be spoofed - extracted from token, not from client headers
- Each token = unique session for audit tracking

```
Client Request                 Gateway                    Backend Service
     │                           │                              │
     │──── Bearer Token ────────▶│                              │
     │                           │── Extract jti from JWT ──    │
     │                           │── Add X-Session-Id header ──▶│
     │                           │                              │── Log with session_id
     │◀──────────────────────────│◀─────────────────────────────│
```
