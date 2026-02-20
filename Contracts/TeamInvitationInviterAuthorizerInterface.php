<?php

namespace Andmarruda\AuthModule\Contracts;

use Andmarruda\AuthModule\Models\Team;
use Andmarruda\AuthModule\Models\User;
use Illuminate\Database\Eloquent\Model;

interface TeamInvitationInviterAuthorizerInterface
{
    public function authorize(User $actor, Model $inviter, Team $team): bool;
}
