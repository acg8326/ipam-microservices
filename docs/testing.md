# Testing Documentation

## Overview

The IPAM project includes both frontend (Vitest) and backend (PHPUnit) tests to ensure code quality and reliability.

## Prerequisites

Tests run **locally** (not inside Docker) because production containers don't include dev dependencies.

### Backend Tests Requirements

| Requirement | Version | Check Command |
|-------------|---------|---------------|
| PHP | 8.2+ | `php -v` |
| PHP Extensions | mbstring, xml, curl, sqlite3, tokenizer, openssl | `php -m` |
| Composer | 2.0+ | `composer -V` |

### Frontend Tests Requirements

| Requirement | Version | Check Command |
|-------------|---------|---------------|
| Node.js | 18+ | `node -v` |
| npm | 9+ | `npm -v` |

### Installation by Platform

<details>
<summary><strong>Ubuntu / Debian / WSL</strong></summary>

```bash
# Update package list
sudo apt-get update

# Install PHP and required extensions
sudo apt-get install -y php8.2 php8.2-cli php8.2-mbstring php8.2-xml \
    php8.2-curl php8.2-sqlite3 php8.2-tokenizer php8.2-openssl unzip

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js 20.x
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# Install project dependencies
cd /path/to/ipam-microservices
cd services/auth-service && composer install
cd ../ip-service && composer install
cd ../gateway && composer install
cd ../../frontend && npm install
```

</details>

<details>
<summary><strong>macOS (Homebrew)</strong></summary>

```bash
# Install PHP
brew install php@8.2

# Install Composer
brew install composer

# Install Node.js
brew install node@20

# Install project dependencies
cd /path/to/ipam-microservices
cd services/auth-service && composer install
cd ../ip-service && composer install
cd ../gateway && composer install
cd ../../frontend && npm install
```

</details>

<details>
<summary><strong>Windows (Native - Not Recommended)</strong></summary>

> ⚠️ **Recommendation:** Use WSL 2 instead. Native Windows has path issues with some npm packages.

1. Install [PHP for Windows](https://windows.php.net/download/) (VS16 x64 Non Thread Safe)
2. Install [Composer](https://getcomposer.org/Composer-Setup.exe)
3. Install [Node.js](https://nodejs.org/) (LTS version)
4. Add PHP to your PATH environment variable
5. Run `composer install` in each service directory
6. Run `npm install` in the frontend directory

</details>

---

## Quick Reference

```bash
# Run all tests (backend + frontend)
make test

# Backend tests only
make test-be

# Frontend tests only
make test-fe
```

### Running Tests Individually

```bash
# Frontend tests
cd frontend && npm run test:run

# Backend tests (run locally - requires PHP 8.2+ and Composer)
cd services/auth-service && php artisan test
cd services/ip-service && php artisan test
cd services/gateway && php artisan test
```

## Troubleshooting

### WSL: `'vitest' is not recognized` or `UNC paths are not supported`

**Cause:** You're using Windows Node.js from WSL instead of Linux Node.js.

**Fix:** Install Node.js inside WSL:
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs
```

### `Class 'PDO' not found` or `driver not found`

**Cause:** Missing PHP SQLite extension (tests use SQLite in-memory database).

**Fix:**
```bash
sudo apt-get install php8.2-sqlite3
```

### `composer: command not found`

**Fix:**
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

---

## Frontend Tests (Vitest)

### Setup

Tests use [Vitest](https://vitest.dev/) with Vue Test Utils for component testing.

**Configuration**: `frontend/vitest.config.ts`

### Running Tests

```bash
cd frontend

# Run once
npm run test:run

# Watch mode (re-runs on file changes)
npm run test

# With coverage report
npm run test:coverage
```

### Test Files

| File | Tests | Description |
|------|-------|-------------|
| `src/api/client.test.ts` | 17 | Token management, expiry, refresh |
| `src/stores/auth.test.ts` | 4 | Auth store state and actions |
| `src/stores/ipAddresses.test.ts` | 4 | IP store state and actions |
| `src/utils/validation.test.ts` | 8 | IP address validation utilities |

**Total: 33 tests**

### Writing New Tests

Create test files with `.test.ts` suffix next to the file being tested:

```typescript
// src/stores/example.test.ts
import { describe, it, expect, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useExampleStore } from './example';

describe('Example Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('should have initial state', () => {
    const store = useExampleStore();
    expect(store.items).toEqual([]);
  });

  it('should add item', () => {
    const store = useExampleStore();
    store.addItem({ id: 1, name: 'Test' });
    expect(store.items).toHaveLength(1);
  });
});
```

### Test Conventions

- Use `describe` blocks to group related tests
- Use `it` for individual test cases
- Use `beforeEach` to reset state between tests
- Mock API calls with `vi.mock()`

---

## Backend Tests (PHPUnit)

### Setup

Laravel includes PHPUnit out of the box. Each service has its own test suite.

**Configuration**: `services/{service}/phpunit.xml`

### Running Tests

Tests run locally using PHP and Composer (not inside Docker containers):

```bash
# Auth Service
cd services/auth-service && php artisan test

# IP Service
cd services/ip-service && php artisan test

# Gateway
cd services/gateway && php artisan test

