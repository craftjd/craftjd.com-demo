@extends('layouts.base')

@section('title', 'Contact')
@section('meta_description', 'Get in touch with '.config('client.site_name').'.')

@section('content')
    <x-page-header
        title="Contact"
        description="Send us a message and we'll get back to you."
    />

    <section class="section">
        <div class="container max-w-xl">
            <div class="grid gap-10 sm:grid-cols-2">
                <div class="text-sm text-gray-600 space-y-4">
                    @if ($email = config('client.contact_email'))
                        <p>
                            <span class="font-medium text-gray-900">Email</span><br>
                            <a href="mailto:{{ $email }}" class="text-blue-600 hover:text-blue-700">{{ $email }}</a>
                        </p>
                    @endif

                    <p>
                        <span class="font-medium text-gray-900">Location</span><br>
                        Add your address or service area here.
                    </p>

                    <p>
                        <span class="font-medium text-gray-900">Hours</span><br>
                        Monday – Friday, 9am – 5pm
                    </p>
                </div>

                <div class="card">
                    <livewire:contact-form />
                </div>
            </div>
        </div>
    </section>
@endsection
