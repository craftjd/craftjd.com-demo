@props(['hero'])

<section class="hero-shell relative overflow-hidden text-cream">
    <div class="hero-gradient pointer-events-none absolute inset-0" aria-hidden="true"></div>
    <div class="hero-vignette pointer-events-none absolute inset-0" aria-hidden="true"></div>

    <div class="site-wrap relative z-10 py-16 sm:py-20 lg:py-24">
        <div class="max-w-2xl">
            <h1 class="font-display text-5xl font-extrabold leading-[0.95] tracking-tight text-cream sm:text-6xl lg:text-7xl">
                {{ $hero['headline'] ?? config('client.site_description') }}
            </h1>

            @if (! empty($hero['subhead']))
                <p class="mt-6 max-w-lg text-lg leading-relaxed text-pine-100/80">{{ $hero['subhead'] }}</p>
            @endif

            <div class="mt-10 flex flex-wrap gap-4">
                <a href="#coffee" class="btn btn-clay">See the menu</a>
                <a href="#takeout" class="btn btn-secondary border-cream/30 text-cream hover:bg-cream hover:text-ink">Order Ahead</a>
            </div>
        </div>

        {{ $slot }}
    </div>
</section>
