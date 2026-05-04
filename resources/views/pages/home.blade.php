<x-layouts.base title="{{ config('client.site_name') }}" :meta-description="config('client.site_description')">
    <x-page-header
        :title="config('client.site_name')"
        :description="config('client.site_description')"
    />

    <section class="section">
        <div class="container max-w-3xl text-center">
            <p class="text-gray-600">
                This page is <code class="rounded bg-gray-100 px-2 py-1 text-sm">resources/views/pages/home.blade.php</code>.
                Hand-build pages here with Tailwind — one file, full control.
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-4">
                <a href="{{ url('/services') }}" class="btn btn-primary">Services</a>
                <a href="{{ url('/about') }}" class="btn btn-secondary">About</a>
            </div>
        </div>
    </section>
</x-layouts.base>
