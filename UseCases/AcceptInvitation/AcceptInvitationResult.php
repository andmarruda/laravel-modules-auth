<?php

namespace App\Modules\AuthModule\UseCases\AcceptInvitation;

use App\Modules\AuthModule\Models\Invitation;

class AcceptInvitationResult
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

    public static function notFound(): self
    {
        return new self(false, 'not_found', null);
    }

    public static function alreadyUsed(): self
    {
        return new self(false, 'already_used', null);
    }

    public static function expired(): self
    {
        return new self(false, 'expired', null);
    }
}
