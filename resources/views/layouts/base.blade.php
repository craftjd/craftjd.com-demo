<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="{{ $metaDescription ?? config('client.site_description', '') }}">
  <title>{{ $title ?? config('client.site_name') }}</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @livewireStyles
</head>

<body class="font-sans antialiased">
  <x-starter-nav />
  <main>
    {{ $slot }}
  </main>
  <x-starter-footer />
  @livewireScripts
</body>

</html>
