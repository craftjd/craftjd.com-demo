@props(['location', 'contact'])

<div {{ $attributes->merge(['class' => 'location-map']) }}>
  
  @if ($headline = $location['headline'] ?? null)
  <h3 class="location-map__title mb-4">{{ $headline }}</h3>
  @endif

  @if ($intro = $location['intro'] ?? null)
      <p class="location-map__lead mb-6">{{ $intro }}</p>
  @endif

    <ul class="location-map__directions">
        @foreach ($location['directions'] ?? [] as $step)
            <li class="location-map__step">
                <h4 class="location-map__step-title">{{ $step['title'] }}</h4>
                <p class="location-map__step-text">{{ $step['text'] }}</p>
            </li>
        @endforeach
    </ul>
</div>
