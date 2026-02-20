<?php

namespace Andmarruda\AuthModule\Support;

use Andmarruda\AuthModule\Contracts\TeamInvitationInviterAuthorizerInterface;
use Andmarruda\AuthModule\Models\Team;
use Andmarruda\AuthModule\Models\User;
use Illuminate\Database\Eloquent\Model;

class DefaultTeamInvitationInviterAuthorizer implements TeamInvitationInviterAuthorizerInterface
{
    public function authorize(User $actor, Model $inviter, Team $team): bool
    {
        if (!$inviter instanceof User) {
            return false;
        }

        return (int) $inviter->getKey() === (int) $actor->getKey();
    }
}
