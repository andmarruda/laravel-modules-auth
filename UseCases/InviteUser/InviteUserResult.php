<?php

namespace App\Modules\AuthModule\UseCases\InviteUser;

use App\Modules\AuthModule\Models\Invitation;

class InviteUserResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?string $error,
        public readonly ?Invitation $invitation,
    ) {}

    public static function success(Invitation $invitation): self
    {
        return new self(true, null, $invitation);
    }

    public static function forbidden(): self
    {
        return new self(false, 'forbidden', null);
    }

    public static function emailAlreadyRegistered(): self
    {
        return new self(false, 'email_already_registered', null);
    }
}
