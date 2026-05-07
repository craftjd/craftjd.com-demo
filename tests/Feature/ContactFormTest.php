<?php

namespace Tests\Feature;

use App\Livewire\ContactForm;
use App\Mail\ContactFormSubmitted;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    public function test_contact_page_renders(): void
    {
        config(['client.contact_email' => 'shop@example.com']);

        $this->get('/contact')
            ->assertOk()
            ->assertSee('Send us a message');
    }

    public function test_contact_form_sends_mail(): void
    {
        Mail::fake();
        config(['client.contact_email' => 'shop@example.com']);

        Livewire::test(ContactForm::class)
            ->set('name', 'Jane Doe')
            ->set('email', 'jane@example.com')
            ->set('message', 'Hello there')
            ->call('submit')
            ->assertSet('submitted', true);

        Mail::assertSent(ContactFormSubmitted::class, function (ContactFormSubmitted $mail) {
            return $mail->senderEmail === 'jane@example.com'
                && $mail->senderName === 'Jane Doe'
                && $mail->body === 'Hello there';
        });
    }

    public function test_honeypot_does_not_send_mail(): void
    {
        Mail::fake();
        config(['client.contact_email' => 'shop@example.com']);

        Livewire::test(ContactForm::class)
            ->set('name', 'Bot')
            ->set('email', 'bot@example.com')
            ->set('message', 'Spam')
            ->set('website', 'http://spam.test')
            ->call('submit')
            ->assertSet('submitted', true);

        Mail::assertNothingSent();
    }
}
