@php
    $links = config('client.navigation', []);

    $href = function (string $url): string {
        if (str_starts_with($url, '#')) {
            return url('/').$url;
        }

        return url($url);
    };
@endphp

<header class="sticky top-0 z-50 border-b border-fog/80 bg-cream/95 backdrop-blur-sm" x-data="{ open: false }" @keydown.escape.window="open = false">
    <nav class="site-wrap flex items-center justify-between gap-4 py-4" aria-label="Main">
        <a href="{{ url('/') }}" class="group flex min-w-0 items-center gap-3 shrink-0">
            <x-joes.logo-mark class="transition group-hover:text-clay-600" />
            <span class="min-w-0 leading-tight">
                <span class="block truncate font-display text-lg font-bold text-ink">{{ config('client.site_name') }}</span>
                <span class="block truncate text-xs font-medium tracking-wide text-stone-500">{{ config('client.site_description') }}</span>
            </span>
        </a>

        @if ($links)
            <button
                type="button"
                class="inline-flex items-center justify-center rounded-sm p-2 text-stone-700 hover:bg-fog md:hidden"
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

            <ul id="site-nav" class="hidden items-center gap-1 md:flex">
                @foreach ($links as $link)
                    <li>
                        <a href="{{ $href($link['url']) }}" class="rounded-sm px-3 py-2 text-sm font-semibold text-stone-700 transition hover:bg-fog hover:text-ink">
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach

                {{-- Careers — distinct treatment, set apart from main nav --}}
                <li class="ml-3 pl-3 border-l border-fog/60">
                    <a href="{{ url('/careers') }}" class="rounded-sm px-2.5 py-1.5 text-xs font-semibold uppercase tracking-wider text-clay-600 transition hover:text-clay-700">
                        We{{ "'" }}re hiring
                    </a>
                </li>
            </ul>
        @endif
    </nav>

    @if ($links)
        <div id="site-nav-mobile" class="border-t border-fog md:hidden" x-show="open" x-cloak @click.outside="open = false">
            <ul class="site-wrap flex flex-col gap-1 py-3">
                @foreach ($links as $link)
                    <li>
                        <a href="{{ $href($link['url']) }}" class="block rounded-sm px-3 py-2 text-sm font-semibold text-stone-700 hover:bg-fog" @click="open = false">
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach
                <li class="border-t border-fog/60 pt-2 mt-1">
                    <a href="{{ url('/careers') }}" class="block rounded-sm px-3 py-2 text-xs font-semibold uppercase tracking-wider text-clay-600 hover:bg-fog" @click="open = false">
                        We{{ "'" }}re hiring
                    </a>
                </li>
            </ul>
        </div>
    @endif
</header>
