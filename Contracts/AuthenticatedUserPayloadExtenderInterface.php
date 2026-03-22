<?php

namespace Andmarruda\AuthModule\Contracts;

use Andmarruda\AuthModule\Models\User;
use Illuminate\Http\Request;

interface AuthenticatedUserPayloadExtenderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function extend(User $user, Request $request): array;
}
