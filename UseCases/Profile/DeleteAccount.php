<?php

namespace Andmarruda\AuthModule\UseCases\Profile;

use Andmarruda\AuthModule\Ports\Repositories\UserRepositoryInterface;
use Andmarruda\AuthModule\Ports\Services\AuditLoggerInterface;

class DeleteAccount
{
    public function __construct(
        private UserRepositoryInterface $users,
        private AuditLoggerInterface $auditLogger,
    ) {}

    public function execute(int $userId, ?string $ipAddress = null, ?string $userAgent = null): DeleteAccountResult
    {
        $user = $this->users->findById($userId);

        if (!$user) {
            return DeleteAccountResult::userNotFound();
        }

        $this->auditLogger->logAccountDeleted($user, $ipAddress, $userAgent);

        $user->delete();

        return DeleteAccountResult::success();
    }
}
