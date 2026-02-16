<?php

namespace Andmarruda\AuthModule\Infrastructure\Persistence;

use Andmarruda\AuthModule\Models\Invitation;
use Andmarruda\AuthModule\Ports\Repositories\InvitationRepositoryInterface;

class EloquentInvitationRepository implements InvitationRepositoryInterface
{
    public function create(array $data): Invitation
    {
        return Invitation::create($data);
    }

    public function findByToken(string $token): ?Invitation
    {
        return Invitation::where('token', $token)->first();
    }

    public function markAsAccepted(Invitation $invitation): void
    {
        $invitation->update(['accepted_at' => now()]);
    }
}
