<?php

namespace App\Modules\AuthModule\Ports\Repositories;

use App\Modules\AuthModule\Models\Invitation;

interface InvitationRepositoryInterface
{
    /**
     * Create a new invitation.
     * 
     * @param array $data
     * @return Invitation
     */
    public function create(array $data): Invitation;

    /**
     * Find an invitation by its token.
     * 
     * @param string $token
     * @return Invitation|null
     */
    public function findByToken(string $token): ?Invitation;

    /**
     * Mark an invitation as accepted.
     * 
     * @param Invitation $invitation
     * @return void
     */
    public function markAsAccepted(Invitation $invitation): void;
}
