<?php

namespace Andmarruda\AuthModule\UseCases\Otp;

use Andmarruda\AuthModule\Models\Otp;

class GenerateAndSendOtpResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?string $error,
        public readonly ?Otp $otp,
    ) {}

    public static function success(Otp $otp): self
    {
        return new self(true, null, $otp);
    }

    public static function throttled(): self
    {
        return new self(false, 'throttled', null);
    }
}
