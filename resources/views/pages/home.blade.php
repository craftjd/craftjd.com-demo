@extends('layouts.base')

@section('title', config('client.site_name').' — '.config('client.site_description'))
@section('meta_description', config('client.site_description'))

@section('content')
    <x-joes.hero :hero="$content->get('hero')">
        @php $specials = $content->get('specials'); @endphp
        @if ($specials)
            <div id="specials" class="mt-16 sm:mt-20 max-w-4xl">
                <p class="font-display text-xs font-bold uppercase tracking-[0.2em] text-clay-400">Specials</p>
                @if (! empty($specials['intro']))
                    <p class="mt-2 max-w-prose text-base leading-relaxed text-pine-100/70">{{ $specials['intro'] }}</p>
                @endif
                <div class="mt-8 grid gap-6 sm:grid-cols-2 sm:gap-x-10 lg:gap-x-14">
                    @foreach ($content->categories('specials') as $category)
                        <div>
                            @if (! empty($category['name']))
                                <h3 class="mb-4 font-display text-xs font-bold uppercase tracking-[0.18em] text-clay-500">{{ $category['name'] }}</h3>
                            @endif
                            <div class="space-y-0">
                                @foreach ($category['items'] ?? [] as $item)
                                    <div class="flex items-baseline justify-between gap-4 py-1.5">
                                        <div class="min-w-0">
                                            <div class="flex items-baseline gap-2">
                                                <span class="text-base font-semibold text-cream sm:text-lg">{{ $item['name'] }}</span>
                                                <span class="min-w-[0.75rem] flex-1 border-b border-dotted border-cream/20"></span>
                                            </div>
                                            @if (! empty($item['note']))
                                                <p class="mt-0.5 max-w-md text-sm leading-snug text-pine-100/60">{{ $item['note'] }}</p>
                                            @endif
                                        </div>
                                        @if (! empty($item['price']))
                                            <span class="shrink-0 font-display text-lg font-bold tabular-nums text-cream/80">${{ $item['price'] }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </x-joes.hero>

    <x-joes.photo-gallery :gallery="$content->get('gallery')" />

    <div class="menu-chapter">
        {{-- Coffee --}}
        <section id="coffee" class="section-block section-block--menu">
            <div class="site-wrap">
                <nav class="menu-jump" aria-label="Menu sections">
                    <a href="#coffee" class="menu-jump__link">Coffee</a>
                    <a href="#food" class="menu-jump__link">Food</a>
                </nav>
                <x-joes.section-heading
                    number="01"
                    title="Coffee"
                    :lead="$content->string('coffee-menu', 'intro')"
                    watermark
                />
                <div class="mt-6">
                    <x-joes.menu-list :categories="$content->categories('coffee-menu')" />
                </div>

                <div id="roasters" class="mt-12 border-t border-fog pt-10 scroll-mt-24">
                    <p class="section-label">On the bar now</p>
                    <x-joes.roaster-grid :roasters="$content->get('roasters')" class="mt-4" />
                </div>
            </div>
        </section>

        {{-- Food --}}
        <section id="food" class="section-band section-block--menu relative">
            <div class="site-wrap relative lg:min-h-[22rem]">
                <div class="lg:max-w-[58%]">
                    <x-joes.section-heading
                        number="02"
                        title="Food"
                        :lead="$content->string('food-menu', 'intro')"
                    />
                    <div class="mt-6">
                        <x-joes.menu-list :categories="$content->categories('food-menu')" />
                    </div>
                </div>

                @php $foodImage = $content->get('food-menu')['image'] ?? null; @endphp
                @if ($foodImage)
                    <figure class="breakout-panel breakout-panel--food surface mt-8 overflow-hidden lg:mt-0">
                        <img
                            src="{{ $foodImage['src'] }}"
                            alt="{{ $foodImage['alt'] ?? '' }}"
                            class="aspect-[4/5] w-full object-cover lg:aspect-[3/4]"
                            width="304"
                            height="405"
                            loading="lazy"
                            decoding="async"
                        >
                    </figure>
                @endif
            </div>
        </section>
    </div>

    {{-- Takeout --}}
    <section id="takeout" class="section-block">
        <div class="site-wrap">
            <div class="grid gap-10 lg:grid-cols-2 lg:items-start">
                <div class="lg:pt-2">
                    <p class="section-label">04 — Takeout</p>
                    <h2 class="section-title">Order ahead. Pick up in person.</h2>
                    <p class="section-lead">{{ $content->string('takeout-options', 'intro') }}</p>
                    <ul class="takeout-list mt-6">
                        @foreach ($content->list('takeout-options', 'options') as $option)
                            <li class="takeout-list__item">
                                <h3 class="takeout-list__title">{{ $option['title'] }}</h3>
                                <p class="takeout-list__text">{{ $option['description'] }}</p>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="breakout-panel breakout-panel--takeout form-panel-dark">
                    <h3 class="font-display text-xl font-bold text-cream">Place a takeout order</h3>
                    <p class="mt-2 text-sm text-pine-100/75">Fill it out. We'll pretend to read it.</p>
                    <livewire:takeout-order-form />
                </div>
            </div>
        </div>
    </section>

    {{-- Hours + responsibility --}}
    <section id="hours" class="section-band">
        <div class="site-wrap grid gap-10 lg:grid-cols-12 lg:gap-14">
            <div class="lg:col-span-5">
                <x-joes.section-heading
                    number="05"
                    title="Hours"
                    :lead="$content->string('hours', 'intro')"
                />
                <dl class="hours-band mt-6">
                    @foreach ($content->list('hours', 'schedule') as $row)
                        <div class="hours-band__row">
                            <dt class="hours-band__days">{{ $row['days'] }}</dt>
                            <dd class="hours-band__time">{{ $row['hours'] }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if ($hoursNote = $content->string('hours', 'note'))
                    <p class="mt-4 text-sm leading-relaxed text-stone-500">{{ $hoursNote }}</p>
                @endif
            </div>

            <div class="lg:col-span-7 lg:border-l lg:border-fog/80 lg:pl-12 xl:pl-16">
                <x-joes.responsibility-note :data="$content->get('responsibility')" embedded />
            </div>
        </div>
    </section>

    {{-- Team --}}
    <section id="team" class="section-block">
        <div class="site-wrap">
            <x-joes.section-heading
                number="07"
                title="The crew"
                :lead="$content->string('staff', 'intro')"
            />
            <x-joes.staff-list :members="$content->list('staff', 'members')" class="mt-8" />
        </div>
    </section>

    {{-- Reviews --}}
    <section id="reviews" class="section-block section-block--reviews">
        <div class="site-wrap">
            <x-joes.section-heading
                number="08"
                title="Reviews"
                :lead="$content->string('reviews', 'intro')"
            />
            <x-joes.reviews-board :reviews="$content->get('reviews')" class="mt-6" />
        </div>
    </section>

    {{-- Contact --}}
    <section id="contact" class="section-block">
        <div class="site-wrap">
            <x-joes.section-heading
                number="09"
                title="Contact"
                :lead="$content->string('contact', 'intro')"
            />

            @php
                $contact = $content->get('contact');
                $location = $content->get('location');
                $street = $contact['street'] ?? '';
                $cityLine = trim(($contact['city'] ?? '').', '.($contact['region'] ?? '').' '.($contact['postal'] ?? ''));
                $phone = $contact['phone'] ?? '';
                $email = $contact['email'] ?? '';
            @endphp

            <div class="contact-bar mt-6">
                <address class="contact-bar__inner not-italic">
                    <span class="contact-bar__item">{{ $street }}@if ($street && $cityLine), @endif{{ $cityLine }}</span>
                    @if ($phone)
                        <span class="contact-bar__sep" aria-hidden="true">·</span>
                        <a href="tel:{{ preg_replace('/\D/', '', $phone) }}" class="contact-bar__item link-pine">{{ $phone }}</a>
                    @endif
                    @if ($email)
                        <span class="contact-bar__sep" aria-hidden="true">·</span>
                        <a href="mailto:{{ $email }}" class="contact-bar__item link-pine">{{ $email }}</a>
                    @endif
                </address>
            </div>

            <div class="mt-8">
                <x-joes.location-map :location="$location" :contact="$contact" />
            </div>
        </div>
    </section>

    {{-- Coffee club --}}
    <section id="club" class="section-block club-shell">
        <div class="club-gradient pointer-events-none absolute inset-0" aria-hidden="true"></div>
        <div class="site-wrap relative grid gap-10 lg:grid-cols-2 lg:items-center">
            <div>
                <p class="section-label text-clay-500">10 — Club</p>
                <h2 class="section-title text-cream">{{ $content->string('coffee-club', 'headline') }}</h2>
                <p class="section-lead text-pine-100/75">{{ $content->string('coffee-club', 'intro') }}</p>

                <ul class="mt-6 space-y-2.5 text-sm text-pine-100/85">
                    @foreach ($content->list('coffee-club', 'perks') as $perk)
                        <li class="flex gap-3">
                            <span class="text-clay-500" aria-hidden="true">✓</span>
                            <span>{{ $perk }}</span>
                        </li>
                    @endforeach
                </ul>

                <p class="mt-7 font-display text-3xl font-bold text-cream">
                    ${{ $content->string('coffee-club', 'price') }}<span class="text-lg font-normal text-pine-100/60">/mo</span>
                </p>
                <p class="mt-1 text-sm text-pine-100/55">{{ $content->string('coffee-club', 'price_note') }}</p>
            </div>

            <div class="club-form-panel form-panel-dark">
                <h3 class="font-display text-xl font-bold text-cream">Sign up</h3>
                <livewire:coffee-club-form />
            </div>
        </div>
    </section>
@endsection
