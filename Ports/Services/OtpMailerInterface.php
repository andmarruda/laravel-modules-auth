<?php

namespace Andmarruda\AuthModule\Ports\Services;

use Andmarruda\AuthModule\Models\Otp;
use Andmarruda\AuthModule\Models\User;

interface OtpMailerInterface
{
    public function sendOtp(User $user, Otp $otp): void;

    public function sendPasswordResetOtp(User $user, Otp $otp): void;
}
