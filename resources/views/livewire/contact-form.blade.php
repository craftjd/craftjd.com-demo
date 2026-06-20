<div>
    @if ($submitted)
        <div class="demo-notice" role="status">
            <p class="font-semibold text-pine-900">Message received.</p>
            <p class="mt-1 text-stone-700">Thanks — we'd reply if this were live. Nothing was sent anywhere.</p>
        </div>
    @endif

    <form wire:submit="submit" class="mt-6 space-y-5 {{ $submitted ? 'opacity-60' : '' }}" @if ($submitted) aria-disabled="true" @endif>
        <div>
            <label for="contact-name" class="form-label">Name</label>
            <input id="contact-name" type="text" wire:model.blur="name" class="form-input" autocomplete="name" @disabled($submitted)>
            @error('name') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="contact-email" class="form-label">Email</label>
            <input id="contact-email" type="email" wire:model.blur="email" class="form-input" autocomplete="email" @disabled($submitted)>
            @error('email') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="contact-subject" class="form-label">Subject <span class="font-normal text-stone-500">(optional)</span></label>
            <input id="contact-subject" type="text" wire:model.blur="subject" class="form-input" @disabled($submitted)>
            @error('subject') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="contact-message" class="form-label">Message</label>
            <textarea id="contact-message" wire:model.blur="message" rows="5" class="form-input" @disabled($submitted)></textarea>
            @error('message') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn btn-primary" @disabled($submitted)>Send message</button>
    </form>
</div>
