<?php

namespace Andmarruda\AuthModule\Ports\Services;

use Andmarruda\AuthModule\Models\User;

interface JwtManagerInterface
{
    /**
     * @param array<string, mixed> $customClaims
     */
    public function issueToken(User $user, array $customClaims = []): string;

    /**
     * @return array<string, mixed>|null
     */
    public function decode(string $token): ?array;
}
