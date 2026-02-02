<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    private const TOKEN_EXPIRATION_SECONDS = 3600;

    public function register(array $data, ?string $ipAddress = null): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'] ?? 'user',
        ]);

        $sessionId = $this->generateSessionId();

        AuditLog::log(
            'register',
            'user',
            $user->id,
            null,
            ['name' => $user->name, 'email' => $user->email, 'role' => $user->role],
            $user->id,
            $user->email,
            $sessionId,
            $ipAddress
        );

        return $this->createTokenResponse($user, $sessionId);
    }

    public function login(array $credentials, ?string $ipAddress = null): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            AuditLog::log(
                'login_failed',
                'user',
                null,
                null,
                ['email' => $credentials['email']],
                null,
                $credentials['email'],
                null,
                $ipAddress
            );

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $this->revokeUserTokens($user);

        $sessionId = $this->generateSessionId();

        AuditLog::log(
            'login',
            'user',
            $user->id,
            null,
            ['email' => $user->email],
            $user->id,
            $user->email,
            $sessionId,
            $ipAddress
        );

        return $this->createTokenResponse($user, $sessionId);
    }

    public function logout(User $user, ?string $sessionId = null, ?string $ipAddress = null): void
    {
        AuditLog::log(
            'logout',
            'user',
            $user->id,
            null,
            ['email' => $user->email],
            $user->id,
            $user->email,
            $sessionId,
            $ipAddress
        );

        $user->token()->revoke();
    }

    public function refresh(User $user, ?string $sessionId = null, ?string $ipAddress = null): array
    {
        $user->token()->revoke();

        AuditLog::log(
            'token_refresh',
            'user',
            $user->id,
            null,
            ['email' => $user->email],
            $user->id,
            $user->email,
            $sessionId,
            $ipAddress
        );

        return $this->createTokenResponse($user, $sessionId ?? $this->generateSessionId());
    }

    public function getAllUsers(): \Illuminate\Database\Eloquent\Collection
    {
        return User::all();
    }

    private function createTokenResponse(User $user, ?string $auditSessionId = null): array
    {
        $tokenResult = $user->createToken('auth_token', [$user->role]);
        
        // Use the token's jti (JWT ID) as the session ID for subsequent requests
        // This is cryptographically secure - can't be forged without private key
        $sessionId = $tokenResult->token->id;

        // If we logged audit before token creation, update with real session ID
        if ($auditSessionId && $auditSessionId !== $sessionId) {
            AuditLog::where('session_id', $auditSessionId)
                ->update(['session_id' => $sessionId]);
        }

        return [
            'user' => $user,
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_in' => self::TOKEN_EXPIRATION_SECONDS,
            'session_id' => $sessionId, // Now tied to JWT, can't be spoofed
        ];
    }

    private function revokeUserTokens(User $user): void
    {
        $user->tokens()->update(['revoked' => true]);
    }

    private function generateSessionId(): string
    {
        return Str::uuid()->toString();
    }
}
