<x-layouts.base>
    <section class="section">
        <div class="container max-w-3xl text-center">
            <h1 class="text-4xl font-bold mb-4">{{ config('client.site_name') }}</h1>
            @if (config('client.site_description'))
                <p class="text-lg text-gray-600 mb-8">{{ config('client.site_description') }}</p>
            @endif
            <p class="text-gray-500">
                Override <code class="text-sm bg-gray-100 px-2 py-1 rounded">resources/views/client/home.blade.php</code>
                or add <code class="text-sm bg-gray-100 px-2 py-1 rounded">resources/content/home.md</code>.
            </p>
        </div>
    </section>
</x-layouts.base>
