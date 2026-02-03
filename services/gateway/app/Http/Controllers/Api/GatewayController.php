<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GatewayController extends Controller
{
    public function __construct(private GatewayService $gateway)
    {
    }

    public function register(Request $request): JsonResponse
    {
        return $this->gateway->forwardToAuth($request, '/api/register', 'POST');
    }

    public function login(Request $request): JsonResponse
    {
        return $this->gateway->forwardToAuth($request, '/api/login', 'POST');
    }

    public function logout(Request $request): JsonResponse
    {
        return $this->gateway->forwardToAuth($request, '/api/logout', 'POST');
    }

    public function user(Request $request): JsonResponse
    {
        return $this->gateway->forwardToAuth($request, '/api/user', 'GET');
    }

    public function refresh(Request $request): JsonResponse
    {
        return $this->gateway->forwardToAuth($request, '/api/refresh', 'POST');
    }

    public function users(Request $request): JsonResponse
    {
        return $this->gateway->forwardToAuth($request, '/api/users', 'GET');
    }

    public function ipAddressIndex(Request $request): JsonResponse
    {
        return $this->gateway->forwardToIpService($request, '/api/ip-addresses', 'GET');
    }

    public function ipAddressStore(Request $request): JsonResponse
    {
        return $this->gateway->forwardToIpService($request, '/api/ip-addresses', 'POST');
    }

    public function ipAddressShow(Request $request, int $id): JsonResponse
    {
        return $this->gateway->forwardToIpService($request, "/api/ip-addresses/{$id}", 'GET');
    }

    public function ipAddressUpdate(Request $request, int $id): JsonResponse
    {
        return $this->gateway->forwardToIpService($request, "/api/ip-addresses/{$id}", 'PUT');
    }

    public function ipAddressDestroy(Request $request, int $id): JsonResponse
    {
        return $this->gateway->forwardToIpService($request, "/api/ip-addresses/{$id}", 'DELETE');
    }

    public function auditLogIndex(Request $request): JsonResponse
    {
        return $this->gateway->forwardToIpService($request, '/api/audit-logs', 'GET');
    }

    public function auditLogVerify(Request $request): JsonResponse
    {
        return $this->gateway->forwardToIpService($request, '/api/audit-logs/verify', 'GET');
    }

    public function authAuditLogIndex(Request $request): JsonResponse
    {
        return $this->gateway->forwardToAuth($request, '/api/audit-logs', 'GET');
    }

    public function authAuditLogVerify(Request $request): JsonResponse
    {
        return $this->gateway->forwardToAuth($request, '/api/audit-logs/verify', 'GET');
    }

    public function health(): JsonResponse
    {
        return response()->json($this->gateway->healthCheck());
    }

    // Dashboard
    public function dashboardStats(Request $request): JsonResponse
    {
        return $this->gateway->forwardToIpService($request, '/api/dashboard/stats', 'GET');
    }
}