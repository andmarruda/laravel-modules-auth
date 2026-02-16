<?php

namespace Andmarruda\AuthModule\UseCases\Profile;

class DeleteAccountResult
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
}
