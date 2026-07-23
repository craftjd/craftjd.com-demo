@props(['entries' => []])
@php
    $fmtDur = function (?int $mins): ?string {
        if ($mins === null) {
            return null;
        }
        if ($mins < 60) {
            return '~' . $mins . 'min';
        }
        $h = intdiv($mins, 60);
        $m = $mins % 60;
        return '~' . $h . 'hr' . $m . 'min';
    };
@endphp

<style>
    .changelog-panel {
        will-change: transform, opacity;
        opacity: 0;
        transform: translateY(2px) scale(0.95);
        pointer-events: none;
        visibility: hidden;
        transition:
            opacity 150ms ease-in,
            transform 150ms ease-in,
            visibility 150ms;
    }
    .changelog-panel-visible {
        opacity: 1;
        transform: translateY(0) scale(1);
        pointer-events: auto;
        visibility: visible;
        transition:
            opacity 200ms ease-out,
            transform 200ms ease-out,
            visibility 200ms;
    }
</style>

<div
    x-data="{ open: false }"
    class="fixed bottom-4 left-4 z-50"
    x-on:keydown.escape.window="open = false"
>
    {{-- Modal window --}}
    <div
        x-cloak
        :class="open ? 'changelog-panel-visible' : 'changelog-panel'"
        class="changelog-panel absolute bottom-full left-0 mb-3 w-[clamp(20rem,80vw,32rem)] origin-bottom-left overflow-hidden rounded-lg border border-stone-700/60 bg-stone-950 shadow-2xl shadow-black/50"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-stone-800 px-4 py-2.5">
            <span class="font-mono text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-stone-400">
                &#95; completed requests
            </span>
            <button
                @click="open = false"
                class="flex h-5 w-5 cursor-pointer items-center justify-center rounded font-mono text-[0.75rem] text-stone-500 transition hover:bg-stone-800 hover:text-stone-300"
                aria-label="Close"
            >
                &times;
            </button>
        </div>

        {{-- Entries --}}
        <div class="changelog-scroll max-h-[28rem] space-y-0 overflow-y-auto overscroll-contain lg:px-4 lg:py-3">
            @forelse ($entries as $entry)
                @php
                    $submitted = $entry['submitted_at'] ?? null;
                    $claimed = $entry['claimed_at'] ?? null;
                    $completed = $entry['completed_at'] ?? null;

                    $startDur = $submitted && $claimed
                        ? \Carbon\CarbonImmutable::parse($submitted)->diffInMinutes(\Carbon\CarbonImmutable::parse($claimed))
                        : null;

                    $completeDur = $claimed && $completed
                        ? \Carbon\CarbonImmutable::parse($claimed)->diffInMinutes(\Carbon\CarbonImmutable::parse($completed))
                        : null;

                    $totalDur = $submitted && $completed
                        ? \Carbon\CarbonImmutable::parse($submitted)->diffInMinutes(\Carbon\CarbonImmutable::parse($completed))
                        : null;
                @endphp

                <div class="group border-b border-stone-800 py-3 pl-3 transition hover:border-clay-600/60 last:border-b-0 sm:border-b-0 sm:border-l-2 sm:border-stone-800 sm:last:border-l-stone-800 sm:hover:border-l-clay-600/60">
                    {{-- Public ID --}}
                    <div class="font-mono text-[0.65rem] font-medium tracking-wider text-stone-500">
                        {{ $entry['public_id'] ?? '' }}
                    </div>

                    {{-- Summary --}}
                    <p class="mt-1 font-mono text-[0.8125rem] leading-relaxed text-stone-300">
                        {{ $entry['fulfillment_summary'] ?? $entry['request'] ?? '' }}
                    </p>

                    {{-- Timings --}}
                    <div class="mt-1.5 flex gap-3 font-mono text-[0.6rem] text-stone-600">
                        @if ($fmtDur($startDur))
                            <span>{{ $fmtDur($startDur) }} to start</span>
                        @endif
                        @if ($fmtDur($completeDur))
                            <span>{{ $fmtDur($completeDur) }} to complete</span>
                        @endif
                        @if (! $fmtDur($startDur) && ! $fmtDur($completeDur) && $fmtDur($totalDur))
                            <span>{{ $fmtDur($totalDur) }} total</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-6 text-center">
                    <p class="font-mono text-[0.75rem] text-stone-600">No completed requests yet.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Toggle button --}}
    <button
        @click="open = !open"
        :class="open ? 'ring-1 ring-stone-600 bg-stone-800 text-stone-200' : 'bg-stone-900/80 text-stone-400 hover:bg-stone-800 hover:text-stone-200 ring-1 ring-stone-700/50 hover:ring-stone-600/60'"
        class="flex cursor-pointer items-center gap-2 rounded-lg px-3.5 py-2 font-mono text-[0.75rem] font-medium uppercase tracking-wider transition-all duration-150"
        aria-label="Toggle completed requests"
    >
        <span class="text-[0.9rem] leading-none" aria-hidden="true">~</span>
        <span>Joe's requests</span>
    </button>
</div>
