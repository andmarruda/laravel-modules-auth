<?php

namespace Andmarruda\AuthModule\Infrastructure\Services;

use Andmarruda\AuthModule\Models\AuthAuditLog;
use Andmarruda\AuthModule\Models\Invitation;
use Andmarruda\AuthModule\Models\User;
use Andmarruda\AuthModule\Ports\Services\AuditLoggerInterface;

class EloquentAuditLogger implements AuditLoggerInterface
{
    public function logInvitationCreated(Invitation $invitation, User $inviter, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        AuthAuditLog::create([
            'action' => 'invitation_created',
            'actor_id' => $inviter->id,
            'actor_email' => $inviter->email,
            'target_email' => $invitation->email,
            'invitation_id' => $invitation->id,
            'resource_scope' => $invitation->resource_scope,
            'metadata' => [
                'expires_at' => $invitation->expires_at->toIso8601String(),
            ],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }

    public function logInvitationAccepted(Invitation $invitation, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        AuthAuditLog::create([
            'action' => 'invitation_accepted',
            'actor_id' => null,
            'actor_email' => $invitation->email,
            'target_email' => $invitation->email,
            'invitation_id' => $invitation->id,
            'resource_scope' => $invitation->resource_scope,
            'metadata' => [
                'accepted_at' => $invitation->accepted_at?->toIso8601String(),
            ],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }

    public function logUserRegistered(User $user, Invitation $invitation, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        AuthAuditLog::create([
            'action' => 'user_registered',
            'actor_id' => $user->id,
            'actor_email' => $user->email,
            'target_email' => $user->email,
            'invitation_id' => $invitation->id,
            'resource_scope' => $invitation->resource_scope,
            'metadata' => [
                'user_id' => $user->id,
                'user_name' => $user->name,
            ],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }

    public function logProfileUpdated(User $user, array $changedFields, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        AuthAuditLog::create([
            'action' => 'profile_updated',
            'actor_id' => $user->id,
            'actor_email' => $user->email,
            'target_email' => $user->email,
            'invitation_id' => null,
            'resource_scope' => null,
            'metadata' => [
                'changed_fields' => $changedFields,
            ],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }

    public function logPasswordResetRequested(User $user, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        AuthAuditLog::create([
            'action' => 'password_reset_requested',
            'actor_id' => $user->id,
            'actor_email' => $user->email,
            'target_email' => $user->email,
            'invitation_id' => null,
            'resource_scope' => null,
            'metadata' => [],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }

    public function logPasswordChanged(User $user, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        AuthAuditLog::create([
            'action' => 'password_changed',
            'actor_id' => $user->id,
            'actor_email' => $user->email,
            'target_email' => $user->email,
            'invitation_id' => null,
            'resource_scope' => null,
            'metadata' => [],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }

    public function logAccountDeleted(User $user, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        AuthAuditLog::create([
            'action' => 'account_deleted',
            'actor_id' => $user->id,
            'actor_email' => $user->email,
            'target_email' => $user->email,
            'invitation_id' => null,
            'resource_scope' => null,
            'metadata' => [
                'user_id' => $user->id,
                'user_name' => $user->name,
            ],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }
}
