<?php

namespace Andmarruda\AuthModule\UseCases\PasswordReset;

use Andmarruda\AuthModule\Models\User;

class ResetPasswordWithOtpResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?string $error,
        public readonly ?User $user,
    ) {}

    public static function success(User $user): self
    {
        return new self(true, null, $user);
    }

    public static function userNotFound(): self
    {
        return new self(false, 'user_not_found', null);
    }

    public static function otpNotFound(): self
    {
        return new self(false, 'otp_not_found', null);
    }

    public static function invalidCode(): self
    {
        return new self(false, 'invalid_code', null);
    }
}
