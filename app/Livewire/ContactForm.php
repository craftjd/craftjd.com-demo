<?php

namespace App\Livewire;

use Livewire\Component;

class ContactForm extends Component
{
    public string $name = '';

    public string $email = '';

    public string $subject = '';

    public string $message = '';

    public bool $submitted = false;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Tell us who you are.',
            'name.min' => 'Name needs at least a couple characters.',
            'email.required' => 'We need an email to reply — even in a demo.',
            'email.email' => 'That email address does not look right.',
            'message.required' => 'Say something. Even "where are you?" works.',
            'message.min' => 'A bit more detail helps — at least ten characters.',
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $this->submitted = true;
        $this->reset(['name', 'email', 'subject', 'message']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.contact-form');
    }
}
