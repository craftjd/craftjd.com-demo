@props(['name'])

@switch($name)
    @case('bean')
        <svg {{ $attributes->merge(['class' => 'responsibility-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path d="M8 20c-3-2-4-7-1-11 2-3 6-4 9-2 1 1 2 3 1 5-1 3-4 6-7 7-1 0-1-1-2-1z"/>
            <path d="M14 18c2-2 3-5 2-8-1-2-3-4-6-4"/>
        </svg>
        @break
    @case('farm')
        <svg {{ $attributes->merge(['class' => 'responsibility-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" d="M4 20V10l8-6 8 6v10"/>
            <path stroke-linecap="round" d="M9 20v-6h6v6"/>
        </svg>
        @break
    @case('label')
        <svg {{ $attributes->merge(['class' => 'responsibility-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <rect x="5" y="4" width="14" height="16" rx="1"/>
            <path stroke-linecap="round" d="M9 9h6M9 13h4"/>
        </svg>
        @break
    @case('cup')
        <svg {{ $attributes->merge(['class' => 'responsibility-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" d="M5 8h12v8a3 3 0 01-3 3H8a3 3 0 01-3-3V8z"/>
            <path stroke-linecap="round" d="M17 10h2a2 2 0 010 4h-2"/>
            <path stroke-linecap="round" d="M6 5c0-1 1-2 2-2"/>
        </svg>
        @break
    @case('people')
        <svg {{ $attributes->merge(['class' => 'responsibility-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <circle cx="9" cy="8" r="3"/>
            <path stroke-linecap="round" d="M3 20c0-3 2.5-5 6-5s6 2 6 5"/>
            <circle cx="17" cy="9" r="2"/>
            <path stroke-linecap="round" d="M15 20c0-2 1.5-3.5 4-3.5"/>
        </svg>
        @break
    @case('energy')
        <svg {{ $attributes->merge(['class' => 'responsibility-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 2L5 14h6l-1 8 9-14h-6l1-6z"/>
        </svg>
        @break
    @default
        <svg {{ $attributes->merge(['class' => 'responsibility-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <circle cx="12" cy="12" r="8"/>
        </svg>
@endswitch
