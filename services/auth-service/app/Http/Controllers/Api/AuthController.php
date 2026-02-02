<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register(
            $request->validated(),
            $request->ip()
        );

        return response()->json($result, 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->validated(),
            $request->ip()
        );

        return response()->json($result);
    }

    public function logout(Request $request): JsonResponse
    {
        $sessionId = $request->header('X-Session-Id');
        
        $this->authService->logout(
            $request->user(),
            $sessionId,
            $request->ip()
        );

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function refresh(Request $request): JsonResponse
    {
        $sessionId = $request->header('X-Session-Id');
        
        $result = $this->authService->refresh(
            $request->user(),
            $sessionId,
            $request->ip()
        );

        return response()->json($result);
    }

    public function users(): JsonResponse
    {
        $users = $this->authService->getAllUsers();

        return response()->json($users);
    }
}