# With verbose output
cd services/auth-service && php artisan test --verbose

# Run specific test class
cd services/auth-service && php artisan test --filter=AuthenticationTest

# Run specific test method
cd services/auth-service && php artisan test --filter=test_user_can_login
```

### Test Files

#### Auth Service (`services/auth-service/tests/`)

| File | Tests | Description |
|------|-------|-------------|
| `Feature/AuthenticationTest.php` | 9 | Login, logout, registration, token validation |
| `Feature/UserManagementTest.php` | 5 | User CRUD by admin |
| `Unit/ExampleTest.php` | 1 | Basic unit test |
| `Feature/ExampleTest.php` | 1 | Basic feature test |

#### IP Service (`services/ip-service/tests/`)

| File | Tests | Description |
|------|-------|-------------|
| `Feature/IpAddressTest.php` | 13 | IP CRUD operations, permissions |
| `Feature/AuditLogTest.php` | 9 | Audit log creation, viewing, verification |
| `Unit/ExampleTest.php` | 1 | Basic unit test |
| `Feature/ExampleTest.php` | 1 | Basic feature test |

#### Gateway (`services/gateway/tests/`)

| File | Tests | Description |
|------|-------|-------------|
| `Feature/HealthCheckTest.php` | 2 | Health endpoint |
| `Unit/ExampleTest.php` | 1 | Basic unit test |
| `Feature/ExampleTest.php` | 1 | Basic feature test |

**Total Backend: 44 tests**

### Test Authentication Helpers

The IP Service tests use custom authentication helpers that mock the `ValidateToken` middleware:

```php
// In your test class (extends Tests\TestCase)
public function test_example(): void
{
    // Authenticate as regular user
    $response = $this->withAuthHeaders(userId: 1, role: 'user')
        ->getJson('/api/ip-addresses');

    // Authenticate as admin
    $response = $this->withAdminHeaders()
        ->deleteJson('/api/ip-addresses/1');
}
```

### Writing New Tests

#### Feature Test Example

```php
// tests/Feature/ExampleTest.php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_example_endpoint_returns_success(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        Passport::actingAs($user, ['admin']);

        $response = $this->getJson('/api/example');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_unauthenticated_user_cannot_access(): void
    {
        $response = $this->getJson('/api/example');

        $response->assertStatus(401);
    }
}
```

#### Unit Test Example

```php
// tests/Unit/ExampleServiceTest.php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ExampleService;

class ExampleServiceTest extends TestCase
{
    public function test_service_calculates_correctly(): void
    {
        $service = new ExampleService();
        
        $result = $service->calculate(10, 5);
        
        $this->assertEquals(15, $result);
    }
}
```

### Test Conventions

- Use `RefreshDatabase` trait for database tests
- Use factories for creating test data
- Use `actingAs()` for authenticated requests
- Test both success and failure cases
- Test authorization (admin vs user)

### Factories

Model factories are located in `database/factories/`:

```php
// database/factories/IpAddressFactory.php
<?php

namespace Database\Factories;

use App\Models\IpAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

class IpAddressFactory extends Factory
{
    protected $model = IpAddress::class;

    public function definition(): array
    {
        return [
            'ip_address' => $this->faker->unique()->ipv4(),
            'label' => $this->faker->words(2, true),
            'comment' => $this->faker->optional()->sentence(),
            'created_by' => 1,
        ];
    }
}
```

---

## Makefile Commands

Add to your `Makefile` for convenience:

```makefile
# Run all tests
test:
	docker exec ipam-auth-service php artisan test
	docker exec ipam-ip-service php artisan test
	docker exec ipam-gateway php artisan test
	cd frontend && npm run test:run

# Run frontend tests only
test-fe:
	cd frontend && npm run test:run

# Run backend tests only  
test-be:
	docker exec ipam-auth-service php artisan test
	docker exec ipam-ip-service php artisan test
	docker exec ipam-gateway php artisan test
```

---

## Test Coverage

### Frontend Coverage

```bash
cd frontend && npm run test:coverage
```

Generates coverage report in `frontend/coverage/`.

### Backend Coverage

```bash
docker exec ipam-auth-service php artisan test --coverage
```

Requires Xdebug or PCOV extension (not included in Docker images by default).

---

## CI/CD Integration

Example GitHub Actions workflow:

```yaml
# .github/workflows/test.yml
name: Tests

on: [push, pull_request]

jobs:
  frontend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
      - run: cd frontend && npm ci
      - run: cd frontend && npm run test:run

  backend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - run: docker compose up -d --build
      - run: sleep 30  # Wait for services
      - run: docker exec ipam-auth-service php artisan test
      - run: docker exec ipam-ip-service php artisan test
```

---

## Troubleshooting

### Frontend Tests Failing

```bash
# Clear cache and reinstall
cd frontend
rm -rf node_modules
npm install
npm run test:run
```

### Backend Tests Failing

```bash
# Check container is running
docker ps | grep ipam

# Check logs
docker logs ipam-auth-service

# Rebuild container
docker compose up -d --build auth-service
```

### Database Issues

```bash
# Reset test database
docker exec ipam-auth-service php artisan migrate:fresh --env=testing
```

---

*Last updated: February 3, 2026*
