<?php

namespace App\Modules\AuthModule\Infrastructure\Services;

use App\Modules\AuthModule\Infrastructure\Mail\OtpMail;
use App\Modules\AuthModule\Models\Otp;
use App\Modules\AuthModule\Models\User;
use App\Modules\AuthModule\Ports\Services\OtpMailerInterface;
use Illuminate\Support\Facades\Mail;

class MailOtpMailer implements OtpMailerInterface
{
    public function sendOtp(User $user, Otp $otp): void
    {
        Mail::to($user->email)
            ->queue(new OtpMail($user, $otp));
    }
}
