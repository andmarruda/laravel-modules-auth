<?php

namespace App\Modules\AuthModule\Ports\Services;

use App\Modules\AuthModule\Models\Otp;
use App\Modules\AuthModule\Models\User;

interface OtpMailerInterface
{
    public function sendOtp(User $user, Otp $otp): void;
}
