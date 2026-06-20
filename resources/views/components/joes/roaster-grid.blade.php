@props(['roasters'])

<div {{ $attributes }}>
    @if ($intro = $roasters['intro'] ?? null)
        <p class="roaster-grid__intro">{{ $intro }}</p>
    @endif

    <ul class="roaster-grid">
        @foreach ($roasters['roasters'] ?? [] as $roaster)
            <li class="roaster-grid__item">
                <span class="roaster-grid__name">{{ $roaster['name'] }}</span>
                @if (! empty($roaster['origin']))
                    <span class="roaster-grid__origin">{{ $roaster['origin'] }}</span>
                @endif
            </li>
        @endforeach
    </ul>
</div>
