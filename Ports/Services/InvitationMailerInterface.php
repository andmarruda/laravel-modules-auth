<?php

namespace Andmarruda\AuthModule\Ports\Services;

use Andmarruda\AuthModule\Models\Invitation;

interface InvitationMailerInterface
{
    public function sendInvitation(Invitation $invitation): void;
}
