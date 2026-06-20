@inject('content', App\Services\SiteContent::class)
@inject('deployMeta', App\Services\DeployMeta::class)

@php
    $hours = $content->list('hours', 'schedule');
    $footer = $content->get('footer');
    $philosophy = $footer['philosophy'] ?? null;
    $changelog = $deployMeta->changelog();
@endphp

<footer class="footer-shell">
    <div class="footer-gradient pointer-events-none absolute inset-0" aria-hidden="true"></div>

    <div class="site-wrap relative py-16 sm:py-20">
        <div class="grid gap-12 lg:grid-cols-12 lg:gap-12">
            <div class="lg:col-span-5">
                <div class="flex items-center gap-3">
                    <x-joes.logo-mark class="h-12 w-12 text-clay-500" />
                    <p class="font-display text-2xl font-bold text-cream">{{ config('client.site_name') }}</p>
                </div>
                @if ($footerText = config('client.theme.footer_text'))
                    <p class="mt-4 text-base leading-relaxed text-pine-100/70">{{ $footerText }}</p>
                @endif
                @if ($philosophy)
                    <div class="mt-8 border-t border-pine-600/30 pt-8">
                        <p class="footer-heading">Philosophy</p>
                        <p class="footer-lead">{{ $philosophy }}</p>
                    </div>
                @endif
                @if ($hours)
                    <div class="lg:col-span-3">
                        <p class="footer-heading">Hours</p>
                        <ul class="mt-5 space-y-3 text-base">
                            @foreach ($hours as $row)
                                <li class="flex justify-between gap-4 border-b border-pine-600/25 pb-3 last:border-0 last:pb-0">
                                    <span class="text-pine-100/80">{{ $row['days'] }}</span>
                                    <span class="font-semibold text-cream">{{ $row['hours'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            @if ($changelog !== [])
                <div class="lg:col-span-6">
                    <p class="footer-heading">Request changelog</p>
                    <ol class="mt-6 space-y-5">
                        @foreach ($changelog as $entry)
                            <li class="footer-changelog-item">
                                @if ($entry['datetime'])
                                    <time datetime="{{ $entry['datetime'] }}" class="text-xs font-semibold uppercase tracking-wide text-fog/60">{{ $entry['date'] }}</time>
                                @endif
                                <p class="mt-1 text-sm leading-relaxed text-fog/90">{{ $entry['summary'] }}</p>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>
    </div>

    <div class="relative border-t border-stone-700/30">
        <div class="site-wrap py-8 text-center text-sm text-pine-100/50">
            <p>&copy; {{ date('Y') }} {{ config('client.site_name') }}. Drinkable Coffee.</p>
        </div>
    </div>
</footer>
