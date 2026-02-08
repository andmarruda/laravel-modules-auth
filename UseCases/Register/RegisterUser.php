<?php

namespace App\Modules\AuthModule\UseCases\Register;

use App\Modules\AuthModule\Ports\Repositories\InvitationRepositoryInterface;
use App\Modules\AuthModule\Ports\Repositories\UserRepositoryInterface;
use App\Modules\AuthModule\Ports\Services\AuditLoggerInterface;

class RegisterUser
{
    public function __construct(
        private InvitationRepositoryInterface $invitations,
        private UserRepositoryInterface $users,
        private AuditLoggerInterface $auditLogger,
    ) {}

    public function execute(string $token, string $name, string $password, ?string $ipAddress = null, ?string $userAgent = null): RegisterUserResult
    {
        $invitation = $this->invitations->findByToken($token);

        if (!$invitation) {
            return RegisterUserResult::invitationNotFound();
        }

        if ($invitation->isAccepted()) {
            return RegisterUserResult::invitationAlreadyUsed();
        }

        if ($invitation->isExpired()) {
            return RegisterUserResult::invitationExpired();
        }

        $user = $this->users->create([
            'name' => $name,
            'email' => $invitation->email,
            'password' => $password,
        ]);

        $this->invitations->markAsAccepted($invitation);

        $this->auditLogger->logUserRegistered($user, $invitation, $ipAddress, $userAgent);

        return RegisterUserResult::success($user);
    }
}
