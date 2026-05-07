<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Invitation $invitation) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'You\'ve been invited to Chrono');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitation',
            with: [
                'acceptUrl' => config('app.frontend_url') . '/accept-invite/' . $this->invitation->token,
                'email' => $this->invitation->email,
            ],
        );
    }
}
