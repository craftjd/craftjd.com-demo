@inject('content', App\Services\SiteContent::class)

@php
    $hours = $content->list('hours', 'schedule');
    $footer = $content->get('footer');
    $philosophy = $footer['philosophy'] ?? null;
    $navigation = config('client.navigation', []);
    $siteName = config('client.site_name');
    $tagline = config('client.theme.footer_text');
    $contact = $content->get('contact');
@endphp

<footer class="bg-stone-950 text-stone-400">
    <div class="site-wrap py-16 sm:py-20 lg:py-24">
        <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">
            {{-- Brand — deliberate asymmetry, generous vertical room --}}
            <div class="lg:col-span-6">
                <div class="flex items-center gap-3">
                    <x-joes.logo-mark class="h-10 w-10 shrink-0 text-clay-500" />
                    <div>
                        <p class="font-display text-2xl font-bold tracking-tight text-cream">{{ $siteName }}</p>
                        @if ($tagline)
                            <p class="-mt-0.5 text-sm text-stone-500">{{ $tagline }}</p>
                        @endif
                    </div>
                </div>

                @if ($philosophy)
                    <p class="mt-8 max-w-prose text-sm leading-relaxed text-stone-400/70 italic">
                        {{ $philosophy }}
                    </p>
                @endif
            </div>

            {{-- Navigation + Hours — compact right column --}}
            <div class="lg:col-span-5 lg:col-start-8">
                @if ($navigation)
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-600">Navigate</p>
                    <ul class="mt-4 flex flex-wrap gap-x-5 gap-y-2">
                        @foreach ($navigation as $link)
                            <li>
                                <a href="{{ $link['url'] }}" class="text-sm font-medium text-stone-400 transition hover:text-clay-500">
                                    {{ $link['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($contact && ($contact['phone'] ?? null))
                    <p class="mt-10 text-xs font-semibold uppercase tracking-[0.2em] text-stone-600">Call</p>
                    <a href="tel:{{ preg_replace('/\D/', '', $contact['phone']) }}" class="mt-2 block text-sm font-medium text-stone-400 transition hover:text-clay-500">
                        {{ $contact['phone'] }}
                    </a>
                @endif

                @if ($hours)
                    <div class="mt-10">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-600">Hours</p>
                        <div class="mt-4 space-y-1.5">
                            @foreach ($hours as $row)
                                <div class="flex gap-3 text-sm">
                                    <span class="w-16 shrink-0 font-medium text-stone-500">{{ $row['days'] }}</span>
                                    <span class="text-stone-400">{{ $row['hours'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Colophon --}}
    <div class="border-t border-stone-800/60">
        <div class="site-wrap flex flex-wrap items-baseline justify-between gap-2 py-6 text-xs text-stone-600">
            <p>&copy; {{ date('Y') }} {{ $siteName }}. <span class="text-stone-500/50">{{ config('client.site_description') }}</span></p>
            <span class="h-1 w-1 shrink-0 rounded-full bg-clay-600/40" aria-hidden="true"></span>
        </div>
    </div>
</footer>
