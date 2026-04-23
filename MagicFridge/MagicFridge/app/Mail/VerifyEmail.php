<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $name;

    /**
     * A verifikációs levélhez eltesszük a tokent és a nevet,
     * mert a Blade email sablon ezekből építi fel a személyes linket.
     */
    public function __construct($token, $name)
    {
        $this->token = $token;
        $this->name = $name;
    }

    /**
     * Az envelope a levél külső adatait tartalmazza, például a tárgyat.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify Your Email Address - MagicFridge',
        );
    }

    /**
     * A content mondja meg, melyik email view renderelődjön és milyen adatokkal.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.verify',
            with: [
                'token' => $this->token,
                'name' => $this->name,
            ],
        );
    }

    /**
     * Ehhez a verifikációs levélhez nincs csatolmány.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
