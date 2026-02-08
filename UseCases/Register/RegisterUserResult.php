<?php

namespace App\Modules\AuthModule\UseCases\Register;

use App\Modules\AuthModule\Models\User;

class RegisterUserResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?string $error,
        public readonly ?User $user,
    ) {}

    public static function success(User $user): self
    {
        return new self(true, null, $user);
    }

    public static function invitationNotFound(): self
    {
        return new self(false, 'invitation_not_found', null);
    }

    public static function invitationAlreadyUsed(): self
    {
        return new self(false, 'invitation_already_used', null);
    }

    public static function invitationExpired(): self
    {
        return new self(false, 'invitation_expired', null);
    }
}
