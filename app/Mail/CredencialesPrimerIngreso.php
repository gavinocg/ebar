<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CredencialesPrimerIngreso extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombre,
        public string $correo,
        public string $clave,
        public string $nombreBar,
        public string $url,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Credenciales de acceso a tu bar {$this->nombreBar}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.credenciales');
    }
}