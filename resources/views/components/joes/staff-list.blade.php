@props(['members' => []])

<ul {{ $attributes->merge(['class' => 'staff-list']) }}>
    @foreach ($members as $member)
        <li class="staff-list__item">
            <x-joes.staff-caricature
                :character="$member['caricature'] ?? 'sam'"
                :name="$member['name']"
            />
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
