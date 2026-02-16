<?php

namespace Andmarruda\AuthModule\UseCases\Profile;

use Andmarruda\AuthModule\Ports\Repositories\UserRepositoryInterface;
use Andmarruda\AuthModule\Ports\Services\AuditLoggerInterface;

class UpdateProfile
{
    public function __construct(
        private UserRepositoryInterface $users,
        private AuditLoggerInterface $auditLogger,
    ) {}

    public function execute(int $userId, string $name, ?string $ipAddress = null, ?string $userAgent = null): UpdateProfileResult
    {
        $user = $this->users->findById($userId);

        if (!$user) {
            return UpdateProfileResult::userNotFound();
        }

        $changedFields = [];

        if ($user->name !== $name) {
            $changedFields[] = 'name';
            $user->name = $name;
        }

        if (empty($changedFields)) {
            return UpdateProfileResult::success($user);
        }

        $user = $this->users->save($user);

        $this->auditLogger->logProfileUpdated($user, $changedFields, $ipAddress, $userAgent);

        return UpdateProfileResult::success($user);
    }
}
