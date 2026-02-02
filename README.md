# IPAM Microservices

A microservices-based IP Address Management System built with Laravel and Vue.js.

## Architecture
- **Vue.js Frontend** - Single Page Application
- **Gateway Service** - API Gateway/Router
- **Auth Service** - Authentication & Authorization (JWT + RBAC)
- **IP Management Service** - Core CRUD operations for IP records

┌─────────────────────────────────────────────────────┐
│                  Vue.js Frontend                     │
└─────────────────────┬───────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────┐
│                  Gateway Service                     │
│              (Routes requests, validates tokens)     │
└───────┬─────────────────────┬───────────────────────┘
        │                     │
        ▼                     ▼
┌───────────────┐     ┌───────────────────┐
│ Auth Service  │     │ IP Management     │
│ (JWT, Login,  │     │ Service           │
│  Register)    │     │ (CRUD operations) │
└───────────────┘     └───────────────────┘

## Tech Stack
- **Backend:** Laravel 12
- **Frontend:** Vue.js 3.5
- **PHP:** 8.2
- **Database:** MySQL
- **Containerization:** Docker

## Services

### Auth Service (Port 8001)
Handles authentication and authorization using Laravel Passport.

**Endpoints:**
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | /api/register | Register new user | No |
| POST | /api/login | Login and get token | No |
| GET | /api/user | Get current user | Yes |
| POST | /api/logout | Logout and revoke token | Yes |
| POST | /api/refresh | Refresh access token | Yes |
| GET | /api/users | List all users (admin only) | Admin |

## Quick Start

### Prerequisites
- PHP 8.2
- Composer
- MySQL
- Node.js & npm

### Installation

1. Clone the repository
```bash
git clone https://github.com/acg8326/ipam-microservices.git
cd ipam-microservices
```

2. Setup Auth Service
```bash
cd services/auth-service
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan passport:install
php artisan serve --port=8001
```

## API Authentication

All protected endpoints require a Bearer token:
```
Authorization: Bearer {your_access_token}
```

### Roles
- **admin** - Full access to all resources
- **user** - Limited access to resources