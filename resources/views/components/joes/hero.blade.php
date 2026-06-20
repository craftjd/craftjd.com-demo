@props(['hero'])

<section class="hero-shell relative overflow-hidden text-cream">
    @if (! empty($hero['image']['src']))
        <div class="hero-ambient pointer-events-none absolute inset-0" aria-hidden="true">
            <img
                src="{{ $hero['image']['src'] }}"
                alt=""
                class="hero-ambient__img"
                width="1600"
                height="900"
                loading="eager"
                decoding="async"
            >
        </div>
    @endif

    <div class="hero-gradient pointer-events-none absolute inset-0" aria-hidden="true"></div>
    <div class="hero-vignette pointer-events-none absolute inset-0" aria-hidden="true"></div>

    <div class="hero-grid site-wrap relative z-10 flex min-h-[75vh] flex-col justify-center py-16 sm:py-20 lg:min-h-[78vh] lg:py-24">
        <div class="grid items-center gap-10 lg:grid-cols-12 lg:gap-8">
            <div class="lg:col-span-5">
                <h1 class="mt-6 font-display text-5xl font-extrabold leading-[0.95] tracking-tight text-cream sm:text-6xl lg:text-7xl">
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

            @if (! empty($hero['image']['src']))
                <figure class="hero-photo relative lg:col-span-7 lg:col-start-6 lg:-mr-6 xl:-mr-16">
                    <div class="hero-photo-frame overflow-hidden rounded-sm">
                        <img
                            src="{{ $hero['image']['src'] }}"
                            alt="{{ $hero['image']['alt'] ?? '' }}"
                            class="hero-photo-img aspect-[16/10] w-full object-cover sm:aspect-[5/3] lg:aspect-[16/10] lg:min-h-[20rem]"
                            width="900"
                            height="506"
                            loading="eager"
                            decoding="async"
                        >
                        <div class="hero-photo-overlay pointer-events-none absolute inset-0" aria-hidden="true"></div>
                    </div>
                    <figcaption class="mt-3 text-xs text-pine-100/45">
                        Photo for atmosphere. Actual mug may vary.
                    </figcaption>
                </figure>
            @endif
        </div>

        <a href="#gallery" class="hero-scroll mt-10 inline-flex items-center gap-2 self-start text-xs font-semibold uppercase tracking-[0.2em] text-pine-100/50 transition hover:text-clay-500 lg:mt-12">
            <span>Scroll</span>
            <svg class="h-4 w-4 animate-bounce" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" d="M12 5v14M6 13l6 6 6-6"/>
            </svg>
        </a>
    </div>
</section>
