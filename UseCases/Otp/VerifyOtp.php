<?php

namespace App\Modules\AuthModule\UseCases\Otp;

use App\Modules\AuthModule\Ports\Repositories\OtpRepositoryInterface;
use App\Modules\AuthModule\Ports\Repositories\UserRepositoryInterface;

class VerifyOtp
{
    public function __construct(
        private UserRepositoryInterface $users,
        private OtpRepositoryInterface $otps,
    ) {}

    public function execute(string $email, string $code, string $type = 'email_verification'): VerifyOtpResult
    {
        $user = $this->users->findByEmail($email);

        if (!$user) {
            return VerifyOtpResult::userNotFound();
        }

        if ($type === 'email_verification' && $user->email_verified_at !== null) {
            return VerifyOtpResult::alreadyVerified();
        }

        $otp = $this->otps->findLatestValidForUser($user->id, $type);

        if (!$otp) {
            return VerifyOtpResult::otpNotFound();
        }

        if (!hash_equals($otp->code, $code)) {
            return VerifyOtpResult::invalidCode();
        }

        $this->otps->markAsVerified($otp);

        if ($type === 'email_verification') {
            $user->update(['email_verified_at' => now()]);
        }

        return VerifyOtpResult::success($user);
    }
}
