<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $token,
        public readonly string $fullName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Magic Fridge – Verify your e-mail address',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify_email',
        );
    }
}
