@props([
    'number',
    'title',
    'lead' => null,
    'watermark' => false,
])

<div
    {{ $attributes->class([
        'section-head',
        'section-head--watermark' => $watermark,
    ]) }}
    @if ($watermark) data-watermark="{{ $number }}" @endif
>
    <p class="section-label">{{ $number }} — {{ $title }}</p>
    @if ($lead)
        <p class="section-lead">{{ $lead }}</p>
    @endif
</div>
