<?php

namespace Andmarruda\AuthModule\Infrastructure\Services;

use Andmarruda\AuthModule\Infrastructure\Mail\OtpMail;
use Andmarruda\AuthModule\Infrastructure\Mail\PasswordResetOtpMail;
use Andmarruda\AuthModule\Models\Otp;
use Andmarruda\AuthModule\Models\User;
use Andmarruda\AuthModule\Ports\Services\OtpMailerInterface;
use Illuminate\Support\Facades\Mail;

class MailOtpMailer implements OtpMailerInterface
{
    public function sendOtp(User $user, Otp $otp): void
    {
        Mail::to($user->email)
            ->send(new OtpMail($user, $otp));
    }

    public function sendPasswordResetOtp(User $user, Otp $otp): void
    {
        Mail::to($user->email)
            ->send(new PasswordResetOtpMail($user, $otp));
    }
}
