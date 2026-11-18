<div>
    <h2 class="text-lg font-semibold text-slate-800 mb-6">Platform Administration</h2>

    @if(!$showMfa)
    <form wire:submit="authenticate" class="space-y-5">
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
            <input
                wire:model="email"
                id="email"
                type="email"
                autocomplete="email"
                autofocus
                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500 @error('email') border-red-500 @enderror"
            >
            @error('email')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
            <input
                wire:model="password"
                id="password"
                type="password"
                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500 @error('password') border-red-500 @enderror"
            >
            @error('password')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="w-full flex justify-center rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700" wire:loading.attr="disabled">
            <span wire:loading.remove>Sign in</span>
            <span wire:loading>Signing in…</span>
        </button>
    </form>
    @else
    <form wire:submit="verifyMfa" class="space-y-5">
        <p class="text-sm text-slate-600 text-center">Enter your authenticator code.</p>
        <div>
            <input wire:model="mfaCode" type="text" inputmode="numeric" maxlength="6" autofocus
                class="block w-full rounded-md border border-slate-300 px-3 py-2 text-center text-lg font-mono tracking-widest @error('mfaCode') border-red-500 @enderror">
            @error('mfaCode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="w-full rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Verify</button>
    </form>
    @endif
</div>
