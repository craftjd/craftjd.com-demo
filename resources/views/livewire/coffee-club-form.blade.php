<div class="form-panel-dark">
    @if ($submitted)
        <div class="demo-notice" role="status">
            <p class="font-semibold">You're in — sort of.</p>
            <p class="mt-1">Signup recorded for demo purposes only. No card charged, no beans shipped.</p>
        </div>
    @endif

    <form wire:submit="submit" class="mt-6 space-y-5 {{ $submitted ? 'opacity-60' : '' }}">
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="club-name" class="form-label">Name</label>
                <input id="club-name" type="text" wire:model.blur="name" class="form-input" autocomplete="name" @disabled($submitted)>
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="club-email" class="form-label">Email</label>
                <input id="club-email" type="email" wire:model.blur="email" class="form-input" autocomplete="email" @disabled($submitted)>
                @error('email') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <fieldset class="space-y-3">
            <legend class="form-label">Delivery</legend>
            <div class="flex flex-wrap gap-4">
                <label class="form-option-label">
                    <input type="radio" wire:model="delivery" value="pickup" class="text-pine-800 focus:ring-pine-700" @disabled($submitted)>
                    Pickup at shop
                </label>
                <label class="form-option-label">
                    <input type="radio" wire:model="delivery" value="ship" class="text-pine-800 focus:ring-pine-700" @disabled($submitted)>
                    Ship to me
                </label>
            </div>
            @error('delivery') <p class="form-error">{{ $message }}</p> @enderror
        </fieldset>

        <fieldset class="space-y-3">
            <legend class="form-label">Grind</legend>
            <div class="flex flex-wrap gap-4">
                <label class="form-option-label">
                    <input type="radio" wire:model="grind" value="whole" class="text-pine-800 focus:ring-pine-700" @disabled($submitted)>
                    Whole bean
                </label>
                <label class="form-option-label">
                    <input type="radio" wire:model="grind" value="ground" class="text-pine-800 focus:ring-pine-700" @disabled($submitted)>
                    Ground
                </label>
            </div>
            @error('grind') <p class="form-error">{{ $message }}</p> @enderror
        </fieldset>

        <button type="submit" class="btn btn-clay" @disabled($submitted)>Join the club</button>
    </form>
</div>
