@props([
    'title' => null,
    'metaDescription' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $metaDescription ?? config('client.site_description', '') }}">
    <meta name="robots" content="{{ config('client.seo.robots', 'index, follow') }}">
    <title>{{ $title ?? config('client.site_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    @if ($gaId = config('client.analytics.google_analytics_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ $gaId }}');
        </script>
    @endif
</head>

<body class="flex min-h-full flex-col font-sans antialiased bg-white text-gray-900">
    <x-nav />
    <main class="flex-1">
        {{ $slot }}
    </main>
    <x-footer />
    @livewireScripts
</body>

</html>
