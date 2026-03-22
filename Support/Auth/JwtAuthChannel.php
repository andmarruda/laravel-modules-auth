<?php

namespace Andmarruda\AuthModule\Support\Auth;

use Andmarruda\AuthModule\Contracts\AuthChannelInterface;
use Andmarruda\AuthModule\Models\User;
use Andmarruda\AuthModule\Ports\Services\JwtManagerInterface;
use Illuminate\Http\Request;

class JwtAuthChannel implements AuthChannelInterface
{
    public function __construct(private JwtManagerInterface $jwtManager)
    {
    }

    public function name(): string
    {
        return 'jwt';
    }

    public function authenticate(User $user, Request $request): array
    {
        return $this->issueTokenPayload($user);
    }

    public function refresh(User $user, Request $request): array
    {
        return $this->issueTokenPayload($user);
    }

    public function logout(User $user, Request $request): void
    {
    }

    /**
     * @return array<string, mixed>
     */
    private function issueTokenPayload(User $user): array
    {
        return [
            'token' => $this->jwtManager->issueToken($user),
            'type' => 'Bearer',
            'expires_in' => max(1, (int) config('authmodule.jwt.ttl_minutes', 60)) * 60,
        ];
    }
}
