<?php

namespace App\Livewire;

use Livewire\Component;

class TakeoutOrderForm extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $pickup_time = '';

    public string $order_details = '';

    public bool $submitted = false;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:7', 'max:30'],
            'pickup_time' => ['required', 'string', 'max:80'],
            'order_details' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Who should we call out when the order is ready?',
            'email.required' => 'Email is required for order confirmation.',
            'email.email' => 'Double-check that email address.',
            'phone.required' => 'A phone number helps if we run out of something.',
            'pickup_time.required' => 'When are you picking up?',
            'order_details.required' => 'List what you want — drip, latte, two buns, etc.',
            'order_details.min' => 'Give us at least a rough order to work with.',
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $this->submitted = true;
        $this->reset(['name', 'email', 'phone', 'pickup_time', 'order_details']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.takeout-order-form');
    }
}
