<?php

namespace Andmarruda\AuthModule\UseCases\Profile;

class ChangePasswordResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?string $error,
    ) {}

    public static function success(): self
    {
        return new self(true, null);
    }

    public static function userNotFound(): self
    {
        return new self(false, 'user_not_found');
    }

    public static function currentPasswordInvalid(): self
    {
        return new self(false, 'current_password_invalid');
    }
}
