# Testing Documentation

## Overview

The IPAM project includes both frontend (Vitest) and backend (PHPUnit) tests to ensure code quality and reliability.

## Quick Reference

```bash
# Frontend tests
cd frontend && npm run test:run

# Backend tests (requires running containers)
docker exec ipam-auth-service php artisan test
docker exec ipam-ip-service php artisan test
docker exec ipam-gateway php artisan test

# Run all backend tests
make test
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
| `src/stores/auth.test.ts` | 4 | Auth store state and actions |
| `src/stores/ipAddresses.test.ts` | 4 | IP store state and actions |
| `src/utils/validation.test.ts` | 8 | IP address validation utilities |

**Total: 16 tests**

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

Tests must run inside Docker containers where the Laravel app is configured:

```bash
# Auth Service
docker exec ipam-auth-service php artisan test

# IP Service
docker exec ipam-ip-service php artisan test

# Gateway
docker exec ipam-gateway php artisan test

# With verbose output
docker exec ipam-auth-service php artisan test --verbose

# Run specific test class
docker exec ipam-auth-service php artisan test --filter=AuthenticationTest

# Run specific test method
docker exec ipam-auth-service php artisan test --filter=test_user_can_login
```

### Test Files

#### Auth Service (`services/auth-service/tests/`)

| File | Tests | Description |
|------|-------|-------------|
| `Feature/AuthenticationTest.php` | 9 | Login, logout, token validation |
| `Feature/UserManagementTest.php` | 5 | User CRUD by admin |

#### IP Service (`services/ip-service/tests/`)

| File | Tests | Description |
|------|-------|-------------|
| `Feature/IpAddressTest.php` | 14 | IP CRUD operations |
| `Feature/AuditLogTest.php` | 9 | Audit log viewing |

#### Gateway (`services/gateway/tests/`)

| File | Tests | Description |
|------|-------|-------------|
| `Feature/HealthCheckTest.php` | 2 | Health endpoint |

**Total: 39 tests**

### Writing New Tests

#### Feature Test Example

```php
// tests/Feature/ExampleTest.php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_example_endpoint_returns_success(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/example');

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
