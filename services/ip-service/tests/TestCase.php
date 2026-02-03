<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;
use Closure;

abstract class TestCase extends BaseTestCase
{
    /**
     * Helper to simulate authenticated request with user headers.
     * This bypasses token validation by mocking the user directly.
     */
    protected function withAuthHeaders(int $userId = 1, string $role = 'user', string $email = 'test@example.com'): self
    {
        // Bind a mock middleware that sets user without external validation
        $this->app->bind('App\Http\Middleware\ValidateToken', function () use ($userId, $role, $email) {
            return new class($userId, $role, $email) {
                public function __construct(
                    private int $userId,
                    private string $role,
                    private string $email
                ) {}

                public function handle(Request $request, Closure $next, ?string $scope = null)
                {
                    if ($scope && $this->role !== $scope) {
                        return response()->json(['message' => 'Insufficient permissions'], 403);
                    }

                    $request->attributes->set('user', [
                        'id' => $this->userId,
                        'role' => $this->role,
                        'email' => $this->email,
                    ]);

                    return $next($request);
                }
            };
        });

        return $this->withHeaders([
            'X-User-Id' => $userId,
            'X-User-Role' => $role,
            'X-User-Email' => $email,
            'X-Session-Id' => 'test-session-123',
            'Authorization' => 'Bearer test-token',
        ]);
    }

    /**
     * Helper to simulate admin authenticated request.
     */
    protected function withAdminHeaders(int $userId = 1, string $email = 'admin@example.com'): self
    {
        return $this->withAuthHeaders($userId, 'admin', $email);
    }
}
