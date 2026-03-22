<?php

namespace Andmarruda\AuthModule\UseCases\Register;

use Andmarruda\AuthModule\Models\User;

class RegisterOpenUserResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?User $user = null,
        public readonly ?string $error = null,
    ) {
    }

    public static function success(User $user): self
    {
        return new self(true, $user);
    }

    public static function emailAlreadyRegistered(): self
    {
        return new self(false, null, 'email_already_registered');
    }
}
