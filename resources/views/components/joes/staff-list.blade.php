@props(['members' => []])

@php
    $avatarMap = [
        'joe' => 'manager',
        'mara' => 'chef',
        'elliot' => 'beanie',
        'sam' => 'beanie',
    ];
@endphp

<ul {{ $attributes->merge(['class' => 'staff-list']) }}>
    @foreach ($members as $member)
        @php
            $character = $member['caricature'] ?? 'sam';
            $avatar = $avatarMap[$character] ?? 'manager';
        @endphp
        <li class="staff-list__item">
            <div class="staff-caricature" role="img" aria-label="Role icon for {{ $member['name'] }}">
                <img
                    src="{{ asset('avatars/'.$avatar.'.svg') }}"
                    alt=""
                    class="h-full w-full object-contain p-4"
                    loading="lazy"
                    decoding="async"
                >
            </div>
            <div class="staff-list__body">
                <div class="staff-list__meta">
                    <h3 class="staff-list__name">{{ $member['name'] }}</h3>
                    <p class="staff-list__role">{{ $member['role'] }}</p>
                </div>
                <p class="staff-list__bio">{{ $member['bio'] }}</p>
            </div>
        </li>
    @endforeach
</ul>
