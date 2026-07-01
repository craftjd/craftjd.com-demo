@extends('layouts.base')

@section('title', 'Careers — '.config('client.site_name'))
@section('meta_description', 'Join the team at '.config('client.site_name').'. View open positions for baristas, roasters, bakers, and maintenance.')

@section('content')
    {{-- Hero / header --}}
    <section class="border-b border-fog/80 bg-gradient-to-b from-pine-100/30 to-cream">
        <div class="site-wrap py-16 sm:py-20 lg:py-24">
            <p class="section-label text-clay-600">Careers</p>
            <h1 class="section-title mt-3">Join the crew</h1>
            <p class="section-lead mt-4 max-w-2xl">
                We are an independent coffee shop and roastery committed to quality, consistency, and service. If you share those values, we want to meet you.
            </p>
        </div>
    </section>

    {{-- Open roles --}}
    <section class="section-block">
        <div class="site-wrap">
            <div class="grid gap-10 md:gap-12">
                @php
                    $roles = [
                        [
                            'title' => 'Barista',
                            'type' => 'Full-time &bull; Part-time',
                            'description' => 'Prepare and serve espresso drinks, pour-overs, and other beverages to a consistent standard. Baristas are the primary point of contact for our customers and play a direct role in their experience. Previous coffee experience is helpful but not required — we will train the right candidate.',
                            'responsibilities' => [
                                'Prepare espresso drinks and pour-overs to spec',
                                'Keep the workspace clean, organized, and well-stocked',
                                'Welcome customers, answer questions, and build community',
                                'Assist with opening and closing duties',
                            ],
                            'qualifications' => [
                                'Passion for coffee and eagerness to learn',
                                'Comfortable working weekends and early mornings',
                                'Strong communication and teamwork',
                            ],
                        ],
                        [
                            'title' => 'Roastery Production Assistant',
                            'type' => 'Full-time',
                            'description' => 'Support the roasting team with sourcing, roasting, and packaging operations. This role is physical and detail-oriented. You will work with raw materials, production equipment, and inventory systems to ensure our product meets quality standards.',
                            'responsibilities' => [
                                'Operate and maintain roasting equipment',
                                'Assist with green-coffee receiving, inventory, and rotation',
                                'Package and label roasted coffee for wholesale and retail',
                                'Record roast profiles and help maintain quality logs',
                            ],
                            'qualifications' => [
                                'Comfortable with physical work (lifting 50+ lb bags)',
                                'Basic math and record-keeping skills',
                                'Willingness to work in a hot, fast-paced environment',
                                'Roasting or production experience is a plus, not required',
                            ],
                        ],
                        [
                            'title' => 'Baker / Pastry Chef',
                            'type' => 'Full-time &bull; Part-time',
                            'description' => 'Produce baked goods and pastries from scratch in a commercial kitchen. You will be responsible for daily production, quality control, and kitchen safety. This role requires solid baking fundamentals and the ability to work independently early in the morning.',
                            'responsibilities' => [
                                'Prepare baked goods and pastries from scratch daily',
                                'Develop seasonal specials in collaboration with the team',
                                'Maintain a clean, organized, and safe kitchen',
                                'Manage ingredient inventory and communicate supply needs',
                            ],
                            'qualifications' => [
                                'Experience in a commercial kitchen or bakery',
                                'Understanding of baking fundamentals and food safety',
                                'Ability to work early mornings reliably',
                                'Creativity and willingness to experiment',
                            ],
                        ],
                        [
                            'title' => 'Maintenance Technician',
                            'type' => 'Part-time &bull; Contract',
                            'description' => 'Maintain the facility, coffee equipment, and building systems. Duties span plumbing, electrical, carpentry, and equipment repair. We need someone who can work independently, respond to urgent issues, and keep operations running without interruption.',
                            'responsibilities' => [
                                'Perform routine inspections and preventive maintenance',
                                'Troubleshoot and repair coffee equipment, HVAC, and plumbing',
                                'Coordinate with external contractors for larger repairs',
                                'Maintain an organized tool and supply inventory',
                            ],
                            'qualifications' => [
                                'General handyman or maintenance experience',
                                'Familiarity with commercial kitchen equipment is a plus',
                                'Reliable, self-directed, and responsive to urgent issues',
                                "Valid driver's license and ability to source parts locally",
                            ],
                        ],
                    ];
                @endphp

                @foreach ($roles as $i => $role)
                    <article class="scroll-mt-24 {{ $i > 0 ? 'border-t border-fog/70 pt-10 md:pt-12' : '' }}" id="role-{{ Str::slug($role['title']) }}">
                        <div class="grid gap-6 md:grid-cols-[1fr_2fr] md:gap-10">
                            {{-- Meta column --}}
                            <div class="md:sticky md:top-28 md:self-start">
                                <h2 class="font-display text-2xl font-bold text-ink sm:text-3xl">{{ $role['title'] }}</h2>
                                <p class="mt-2 text-sm font-semibold uppercase tracking-wider text-clay-600">{!! $role['type'] !!}</p>
                            </div>

                            {{-- Detail column --}}
                            <div>
                                <p class="text-base leading-relaxed text-stone-700">{{ $role['description'] }}</p>

                                @if ($role['responsibilities'])
                                    <div class="mt-6">
                                        <h3 class="text-sm font-bold uppercase tracking-wider text-pine-800">What you'll do</h3>
                                        <ul class="mt-3 space-y-2 text-sm leading-relaxed text-stone-600">
                                            @foreach ($role['responsibilities'] as $item)
                                                <li class="flex gap-3">
                                                    <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-clay-500" aria-hidden="true"></span>
                                                    <span>{{ $item }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if ($role['qualifications'])
                                    <div class="mt-5 border-t border-fog/50 pt-5">
                                        <h3 class="text-sm font-bold uppercase tracking-wider text-pine-800">Who you are</h3>
                                        <ul class="mt-3 space-y-2 text-sm leading-relaxed text-stone-600">
                                            @foreach ($role['qualifications'] as $item)
                                                <li class="flex gap-3">
                                                    <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-clay-500" aria-hidden="true"></span>
                                                    <span>{{ $item }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Contact form --}}
    <section id="apply" class="section-band scroll-mt-24">
        <div class="site-wrap">
            <div class="mx-auto max-w-2xl">
                <p class="section-label text-clay-600">Apply</p>
                <h2 class="section-title mt-3">Interested? Get in touch.</h2>
                <p class="section-lead mt-3">
                    Submit your name, email, and a brief message. Tell us which role interests you and why you are a good fit. We will respond if there is a match.
                </p>

                <div class="section-inset bg-white/70 mt-8">
                    <livewire:contact-form subject-label="Job role" />
                </div>
            </div>
        </div>
    </section>
@endsection
