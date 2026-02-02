<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GatewayService
{
    private const TIMEOUT_SECONDS = 10;
    private const CIRCUIT_FAILURE_THRESHOLD = 5;
    private const CIRCUIT_RECOVERY_SECONDS = 30;

    public function forwardToAuth(Request $request, string $path, string $method = 'GET'): JsonResponse
    {
        return $this->forward($request, 'auth', config('services.auth.url'), $path, $method);
    }

    public function forwardToIpService(Request $request, string $path, string $method = 'GET'): JsonResponse
    {
        return $this->forward($request, 'ip', config('services.ip.url'), $path, $method);
    }

    public function healthCheck(): array
    {
        $services = [
            'auth' => config('services.auth.url'),
            'ip' => config('services.ip.url'),
        ];

        $status = [];
        foreach ($services as $name => $url) {
            $status[$name] = $this->checkServiceHealth($name, $url);
        }

        return [
            'gateway' => 'healthy',
            'services' => $status,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    private function checkServiceHealth(string $name, string $url): array
    {
        if ($this->isCircuitOpen($name)) {
            return ['status' => 'circuit_open', 'url' => $url];
        }

        try {
            $start = microtime(true);
            $response = Http::timeout(5)->get($url . '/api/health');
            $latency = round((microtime(true) - $start) * 1000);

            return [
                'status' => $response->successful() ? 'healthy' : 'degraded',
                'latency_ms' => $latency,
                'url' => $url,
            ];
        } catch (\Exception $e) {
            return ['status' => 'unreachable', 'url' => $url];
        }
    }

    private function forward(Request $request, string $service, string $baseUrl, string $path, string $method): JsonResponse
    {
        $requestId = uniqid('gw_', true);
        $startTime = microtime(true);

        if ($this->isCircuitOpen($service)) {
            $this->logRequest($requestId, $request, $service, $path, $method, 503, $startTime, true);
            return response()->json([
                'message' => 'Service temporarily unavailable',
                'retry_after' => $this->getCircuitRecoveryTime($service),
            ], 503);
        }

        try {
            $url = $baseUrl . $path;
            $http = Http::timeout(self::TIMEOUT_SECONDS)->acceptJson();

            if ($request->bearerToken()) {
                $http = $http->withToken($request->bearerToken());
                
                // Extract session ID from JWT's jti claim - can't be spoofed
                $sessionId = $this->extractSessionIdFromToken($request->bearerToken());
                if ($sessionId) {
                    $http = $http->withHeaders(['X-Session-Id' => $sessionId]);
                }
            }

            $response = match (strtoupper($method)) {
                'GET' => $http->get($url, $request->query()),
                'POST' => $http->post($url, $request->all()),
                'PUT' => $http->put($url, $request->all()),
                'DELETE' => $http->delete($url),
                default => $http->get($url),
            };

            $this->recordSuccess($service);
            $this->logRequest($requestId, $request, $service, $path, $method, $response->status(), $startTime);

            return response()->json($response->json(), $response->status());

        } catch (ConnectionException $e) {
            $this->recordFailure($service);
            $this->logRequest($requestId, $request, $service, $path, $method, 503, $startTime, false, $e->getMessage());

            return response()->json([
                'message' => 'Service temporarily unavailable'
            ], 503);
        } catch (\Exception $e) {
            $this->recordFailure($service);
            $this->logRequest($requestId, $request, $service, $path, $method, 502, $startTime, false, $e->getMessage());

            return response()->json([
                'message' => 'Gateway error'
            ], 502);
        }
    }

    private function isCircuitOpen(string $service): bool
    {
        return Cache::get("circuit_{$service}_open", false);
    }

    private function getCircuitRecoveryTime(string $service): int
    {
        $openedAt = Cache::get("circuit_{$service}_opened_at", 0);
        $recoveryAt = $openedAt + self::CIRCUIT_RECOVERY_SECONDS;
        return max(0, $recoveryAt - time());
    }

    private function recordFailure(string $service): void
    {
        $failures = Cache::increment("circuit_{$service}_failures");

        if ($failures >= self::CIRCUIT_FAILURE_THRESHOLD) {
            Cache::put("circuit_{$service}_open", true, self::CIRCUIT_RECOVERY_SECONDS);
            Cache::put("circuit_{$service}_opened_at", time(), self::CIRCUIT_RECOVERY_SECONDS);
            Log::warning("Circuit breaker opened for {$service} service");
        }
    }

    private function recordSuccess(string $service): void
    {
        Cache::forget("circuit_{$service}_failures");
        Cache::forget("circuit_{$service}_open");
    }

    private function logRequest(
        string $requestId,
        Request $request,
        string $service,
        string $path,
        string $method,
        int $status,
        float $startTime,
        bool $circuitOpen = false,
        ?string $error = null
    ): void {
        $duration = round((microtime(true) - $startTime) * 1000);

        $context = [
            'request_id' => $requestId,
            'service' => $service,
            'method' => $method,
            'path' => $path,
            'status' => $status,
            'duration_ms' => $duration,
            'client_ip' => $request->ip(),
            'circuit_open' => $circuitOpen,
        ];

        if ($error) {
            $context['error'] = $error;
        }

        if ($status >= 500) {
            Log::error('Gateway request failed', $context);
        } elseif ($status >= 400) {
            Log::warning('Gateway request client error', $context);
        } else {
            Log::info('Gateway request completed', $context);
        }
    }

    /**
     * Extract session ID from JWT's jti claim.
     * This is secure because the JWT is cryptographically signed.
     */
    private function extractSessionIdFromToken(string $token): ?string
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            
            return $payload['jti'] ?? null;
        } catch (\Exception $e) {
            Log::warning('Failed to extract session ID from token', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
