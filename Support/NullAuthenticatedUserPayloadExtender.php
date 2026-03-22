<?php

namespace Andmarruda\AuthModule\Support;

use Andmarruda\AuthModule\Contracts\AuthenticatedUserPayloadExtenderInterface;
use Andmarruda\AuthModule\Models\User;
use Illuminate\Http\Request;

class NullAuthenticatedUserPayloadExtender implements AuthenticatedUserPayloadExtenderInterface
{
    public function extend(User $user, Request $request): array
    {
        return [];
    }
}
