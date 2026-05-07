<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $senderName,
        public string $senderEmail,
        public string $body,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address(config('client.contact_email'))],
            replyTo: [new Address($this->senderEmail, $this->senderName)],
            subject: 'Contact form — '.config('client.site_name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.contact-form-submitted',
        );
    }
}
