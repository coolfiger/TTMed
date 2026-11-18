<div>
    <h2 class="text-lg font-semibold text-slate-800 mb-6">Sign in to your account</h2>

    @if(!$showMfa)
    <form wire:submit="authenticate" class="space-y-5">
        <div>
            <label for="username" class="block text-sm font-medium text-slate-700">Username</label>
            <input
                wire:model="username"
                id="username"
                type="text"
                autocomplete="username"
                autofocus
                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 @error('username') border-red-500 @enderror"
            >
            @error('username')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
            <input
                wire:model="password"
                id="password"
                type="password"
                autocomplete="current-password"
                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 @error('password') border-red-500 @enderror"
            >
            @error('password')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input wire:model="remember" type="checkbox" class="rounded border-slate-300 text-blue-600">
                Remember me
            </label>
            <a href="{{ route('tenant.password.request') }}" class="text-sm text-blue-600 hover:underline">Forgot password?</a>
        </div>

        <button
            type="submit"
            class="w-full flex justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2"
            wire:loading.attr="disabled"
            wire:loading.class="opacity-75"
        >
            <span wire:loading.remove>Sign in</span>
            <span wire:loading>Signing in…</span>
        </button>
    </form>
    @else
    {{-- MFA step --}}
    <form wire:submit="verifyMfa" class="space-y-5">
        <div class="text-center">
            <p class="text-sm text-slate-600">Enter the 6-digit code from your authenticator app.</p>
        </div>
        <div>
            <label for="mfaCode" class="block text-sm font-medium text-slate-700">Verification Code</label>
            <input
                wire:model="mfaCode"
                id="mfaCode"
                type="text"
                inputmode="numeric"
                maxlength="6"
                autofocus
                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-center text-lg font-mono tracking-widest shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 @error('mfaCode') border-red-500 @enderror"
            >
            @error('mfaCode')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="w-full flex justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
            Verify
        </button>
        <button type="button" wire:click="$set('showMfa', false)" class="w-full text-center text-sm text-slate-500 hover:text-slate-700">
            ← Back
        </button>
    </form>
    @endif
</div>
