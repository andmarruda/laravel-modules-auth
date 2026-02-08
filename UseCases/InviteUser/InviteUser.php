<?php

namespace App\Modules\AuthModule\UseCases\InviteUser;

use App\Modules\AuthModule\Models\User;
use App\Modules\AuthModule\Ports\Repositories\InvitationRepositoryInterface;
use App\Modules\AuthModule\Ports\Repositories\UserRepositoryInterface;
use App\Modules\AuthModule\Ports\Services\AuditLoggerInterface;
use App\Modules\AuthModule\Ports\Services\InvitationMailerInterface;
use App\Modules\AuthModule\Ports\Services\TokenGeneratorInterface;

class InviteUser
{
    public function __construct(
        private InvitationRepositoryInterface $invitations,
        private UserRepositoryInterface $users,
        private TokenGeneratorInterface $tokenGenerator,
        private InvitationMailerInterface $mailer,
        private AuditLoggerInterface $auditLogger,
    ) {}

    public function execute(User $inviter, string $email, ?string $resourceScope = null, ?string $ipAddress = null, ?string $userAgent = null): InviteUserResult
    {
        if (!$inviter->isManager()) {
            return InviteUserResult::forbidden();
        }

        if ($this->users->emailExists($email)) {
            return InviteUserResult::emailAlreadyRegistered();
        }

        $invitation = $this->invitations->create([
            'email' => $email,
            'token' => $this->tokenGenerator->generate(),
            'invited_by' => $inviter->id,
            'resource_scope' => $resourceScope,
            'expires_at' => now()->addDays(7),
        ]);

        $this->mailer->sendInvitation($invitation);

        $this->auditLogger->logInvitationCreated($invitation, $inviter, $ipAddress, $userAgent);

        return InviteUserResult::success($invitation);
    }
}
