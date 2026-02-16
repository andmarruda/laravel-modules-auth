<?php

namespace Andmarruda\AuthModule\UseCases\PasswordReset;

class RequestPasswordResetResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?string $error,
    ) {}

    public static function success(): self
    {
        return new self(true, null);
    }

    public static function throttled(): self
    {
        return new self(false, 'throttled');
    }
}
