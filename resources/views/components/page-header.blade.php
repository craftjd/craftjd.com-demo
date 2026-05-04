@props([
    'title',
    'description' => null,
    'eyebrow' => null,
])

<header {{ $attributes->merge(['class' => 'border-b border-gray-200 bg-gray-50']) }}>
    <div class="container py-12 sm:py-16">
        @if ($eyebrow)
            <p class="text-sm font-semibold uppercase tracking-wider text-blue-600">{{ $eyebrow }}</p>
        @endif

        <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl {{ $eyebrow ? 'mt-2' : '' }}">
            {{ $title }}
        </h1>

        @if ($description)
            <p class="mt-4 max-w-2xl text-lg text-gray-600">{{ $description }}</p>
        @endif
    </div>
</header>
