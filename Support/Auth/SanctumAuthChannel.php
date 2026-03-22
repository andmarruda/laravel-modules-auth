<?php

namespace Andmarruda\AuthModule\Support\Auth;

use Andmarruda\AuthModule\Contracts\AuthChannelInterface;
use Andmarruda\AuthModule\Models\User;
use Illuminate\Http\Request;
use RuntimeException;

class SanctumAuthChannel implements AuthChannelInterface
{
    public function name(): string
    {
        return 'sanctum';
    }

    public function authenticate(User $user, Request $request): array
    {
        return $this->issueTokenPayload($user, $request);
    }

    public function refresh(User $user, Request $request): array
    {
        if (method_exists($user, 'currentAccessToken') && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return $this->issueTokenPayload($user, $request);
    }

    public function logout(User $user, Request $request): void
    {
        if (method_exists($user, 'currentAccessToken') && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function issueTokenPayload(User $user, Request $request): array
    {
        if (!method_exists($user, 'createToken')) {
            throw new RuntimeException('Sanctum auth channel requires the authenticatable model to use Laravel Sanctum.');
        }

        $tokenName = (string) ($request->input('token_name') ?: config('authmodule.auth.sanctum.token_name', 'authmodule'));
        $abilities = (array) config('authmodule.auth.sanctum.abilities', ['*']);
        $expiresAt = config('authmodule.auth.sanctum.expires_at');

        if ($expiresAt) {
            $tokenResult = $user->createToken($tokenName, $abilities, now()->parse((string) $expiresAt));
        } else {
            $tokenResult = $user->createToken($tokenName, $abilities);
        }

        return [
            'token' => $tokenResult->plainTextToken,
            'type' => 'Bearer',
            'expires_in' => null,
        ];
    }
}
