@props([
    'character',
    'name',
])

@php
    $label = 'Illustrated caricature of '.$name.' — fictional staff member';
@endphp

<div {{ $attributes->merge(['class' => 'staff-caricature']) }} role="img" aria-label="{{ $label }}">
    @switch($character)
        @case('joe')
            <svg class="staff-caricature__svg" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <circle cx="36" cy="32" r="20" fill="#1c3a30"/>
                <circle cx="36" cy="32" r="16" fill="#e0c09a"/>
                <path d="M26 36c2 2.5 6 4 10 4s8-1.5 10-4" stroke="#a67c52" stroke-width="1.5" stroke-linecap="round"/>
                <circle cx="30" cy="30" r="1.8" fill="#2f2b26"/>
                <circle cx="42" cy="30" r="1.8" fill="#2f2b26"/>
                <path d="M36 22c-6 0-10 4-10 8" stroke="#2f2b26" stroke-width="1.5" stroke-linecap="round" opacity="0.4"/>
                <circle cx="36" cy="64" r="16" fill="#1c3a30" opacity="0.15"/>
                <rect x="20" y="50" width="32" height="24" rx="8" fill="#2d5a47" opacity="0.25"/>
            </svg>
            @break
        @case('mara')
            <svg class="staff-caricature__svg" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <circle cx="36" cy="30" r="20" fill="#2f241c"/>
                <circle cx="36" cy="30" r="16" fill="#f0c9a8"/>
                <path d="M27 34c2 2.5 5.5 4 9 4s7-1.5 9-4" stroke="#c98560" stroke-width="1.5" stroke-linecap="round"/>
                <circle cx="31" cy="28" r="1.8" fill="#2f2b26"/>
                <circle cx="41" cy="28" r="1.8" fill="#2f2b26"/>
                <path d="M24 22c3-5 8-8 12-8s9 3 12 8" fill="#2f241c"/>
                <circle cx="36" cy="64" r="16" fill="#b85c38" opacity="0.12"/>
                <rect x="21" y="50" width="30" height="24" rx="8" fill="#cf7048" opacity="0.22"/>
            </svg>
            @break
        @case('elliot')
            <svg class="staff-caricature__svg" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <circle cx="36" cy="32" r="20" fill="#3d3830"/>
                <circle cx="36" cy="32" r="16" fill="#dbb896"/>
                <path d="M27 37c2 2.5 5.5 3.5 9 3.5s7-1 9-3.5" stroke="#b8855a" stroke-width="1.5" stroke-linecap="round"/>
                <circle cx="31" cy="31" r="1.8" fill="#2f2b26"/>
                <circle cx="41" cy="31" r="1.8" fill="#2f2b26"/>
                <circle cx="36" cy="64" r="16" fill="#1c3a30" opacity="0.15"/>
                <rect x="20" y="50" width="32" height="24" rx="8" fill="#245041" opacity="0.25"/>
            </svg>
            @break
        @case('sam')
        @default
            <svg class="staff-caricature__svg" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <circle cx="36" cy="31" r="20" fill="#2a2620"/>
                <circle cx="36" cy="31" r="15" fill="#d9a67a"/>
                <path d="M28 36c2 2 5.5 3 8 3s6-1 8-3" stroke="#a6754f" stroke-width="1.5" stroke-linecap="round"/>
                <circle cx="31" cy="30" r="1.8" fill="#2f2b26"/>
                <circle cx="41" cy="30" r="1.8" fill="#2f2b26"/>
                <circle cx="36" cy="64" r="16" fill="#245041" opacity="0.15"/>
                <rect x="20" y="50" width="32" height="24" rx="8" fill="#2d5a47" opacity="0.25"/>
            </svg>
    @endswitch
</div>
