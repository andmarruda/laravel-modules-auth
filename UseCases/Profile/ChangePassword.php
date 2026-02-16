<?php

namespace Andmarruda\AuthModule\UseCases\Profile;

use Andmarruda\AuthModule\Ports\Repositories\UserRepositoryInterface;
use Andmarruda\AuthModule\Ports\Services\AuditLoggerInterface;
use Illuminate\Support\Facades\Hash;

class ChangePassword
{
    public function __construct(
        private UserRepositoryInterface $users,
        private AuditLoggerInterface $auditLogger,
    ) {}

    public function execute(int $userId, string $currentPassword, string $newPassword, ?string $ipAddress = null, ?string $userAgent = null): ChangePasswordResult
    {
        $user = $this->users->findById($userId);

        if (!$user) {
            return ChangePasswordResult::userNotFound();
        }

        if (!Hash::check($currentPassword, $user->password)) {
            return ChangePasswordResult::currentPasswordInvalid();
        }

        $user->password = $newPassword;
        $this->users->save($user);

        $this->auditLogger->logPasswordChanged($user, $ipAddress, $userAgent);

        return ChangePasswordResult::success();
    }
}
