<div>
    <h2 class="text-lg font-semibold text-slate-800 mb-6">Forgot your password?</h2>

    @if($sent)
        <div class="rounded-md bg-green-50 border border-green-200 p-4 text-sm text-green-800">
            If an account with that email address exists, you will receive a password reset link shortly.
        </div>
        <div class="mt-4 text-center">
            <a href="{{ route('tenant.login') }}" class="text-sm text-blue-600 hover:underline">Back to sign in</a>
        </div>
    @else
        <p class="text-sm text-slate-600 mb-5">Enter your email address and we will send you a link to reset your password.</p>

        <form wire:submit="sendLink" class="space-y-5">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
                <input
                    wire:model="email"
                    id="email"
                    type="email"
                    autocomplete="email"
                    autofocus
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 @error('email') border-red-500 @enderror"
                >
                @error('email')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full flex justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-75"
            >
                <span wire:loading.remove>Send reset link</span>
                <span wire:loading>Sending…</span>
            </button>

            <div class="text-center">
                <a href="{{ route('tenant.login') }}" class="text-sm text-slate-500 hover:text-slate-700">Back to sign in</a>
            </div>
        </form>
    @endif
</div>
