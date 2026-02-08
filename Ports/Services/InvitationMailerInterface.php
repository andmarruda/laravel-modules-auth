<?php

namespace App\Modules\AuthModule\Ports\Services;

use App\Modules\AuthModule\Models\Invitation;

interface InvitationMailerInterface
{
    public function sendInvitation(Invitation $invitation): void;
}
