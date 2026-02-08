<?php

namespace App\Modules\AuthModule\Infrastructure\Persistence;

use App\Modules\AuthModule\Models\Invitation;
use App\Modules\AuthModule\Ports\Repositories\InvitationRepositoryInterface;

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
