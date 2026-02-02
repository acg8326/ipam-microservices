# IP Service Documentation

## Overview
IP Address management service with CRUD operations and tamper-proof audit logging.

**Port:** 8002

## Endpoints

### List IP Addresses
```
GET /api/ip-addresses
Authorization: Bearer {token}
```

**Query Parameters:**
- `search` (optional): Filter by IP address or label

**Response:** `200 OK`
```json
{
    "current_page": 1,
    "data": [
        {
            "id": 1,
            "ip_address": "192.168.1.1",
            "label": "Main Server",
            "comment": "Production server",
            "created_by": 1,
            "updated_by": null,
            "created_at": "2026-02-02T07:16:45.000000Z",
            "updated_at": "2026-02-02T07:16:45.000000Z"
        }
    ],
    "per_page": 15,
    "total": 1
}
```

### Create IP Address
```
POST /api/ip-addresses
Authorization: Bearer {token}
```

**Request:**
```json
{
    "ip_address": "192.168.1.100",
    "label": "Web Server",
    "comment": "Optional comment"
}
```

**Response:** `201 Created`
```json
{
    "id": 2,
    "ip_address": "192.168.1.100",
    "label": "Web Server",
    "comment": "Optional comment",
    "created_by": 1,
    "updated_by": null,
    "created_at": "2026-02-02T09:00:00.000000Z",
    "updated_at": "2026-02-02T09:00:00.000000Z"
}
```

### Get IP Address
```
GET /api/ip-addresses/{id}
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
    "id": 1,
    "ip_address": "192.168.1.1",
    "label": "Main Server",
    "comment": "Production server",
    "created_by": 1,
    "updated_by": null,
    "created_at": "2026-02-02T07:16:45.000000Z",
    "updated_at": "2026-02-02T07:16:45.000000Z"
}
```

### Update IP Address
```
PUT /api/ip-addresses/{id}
Authorization: Bearer {token}
```

**Request (Regular User):**
```json
{
    "label": "Updated Label"
}
```

**Request (Admin):**
```json
{
    "ip_address": "192.168.1.200",
    "label": "Updated Label",
    "comment": "Updated comment"
}
```

**Response:** `200 OK`
```json
{
    "id": 1,
    "ip_address": "192.168.1.200",
    "label": "Updated Label",
    "comment": "Updated comment",
    "created_by": 1,
    "updated_by": 2,
    "created_at": "2026-02-02T07:16:45.000000Z",
    "updated_at": "2026-02-02T10:00:00.000000Z"
}
```

### Delete IP Address (Admin Only)
```
DELETE /api/ip-addresses/{id}
Authorization: Bearer {admin_token}
```

**Response:** `200 OK`
```json
{
    "message": "IP address deleted"
}
```

### List Audit Logs (Admin Only)
```
GET /api/audit-logs
Authorization: Bearer {admin_token}
```

**Query Parameters:**
- `entity_type` (optional): Filter by entity type
- `entity_id` (optional): Filter by entity ID
- `user_id` (optional): Filter by user ID (lifetime tracking)
- `session_id` (optional): Filter by session ID (within-session tracking)

**Response:** `200 OK`
```json
{
    "current_page": 1,
    "data": [
        {
            "id": 1,
            "action": "created",
            "entity_type": "ip_address",
            "entity_id": 1,
            "old_values": null,
            "new_values": {
                "id": 1,
                "ip_address": "192.168.1.1",
                "label": "Main Server"
            },
            "user_id": 1,
            "user_email": "admin@example.com",
            "session_id": "c9dcc60dfaecb93dc9c3a55fd78659a10afaea306e2a538fefe597...",
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

> **Session Tracking:** The `session_id` links all operations performed within the same login session. Filter by `session_id` to see all actions in a session, or by `user_id` for lifetime activity.

### Verify Audit Log Integrity (Admin Only)
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

**Response (Tampered):** `400 Bad Request`
```json
{
    "valid": false,
    "errors": [
        "Log #5: Hash mismatch - possible tampering"
    ]
}
```

## Error Responses

### Validation Errors `422 Unprocessable Entity`

**Invalid IP Address:**
```json
{
    "message": "The ip address field must be a valid IP address.",
    "errors": {
        "ip_address": ["The ip address field must be a valid IP address."]
    }
}
```

**Duplicate IP Address:**
```json
{
    "message": "The ip address has already been taken.",
    "errors": {
        "ip_address": ["The ip address has already been taken."]
    }
}
```

**Missing Label:**
```json
{
    "message": "The label field is required.",
    "errors": {
        "label": ["The label field is required."]
    }
}
```

### Authentication Errors

**Missing Token:** `401 Unauthorized`
```json
{
    "message": "Token required"
}
```

**Invalid Token:** `401 Unauthorized`
```json
{
    "message": "Invalid token"
}
```

### Authorization Errors

**Cannot Modify Others' IP (Regular User):** `403 Forbidden`
```json
{
    "message": "Forbidden. You can only modify your own IP addresses."
}
```

**Cannot Delete (Regular User):** `403 Forbidden`
```json
{
    "message": "Forbidden. Only administrators can delete IP addresses."
}
```

**Admin Route Access Denied:** `403 Forbidden`
```json
{
    "message": "Insufficient permissions"
}
```

### Not Found

**IP Address Not Found:** `404 Not Found`
```json
{
    "message": "No query results for model [App\\Models\\IpAddress] 999"
}
```

## Permissions

| Action | Regular User | Admin |
|--------|--------------|-------|
| List all IPs | Yes | Yes |
| View any IP | Yes | Yes |
| Create IP | Yes | Yes |
| Update own IP (label only) | Yes | Yes |
| Update any IP (all fields) | No | Yes |
| Delete IP | No | Yes |
| View audit logs | No | Yes |
| Verify audit integrity | No | Yes |

## Request Headers

All requests should include:
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer {your_access_token}
```

## Audit Log

All create, update, and delete operations are logged with:
- Action performed
- Old and new values
- User information
- Client IP address
- Timestamp
- Cryptographic hash (tamper detection)

The audit log uses a hash chain where each entry includes the hash of the previous entry, making it tamper-proof.
