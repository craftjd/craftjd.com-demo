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
            <svg class="staff-caricature__svg" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <ellipse cx="40" cy="74" rx="22" ry="4" fill="#1a2e26" opacity="0.2"/>
                <path d="M18 72c4-18 12-28 22-28s18 10 22 28H18z" fill="#245041"/>
                <path d="M22 72c3-12 10-18 18-18s15 6 18 18H22z" fill="#2d5a47"/>
                <ellipse cx="40" cy="34" rx="20" ry="22" fill="url(#joe-face)"/>
                <ellipse cx="40" cy="38" rx="16" ry="14" fill="#c49a6c"/>
                <path d="M28 42c2 4 6 6 12 6s10-2 12-6" stroke="#9a6b45" stroke-width="2" stroke-linecap="round"/>
                <ellipse cx="32" cy="36" rx="2.5" ry="3" fill="#2f2b26"/>
                <ellipse cx="48" cy="36" rx="2.5" ry="3" fill="#2f2b26"/>
                <path d="M30 28c2-6 8-10 10-10s8 4 10 10" fill="#4a3728"/>
                <path d="M26 48c-2 8 0 14 6 16 2 1 4 1 8 1s6 0 8-1c6-2 8-8 6-16" fill="#5c4030"/>
                <ellipse cx="40" cy="30" rx="18" ry="8" fill="#4a3728" opacity="0.85"/>
                <defs>
                    <radialGradient id="joe-face" cx="0.35" cy="0.35" r="0.75">
                        <stop stop-color="#ddb896"/>
                        <stop offset="1" stop-color="#a67c52"/>
                    </radialGradient>
                </defs>
            </svg>
            @break
        @case('mara')
            <svg class="staff-caricature__svg" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <ellipse cx="40" cy="74" rx="22" ry="4" fill="#1a2e26" opacity="0.2"/>
                <path d="M20 72c3-16 11-26 20-26s17 10 20 26H20z" fill="#b85c38"/>
                <path d="M24 72c2-10 8-16 16-16s14 6 16 16H24z" fill="#cf7048"/>
                <ellipse cx="40" cy="36" rx="19" ry="21" fill="url(#mara-face)"/>
                <ellipse cx="40" cy="40" rx="15" ry="13" fill="#e8b896"/>
                <path d="M30 44c3 3 7 4 10 4s7-1 10-4" stroke="#c98560" stroke-width="2" stroke-linecap="round"/>
                <ellipse cx="33" cy="38" rx="2.5" ry="3" fill="#2f2b26"/>
                <ellipse cx="47" cy="38" rx="2.5" ry="3" fill="#2f2b26"/>
                <ellipse cx="40" cy="18" rx="16" ry="10" fill="#2f241c"/>
                <path d="M24 22c4-8 10-12 16-12s12 4 16 12" fill="#2f241c"/>
                <circle cx="52" cy="20" r="6" fill="#2f241c"/>
                <path d="M34 26h12" stroke="#cf7048" stroke-width="3" stroke-linecap="round"/>
                <defs>
                    <radialGradient id="mara-face" cx="0.4" cy="0.35" r="0.7">
                        <stop stop-color="#f5d0b5"/>
                        <stop offset="1" stop-color="#d4a574"/>
                    </radialGradient>
                </defs>
            </svg>
            @break
        @case('elliot')
            <svg class="staff-caricature__svg" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <ellipse cx="40" cy="74" rx="22" ry="4" fill="#1a2e26" opacity="0.2"/>
                <path d="M19 72c4-17 12-27 21-27s17 10 21 27H19z" fill="#1c3a30"/>
                <ellipse cx="40" cy="35" rx="18" ry="20" fill="url(#elliot-face)"/>
                <ellipse cx="40" cy="39" rx="14" ry="12" fill="#d4a882"/>
                <rect x="26" y="34" width="28" height="10" rx="4" fill="none" stroke="#2f2b26" stroke-width="2"/>
                <line x1="30" y1="39" x2="34" y2="39" stroke="#fff" stroke-width="1.5" opacity="0.5"/>
                <line x1="46" y1="39" x2="50" y2="39" stroke="#fff" stroke-width="1.5" opacity="0.5"/>
                <ellipse cx="33" cy="38" rx="2" ry="2.5" fill="#2f2b26"/>
                <ellipse cx="47" cy="38" rx="2" ry="2.5" fill="#2f2b26"/>
                <path d="M32 46c2 2 5 3 8 3s6-1 8-3" stroke="#b8855a" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M28 24c2-4 8-8 12-8s10 4 12 8" fill="#3d3830"/>
                <path d="M26 20c6-6 14-8 14-8s8 2 14 8" fill="#3d3830"/>
                <defs>
                    <radialGradient id="elliot-face" cx="0.38" cy="0.32" r="0.72">
                        <stop stop-color="#e8c4a8"/>
                        <stop offset="1" stop-color="#b8896a"/>
                    </radialGradient>
                </defs>
            </svg>
            @break
        @case('sam')
        @default
            <svg class="staff-caricature__svg" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <ellipse cx="40" cy="74" rx="22" ry="4" fill="#1a2e26" opacity="0.2"/>
                <path d="M21 72c3-15 10-24 19-24s16 9 19 24H21z" fill="#245041"/>
                <ellipse cx="40" cy="37" rx="17" ry="19" fill="url(#sam-face)"/>
                <ellipse cx="40" cy="41" rx="13" ry="11" fill="#c9956e"/>
                <path d="M29 44c3 4 7 5 11 5s8-1 11-5" stroke="#a6754f" stroke-width="2" stroke-linecap="round"/>
                <ellipse cx="34" cy="39" rx="2.5" ry="3" fill="#2f2b26"/>
                <ellipse cx="46" cy="39" rx="2.5" ry="3" fill="#2f2b26"/>
                <path d="M22 28c8-10 18-12 18-12s-2 8 2 14" fill="#2a2620"/>
                <path d="M58 28c-8-10-18-12-18-12s2 8-2 14" fill="#b85c38"/>
                <ellipse cx="40" cy="26" rx="14" ry="6" fill="#2a2620"/>
                <defs>
                    <radialGradient id="sam-face" cx="0.42" cy="0.34" r="0.68">
                        <stop stop-color="#f0c9a8"/>
                        <stop offset="1" stop-color="#c48962"/>
                    </radialGradient>
                </defs>
            </svg>
    @endswitch
</div>
