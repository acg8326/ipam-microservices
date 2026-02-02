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
    "token_type": "Bearer"
}
```

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
    "token_type": "Bearer"
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
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "Bearer"
}
```

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
```