<?php

namespace Andmarruda\AuthModule\UseCases\PasswordReset;

use Andmarruda\AuthModule\Ports\Repositories\OtpRepositoryInterface;
use Andmarruda\AuthModule\Ports\Repositories\UserRepositoryInterface;
use Andmarruda\AuthModule\Ports\Services\AuditLoggerInterface;

class ResetPasswordWithOtp
{
    public function __construct(
        private UserRepositoryInterface $users,
        private OtpRepositoryInterface $otps,
        private AuditLoggerInterface $auditLogger,
    ) {}

    public function execute(
        string $email,
        string $code,
        string $newPassword,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ResetPasswordWithOtpResult {
        $user = $this->users->findByEmail($email);

        if (!$user) {
            return ResetPasswordWithOtpResult::userNotFound();
        }

        $otp = $this->otps->findLatestValidForUser($user->id, 'password_reset');

        if (!$otp) {
            return ResetPasswordWithOtpResult::otpNotFound();
        }

        if (!hash_equals($otp->code, $code)) {
            return ResetPasswordWithOtpResult::invalidCode();
        }

        $this->otps->markAsVerified($otp);

        $user->password = $newPassword;
        $this->users->save($user);

        $this->auditLogger->logPasswordChanged($user, $ipAddress, $userAgent);

        return ResetPasswordWithOtpResult::success($user);
    }
}
