<footer class="border-t border-gray-200 mt-16">
    <div class="container py-8 text-center text-sm text-gray-500">
        @if ($footerText = config('client.theme.footer_text'))
            <p class="mb-2">{{ $footerText }}</p>
        @endif

        <p>&copy; {{ date('Y') }} {{ config('client.site_name') }}. All rights reserved.</p>

        @if ($social = array_filter(config('client.social', [])))
            <ul class="flex items-center justify-center gap-4 mt-4">
                @foreach ($social as $platform => $url)
                    <li>
                        <a href="{{ $url }}" class="hover:text-gray-700 capitalize" target="_blank" rel="noopener noreferrer">
                            {{ $platform }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</footer>
