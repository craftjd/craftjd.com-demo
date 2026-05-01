<header class="border-b border-gray-200">
    <nav class="container flex items-center justify-between py-4">
        <a href="{{ url('/') }}" class="text-lg font-semibold text-gray-900 hover:text-gray-700">
            {{ config('client.site_name') }}
        </a>

        @if ($links = config('client.navigation', []))
            <ul class="flex items-center gap-6">
                @foreach ($links as $link)
                    <li>
                        <a
                            href="{{ url($link['url']) }}"
                            class="text-sm font-medium text-gray-600 hover:text-gray-900 {{ request()->is(ltrim($link['url'], '/')) || ($link['url'] === '/' && request()->is('/')) ? 'text-gray-900' : '' }}"
                        >
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </nav>
</header>
