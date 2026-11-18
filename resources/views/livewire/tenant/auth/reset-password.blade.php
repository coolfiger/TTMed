<div>
    <h2 class="text-lg font-semibold text-slate-800 mb-6">Set a new password</h2>

    @if($done)
        <div class="rounded-md bg-green-50 border border-green-200 p-4 text-sm text-green-800">
            Your password has been reset successfully.
        </div>
        <div class="mt-4 text-center">
            <a href="{{ route('tenant.login') }}" class="text-sm text-blue-600 hover:underline">Sign in with your new password</a>
        </div>
    @else
        <form wire:submit="resetPassword" class="space-y-5">
            @error('email')
                <div class="rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-700">{{ $message }}</div>
            @enderror

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">New password</label>
                <input
                    wire:model="password"
                    id="password"
                    type="password"
                    autocomplete="new-password"
                    autofocus
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 @error('password') border-red-500 @enderror"
                >
                @error('password')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm new password</label>
                <input
                    wire:model="password_confirmation"
                    id="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >
            </div>

            <button
                type="submit"
                class="w-full flex justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-75"
            >
                <span wire:loading.remove>Reset password</span>
                <span wire:loading>Resetting…</span>
            </button>
        </form>
    @endif
</div>
