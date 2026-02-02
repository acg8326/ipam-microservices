<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Token;

class AuthService
{
    /**
     * Token expiration time in seconds (1 hour)
     */
    private const TOKEN_EXPIRATION_SECONDS = 3600;

    /**
     * Register a new user and create access token.
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // Model cast handles hashing
            'role' => $data['role'] ?? 'user',
        ]);

        return $this->createTokenResponse($user);
    }

    /**
     * Authenticate user and create access token.
     *
     * @throws ValidationException
     */
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Revoke all existing tokens for this user
        $this->revokeUserTokens($user);

        return $this->createTokenResponse($user);
    }

    /**
     * Logout user by revoking current token.
     */
    public function logout(User $user): void
    {
        $user->token()->revoke();
    }

    /**
     * Refresh user token by revoking current and creating new.
     */
    public function refresh(User $user): array
    {
        $user->token()->revoke();
        
        return $this->createTokenResponse($user);
    }

    /**
     * Get all users (admin function).
     */
    public function getAllUsers(): \Illuminate\Database\Eloquent\Collection
    {
        return User::all();
    }

    /**
     * Create token response with user data and expiration info.
     */
    private function createTokenResponse(User $user): array
    {
        $tokenResult = $user->createToken('auth_token', [$user->role]);
        
        return [
            'user' => $user,
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_in' => self::TOKEN_EXPIRATION_SECONDS,
        ];
    }

    /**
     * Revoke all tokens for a user.
     */
    private function revokeUserTokens(User $user): void
    {
        $user->tokens()->update(['revoked' => true]);
    }
}
