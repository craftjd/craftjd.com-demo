@props(['gallery'])

<section id="gallery" class="photo-gallery">
    <div class="site-wrap">
        <div class="photo-gallery__header">
            <div>
                <p class="photo-gallery__label">From the floor</p>
                @if (! empty($gallery['intro']))
                    <p class="photo-gallery__intro">{{ $gallery['intro'] }}</p>
                @endif
            </div>
            @if ($handle = $gallery['handle'] ?? null)
                <p class="photo-gallery__handle">{{ $handle }}</p>
            @endif
        </div>
    </div>

    <div class="photo-gallery__grid">
        @foreach ($gallery['photos'] ?? [] as $photo)
            <figure @class([
                'photo-gallery__item',
                'photo-gallery__item--wide' => ($photo['span'] ?? '') === 'wide',
                'photo-gallery__item--tall' => ($photo['span'] ?? '') === 'tall',
            ])>
                <img
                    src="{{ $photo['src'] }}"
                    alt="{{ $photo['alt'] ?? '' }}"
                    class="photo-gallery__img"
                    loading="lazy"
                    decoding="async"
                >
            </figure>
        @endforeach
    </div>
</section>
