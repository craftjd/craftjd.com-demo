@php
    $links = config('client.navigation', []);

    $isActive = function (string $url): bool {
        if ($url === '/') {
            return request()->is('/');
        }

        return request()->is(ltrim($url, '/'));
    };
@endphp

<header class="border-b border-gray-200 bg-white" x-data="{ open: false }" @keydown.escape.window="open = false">
    <nav class="container flex items-center justify-between gap-4 py-4" aria-label="Main">
        <a href="{{ url('/') }}" class="text-lg font-semibold text-gray-900 hover:text-gray-700 shrink-0">
            @if ($logo = config('client.theme.logo'))
                <img src="{{ $logo }}" alt="{{ config('client.theme.logo_alt', config('client.site_name')) }}" class="h-8 w-auto">
            @else
                {{ config('client.site_name') }}
            @endif
        </a>

        @if ($links)
            <button
                type="button"
                class="inline-flex items-center justify-center rounded-md p-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 md:hidden"
                :aria-expanded="open"
                aria-controls="site-nav"
                @click="open = !open"
            >
                <span class="sr-only">Toggle menu</span>
                <svg x-show="!open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                </svg>
                <svg x-show="open" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>

            <ul id="site-nav" class="hidden items-center gap-6 md:flex">
                @foreach ($links as $link)
                    <li>
                        <a
                            href="{{ url($link['url']) }}"
                            class="nav-link {{ $isActive($link['url']) ? 'nav-link-active' : '' }}"
                        >
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </nav>

    @if ($links)
        <div
            id="site-nav-mobile"
            class="border-t border-gray-200 md:hidden"
            x-show="open"
            x-cloak
            @click.outside="open = false"
        >
            <ul class="container flex flex-col gap-1 py-3">
                @foreach ($links as $link)
                    <li>
                        <a
                            href="{{ url($link['url']) }}"
                            class="nav-link block rounded-md px-3 py-2 {{ $isActive($link['url']) ? 'nav-link-active' : '' }}"
                            @click="open = false"
                        >
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</header>
