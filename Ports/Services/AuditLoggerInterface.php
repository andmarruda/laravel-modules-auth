<?php

namespace Andmarruda\AuthModule\Ports\Services;

use Andmarruda\AuthModule\Models\Invitation;
use Andmarruda\AuthModule\Models\User;

interface AuditLoggerInterface
{
    public function logInvitationCreated(Invitation $invitation, User $inviter, ?string $ipAddress = null, ?string $userAgent = null): void;

    public function logInvitationAccepted(Invitation $invitation, ?string $ipAddress = null, ?string $userAgent = null): void;

    public function logUserRegistered(User $user, Invitation $invitation, ?string $ipAddress = null, ?string $userAgent = null): void;

    public function logProfileUpdated(User $user, array $changedFields, ?string $ipAddress = null, ?string $userAgent = null): void;

    public function logPasswordResetRequested(User $user, ?string $ipAddress = null, ?string $userAgent = null): void;

    public function logPasswordChanged(User $user, ?string $ipAddress = null, ?string $userAgent = null): void;

    public function logAccountDeleted(User $user, ?string $ipAddress = null, ?string $userAgent = null): void;
}
