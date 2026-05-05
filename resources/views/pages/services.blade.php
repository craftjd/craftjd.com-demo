@extends('layouts.base')

@section('title', 'Services')
@section('meta_description', 'Strategy, design, and development for growing teams.')

@section('content')
    {{-- Dark hero band — inline utilities, no extracted component --}}
    <section class="relative overflow-hidden bg-gray-900 text-white">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-600/30 via-transparent to-transparent"></div>
        <div class="container relative py-20 sm:py-28">
            <p class="mb-4 text-sm font-semibold uppercase tracking-wider text-blue-300">What we do</p>
            <h1 class="max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl">Services built for growth</h1>
            <p class="mt-6 max-w-2xl text-lg text-gray-300">
                Everything on this page lives in a single Blade file. Tweak the markup and classes directly — no front matter, no split content.
            </p>
            <a href="{{ url('/contact') }}" class="btn btn-primary mt-10">Start a project</a>
        </div>
    </section>

    {{-- Stats row --}}
    <section class="border-b border-gray-200 bg-gray-50">
        <div class="container py-12">
            <dl class="grid gap-8 text-center sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Projects delivered</dt>
                    <dd class="mt-2 text-3xl font-bold text-gray-900">120+</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Years experience</dt>
                    <dd class="mt-2 text-3xl font-bold text-gray-900">10</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Client retention</dt>
                    <dd class="mt-2 text-3xl font-bold text-gray-900">94%</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Avg. response time</dt>
                    <dd class="mt-2 text-3xl font-bold text-gray-900">24h</dd>
                </div>
            </dl>
        </div>
    </section>

    {{-- Feature cards — structure varies per page, stays inline --}}
    <section class="section">
        <div class="container">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <article class="card">
                    <h2 class="text-xl font-semibold">Strategy</h2>
                    <p class="mt-3 text-gray-600">Positioning, roadmaps, and discovery workshops before a single line is written.</p>
                </article>
                <article class="card">
                    <h2 class="text-xl font-semibold">Design</h2>
                    <p class="mt-3 text-gray-600">Brand systems, UX flows, and production-ready interfaces across every touchpoint.</p>
                </article>
                <article class="card">
                    <h2 class="text-xl font-semibold">Development</h2>
                    <p class="mt-3 text-gray-600">Fast, maintainable builds on Laravel and modern frontends.</p>
                </article>
            </div>
        </div>
    </section>

    {{-- Process section — different markup than other pages would use --}}
    <section class="section border-t border-gray-200">
        <div class="container max-w-3xl">
            <h2 class="text-2xl font-bold">How we work</h2>
            <ol class="mt-6 space-y-4 text-gray-600">
                <li class="flex gap-4">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">1</span>
                    <span><strong class="text-gray-900">Discovery</strong> — goals, audience, constraints</span>
                </li>
                <li class="flex gap-4">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">2</span>
                    <span><strong class="text-gray-900">Design</strong> — wireframes, visual direction, feedback</span>
                </li>
                <li class="flex gap-4">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">3</span>
                    <span><strong class="text-gray-900">Build</strong> — development, content, QA</span>
                </li>
                <li class="flex gap-4">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">4</span>
                    <span><strong class="text-gray-900">Launch</strong> — deploy, handoff, optional retainer</span>
                </li>
            </ol>
        </div>
    </section>

    {{-- CTA band --}}
    <section class="section">
        <div class="container">
            <div class="rounded-2xl bg-blue-600 px-6 py-12 text-center text-white sm:px-12">
                <h2 class="text-2xl font-bold sm:text-3xl">Ready to talk?</h2>
                <p class="mx-auto mt-4 max-w-2xl text-blue-100">Tell us about your project and we'll follow up within one business day.</p>
                <a href="{{ url('/contact') }}" class="btn btn-secondary mt-8">Contact us</a>
            </div>
        </div>
    </section>
@endsection
