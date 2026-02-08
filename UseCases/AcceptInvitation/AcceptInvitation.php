<?php

namespace App\Modules\AuthModule\UseCases\AcceptInvitation;

use App\Modules\AuthModule\Ports\Repositories\InvitationRepositoryInterface;
use App\Modules\AuthModule\Ports\Services\AuditLoggerInterface;

class AcceptInvitation
{
    public function __construct(
        private InvitationRepositoryInterface $invitations,
        private AuditLoggerInterface $auditLogger,
    ) {}

    public function execute(string $token, ?string $ipAddress = null, ?string $userAgent = null): AcceptInvitationResult
    {
        $invitation = $this->invitations->findByToken($token);

        if (!$invitation) {
            return AcceptInvitationResult::notFound();
        }

        // Idempotent: if already accepted, return success
        if ($invitation->isAccepted()) {
            return AcceptInvitationResult::success($invitation);
        }

        if ($invitation->isExpired()) {
            return AcceptInvitationResult::expired();
        }

        $this->invitations->markAsAccepted($invitation);

        $this->auditLogger->logInvitationAccepted($invitation, $ipAddress, $userAgent);

        return AcceptInvitationResult::success($invitation);
    }
}
