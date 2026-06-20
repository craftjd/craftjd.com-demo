@props([
    'categories' => [],
    'items' => [],
])

@php
    $groups = $categories !== []
        ? $categories
        : ($items !== [] ? [['name' => null, 'items' => $items]] : []);
@endphp

<div {{ $attributes->merge(['class' => 'menu-board']) }}>
    <div class="grid gap-8 sm:grid-cols-2 sm:gap-x-10 lg:gap-x-14">
        @foreach ($groups as $category)
            <div class="menu-category">
                @if (! empty($category['name']))
                    <h3 class="menu-category-title">{{ $category['name'] }}</h3>
                @endif

                <div class="menu-category-items">
                    @foreach ($category['items'] ?? [] as $item)
                        <div class="menu-row">
                            <div>
                                <div class="menu-row-name">
                                    <span>{{ $item['name'] }}</span>
                                </div>
                                @if (! empty($item['note']))
                                    <p class="menu-row-note">{{ $item['note'] }}</p>
                                @endif
                            </div>
                            <span class="menu-row-price">${{ $item['price'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
