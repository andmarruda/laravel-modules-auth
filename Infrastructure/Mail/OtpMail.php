<?php

namespace Andmarruda\AuthModule\Infrastructure\Mail;

use Andmarruda\AuthModule\Models\Otp;
use Andmarruda\AuthModule\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly Otp $otp,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Seu codigo de verificacao - VisibilityRank AI',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'authmodule::emails.otp',
            with: [
                'user' => $this->user,
                'code' => $this->otp->code,
                'expiresAt' => $this->otp->expires_at,
            ],
        );
    }
}
