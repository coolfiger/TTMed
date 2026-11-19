<div>
    <h2 class="text-lg font-semibold text-slate-800 mb-2">Set a new password</h2>
    <p class="text-sm text-slate-500 mb-6">Your account requires a password change before you can continue.</p>

    <form wire:submit="save" class="space-y-5">
        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">New Password</label>
            <input wire:model="password" id="password" type="password" autocomplete="new-password" autofocus
                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 @error('password') border-red-500 @enderror">
            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            <p class="mt-1 text-xs text-slate-400">Minimum 10 characters, mixed case, at least one number.</p>
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm Password</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password"
                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>

        <button type="submit"
            class="w-full flex justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2"
            wire:loading.attr="disabled" wire:loading.class="opacity-75">
            <span wire:loading.remove>Set Password</span>
            <span wire:loading>Saving…</span>
        </button>
    </form>
</div>
