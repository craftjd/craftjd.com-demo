<div>
    @if ($submitted)
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
            <p class="font-medium">Thanks for your message.</p>
            <p class="mt-1 text-sm">We'll be in touch soon.</p>
        </div>
    @else
        @if (! config('client.contact_email'))
            <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800 text-sm">
                Set <code class="rounded bg-amber-100 px-1">CONTACT_EMAIL</code> in your environment to enable the contact form.
            </div>
        @endif

        <form wire:submit="submit" class="space-y-6">
            {{-- Honeypot --}}
            <div class="hidden" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" id="website" wire:model="website" tabindex="-1" autocomplete="off">
            </div>

            <div>
                <label for="name" class="form-label">Name</label>
                <input type="text" id="name" wire:model="name" class="form-input" required>
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" wire:model="email" class="form-input" required>
                @error('email') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="message" class="form-label">Message</label>
                <textarea id="message" wire:model="message" rows="5" class="form-input" required></textarea>
                @error('message') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <button
                type="submit"
                class="btn btn-primary"
                wire:loading.attr="disabled"
                @disabled(! config('client.contact_email'))
            >
                <span wire:loading.remove>Send message</span>
                <span wire:loading>Sending…</span>
            </button>
        </form>
    @endif
</div>
