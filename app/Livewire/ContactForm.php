<?php

namespace App\Livewire;

use App\Mail\ContactFormSubmitted;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ContactForm extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('required|string|max:5000')]
    public string $message = '';

    public string $website = '';

    public bool $submitted = false;

    public function submit(): void
    {
        if ($this->website !== '') {
            $this->submitted = true;

            return;
        }

        if (! config('client.contact_email')) {
            $this->addError('email', 'Contact form is not configured. Set CONTACT_EMAIL in your environment.');

            return;
        }

        $this->validate();

        Mail::send(new ContactFormSubmitted(
            senderName: $this->name,
            senderEmail: $this->email,
            body: $this->message,
        ));

        $this->reset(['name', 'email', 'message', 'website']);
        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.contact-form');
    }
}
