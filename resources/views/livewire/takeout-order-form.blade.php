<div class="form-panel-dark">
    @if ($submitted)
        <div class="demo-notice" role="status">
            <p class="font-semibold">Order submitted!</p>
            <p class="mt-1">Don't worry — you aren't on the hook for a real order. Nothing was processed.</p>
        </div>
    @endif

    <form wire:submit="submit" class="mt-6 space-y-5 {{ $submitted ? 'opacity-60' : '' }}">
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="takeout-name" class="form-label">Name</label>
                <input id="takeout-name" type="text" wire:model.blur="name" class="form-input" autocomplete="name" @disabled($submitted)>
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="takeout-phone" class="form-label">Phone</label>
                <input id="takeout-phone" type="tel" wire:model.blur="phone" class="form-input" autocomplete="tel" @disabled($submitted)>
                @error('phone') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="takeout-email" class="form-label">Email</label>
            <input id="takeout-email" type="email" wire:model.blur="email" class="form-input" autocomplete="email" @disabled($submitted)>
            @error('email') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="takeout-time" class="form-label">Pickup time</label>
            <input id="takeout-time" type="text" wire:model.blur="pickup_time" class="form-input" placeholder="e.g. Today around 8:30am" @disabled($submitted)>
            @error('pickup_time') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="takeout-details" class="form-label">Your order</label>
            <textarea id="takeout-details" wire:model.blur="order_details" rows="4" class="form-input" placeholder="Large drip, one morning bun, oat milk latte..." @disabled($submitted)></textarea>
            @error('order_details') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn btn-clay" @disabled($submitted)>Submit order</button>
    </form>
</div>
