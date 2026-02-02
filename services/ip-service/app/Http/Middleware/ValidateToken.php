<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class ValidateToken
{
    public function handle(Request $request, Closure $next, ?string $scope = null): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token required'], 401);
        }

        try {
            $authServiceUrl = config('services.auth.url', 'http://localhost:8001');
            
            $response = Http::withToken($token)
                ->acceptJson()
                ->get("{$authServiceUrl}/api/user");

            if ($response->failed()) {
                return response()->json(['message' => 'Invalid token'], 401);
            }

            $user = $response->json();

            if ($scope && $user['role'] !== $scope) {
                return response()->json(['message' => 'Insufficient permissions'], 403);
            }

            $request->attributes->set('user', $user);

            return $next($request);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Auth service unavailable'], 503);
        }
    }
}