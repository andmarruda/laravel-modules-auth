<?php

namespace Andmarruda\AuthModule\UseCases\PasswordReset;

use Andmarruda\AuthModule\Ports\Repositories\OtpRepositoryInterface;
use Andmarruda\AuthModule\Ports\Repositories\UserRepositoryInterface;
use Andmarruda\AuthModule\Ports\Services\AuditLoggerInterface;
use Andmarruda\AuthModule\Ports\Services\OtpMailerInterface;

class RequestPasswordReset
{
    public function __construct(
        private UserRepositoryInterface $users,
        private OtpRepositoryInterface $otps,
        private OtpMailerInterface $mailer,
        private AuditLoggerInterface $auditLogger,
    ) {}

    public function execute(string $email, ?string $ipAddress = null, ?string $userAgent = null): RequestPasswordResetResult
    {
        $user = $this->users->findByEmail($email);

        if (!$user) {
            return RequestPasswordResetResult::success();
        }

        $latest = $this->otps->findLatestValidForUser($user->id, 'password_reset');

        if ($latest && $latest->created_at->diffInSeconds(now()) < 60) {
            return RequestPasswordResetResult::throttled();
        }

        $this->otps->invalidateAllForUser($user->id, 'password_reset');

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = $this->otps->create([
            'user_id' => $user->id,
            'code' => $code,
            'type' => 'password_reset',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->mailer->sendPasswordResetOtp($user, $otp);

        $this->auditLogger->logPasswordResetRequested($user, $ipAddress, $userAgent);

        return RequestPasswordResetResult::success();
    }
}
