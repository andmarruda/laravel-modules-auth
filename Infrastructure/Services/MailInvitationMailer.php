<?php

namespace App\Modules\AuthModule\Infrastructure\Services;

use App\Modules\AuthModule\Infrastructure\Mail\InvitationMail;
use App\Modules\AuthModule\Models\Invitation;
use App\Modules\AuthModule\Ports\Services\InvitationMailerInterface;
use Illuminate\Support\Facades\Mail;

class MailInvitationMailer implements InvitationMailerInterface
{
    public function sendInvitation(Invitation $invitation): void
    {
        $acceptUrl = $this->buildAcceptUrl($invitation);

        Mail::to($invitation->email)
            ->queue(new InvitationMail($invitation, $acceptUrl));
    }

    private function buildAcceptUrl(Invitation $invitation): string
    {
        return config('app.frontend_url', config('app.url'))
            . '/invitations/accept?token='
            . $invitation->token;
    }
}
