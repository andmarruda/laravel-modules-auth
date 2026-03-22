<?php

namespace Andmarruda\AuthModule\Contracts;

use Andmarruda\AuthModule\Models\User;
use Illuminate\Http\Request;

interface AuthChannelInterface
{
    public function name(): string;

    /**
     * @return array<string, mixed>
     */
    public function authenticate(User $user, Request $request): array;

    /**
     * @return array<string, mixed>
     */
    public function refresh(User $user, Request $request): array;

    public function logout(User $user, Request $request): void;
}
