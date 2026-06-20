@props(['reviews'])

@php
    $featured = $reviews['featured'] ?? null;
    $items = $reviews['items'] ?? [];
    $tones = ['reviews-board__cell--a', 'reviews-board__cell--b', 'reviews-board__cell--c', 'reviews-board__cell--d'];
@endphp

<div {{ $attributes->merge(['class' => 'reviews-board']) }}>
    @if ($featured)
        <article class="reviews-board__featured reviews-board__cell reviews-board__cell--featured">
            <div class="reviews-board__featured-top">
                @if (! empty($featured['rating']))
                    <x-joes.star-rating :rating="$featured['rating']" />
                @endif
            </div>
            <blockquote>
                <p class="reviews-board__quote reviews-board__quote--lead">&ldquo;{{ $featured['quote'] }}&rdquo;</p>
                <footer class="reviews-board__footer">
                    <cite class="not-italic font-semibold text-pine-800">{{ $featured['author'] }}</cite>
                    @if (! empty($featured['context']))
                        <span class="text-stone-500">{{ $featured['context'] }}</span>
                    @endif
                </footer>
            </blockquote>
        </article>
    @endif

    <div class="reviews-board__grid">
        @foreach ($items as $index => $review)
            <blockquote @class([
                'reviews-board__cell',
                $tones[$index % count($tones)],
                'reviews-board__cell--span-4' => $index === 2,
                'reviews-board__cell--span-2' => $index !== 2,
            ])>
                @if (! empty($review['rating']))
                    <x-joes.star-rating :rating="$review['rating']" class="mb-2 scale-90 origin-left" />
                @endif
                <p class="reviews-board__quote">&ldquo;{{ $review['quote'] }}&rdquo;</p>
                <footer class="reviews-board__footer">
                    <cite class="not-italic font-semibold text-stone-700">{{ $review['author'] }}</cite>
                    @if (! empty($review['context']))
                        <span class="text-stone-400"> · {{ $review['context'] }}</span>
                    @endif
                </footer>
            </blockquote>
        @endforeach
    </div>
</div>
