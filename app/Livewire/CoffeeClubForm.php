<?php

namespace App\Livewire;

use Livewire\Component;

class CoffeeClubForm extends Component
{
    public string $name = '';

    public string $email = '';

    public string $delivery = 'pickup';

    public string $grind = 'whole';

    public bool $submitted = false;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'delivery' => ['required', 'in:pickup,ship'],
            'grind' => ['required', 'in:whole,ground'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Name for the membership — real or fake, we are not checking.',
            'email.required' => 'Email is how clubs usually work.',
            'email.email' => 'That does not look like a valid email.',
            'delivery.in' => 'Pickup or ship — pick one.',
            'grind.in' => 'Whole bean or ground — pick one.',
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $this->submitted = true;
        $this->reset(['name', 'email', 'delivery', 'grind']);
        $this->delivery = 'pickup';
        $this->grind = 'whole';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.coffee-club-form');
    }
}
