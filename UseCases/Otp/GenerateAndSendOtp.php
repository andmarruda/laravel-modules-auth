<?php

namespace App\Modules\AuthModule\UseCases\Otp;

use App\Modules\AuthModule\Models\User;
use App\Modules\AuthModule\Ports\Repositories\OtpRepositoryInterface;
use App\Modules\AuthModule\Ports\Services\OtpMailerInterface;

class GenerateAndSendOtp
{
    public function __construct(
        private OtpRepositoryInterface $otps,
        private OtpMailerInterface $mailer,
    ) {}

    public function execute(User $user, string $type = 'email_verification'): GenerateAndSendOtpResult
    {
        $latest = $this->otps->findLatestValidForUser($user->id, $type);

        if ($latest && $latest->created_at->diffInSeconds(now()) < 60) {
            return GenerateAndSendOtpResult::throttled();
        }

        $this->otps->invalidateAllForUser($user->id, $type);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = $this->otps->create([
            'user_id' => $user->id,
            'code' => $code,
            'type' => $type,
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->mailer->sendOtp($user, $otp);

        return GenerateAndSendOtpResult::success($otp);
    }
}
