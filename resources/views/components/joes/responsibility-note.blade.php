@props(['data', 'embedded' => false])

@php
    $tag = $embedded ? 'div' : 'section';
@endphp

<{{ $tag }}
    @unless($embedded) id="responsibility" @endunless
    {{ $attributes->class($embedded ? 'responsibility-note responsibility-note--embedded' : 'section-block section-block--compact') }}
>
    @unless($embedded)
        <div class="site-wrap max-w-4xl">
    @endunless

    <p class="responsibility-note__label">06 — Sourcing & community</p>
    @if (! empty($data['intro']))
        <p class="responsibility-note__intro">{{ $data['intro'] }}</p>
    @endif

    <div class="responsibility-note__grid">
        @foreach (['sourcing', 'community'] as $key)
            @if ($block = $data[$key] ?? null)
                <div>
                    <h3 class="responsibility-note__heading">{{ $block['title'] ?? '' }}</h3>
                    <ul class="responsibility-note__list">
                        @foreach ($block['items'] ?? [] as $item)
                            @php
                                $text = is_array($item) ? ($item['text'] ?? '') : $item;
                                $icon = is_array($item) ? ($item['icon'] ?? 'default') : 'default';
                            @endphp
                            <li class="responsibility-note__item">
                                <x-joes.responsibility-icon :name="$icon" />
                                <span>{{ $text }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    </div>

    @unless($embedded)
        </div>
    @endunless
</{{ $tag }}>
