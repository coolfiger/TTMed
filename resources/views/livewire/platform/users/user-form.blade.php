<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-base font-semibold text-slate-800">
            @if($isProfile)
                My Profile
            @elseif($user?->exists)
                Edit User
            @else
                New Platform User
            @endif
        </h2>
        @unless($isProfile)
        <a href="{{ route('platform.users.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← Back to users</a>
        @endunless
    </div>

    <form wire:submit="save" class="max-w-lg space-y-5">

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
            <input wire:model="name" type="text" autocomplete="off"
                class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none @error('name') border-red-500 @enderror">
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input wire:model="email" type="email" autocomplete="off"
                class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none @error('email') border-red-500 @enderror">
            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        @unless($isProfile)
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
            <select wire:model="role"
                class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none @error('role') border-red-500 @enderror">
                <option value="super_admin">Super Admin</option>
                <option value="admin">Admin</option>
                <option value="support">Support</option>
            </select>
            @error('role') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3">
            <input wire:model="is_active" id="is_active" type="checkbox"
                class="h-4 w-4 rounded border-slate-300 text-slate-800">
            <label for="is_active" class="text-sm font-medium text-slate-700">Active</label>
        </div>
        @endunless

        <div class="pt-2 border-t border-slate-100">
            <p class="text-xs text-slate-500 mb-4">
                {{ $user?->exists ? 'Leave password fields blank to keep the current password.' : 'Password is required for new users.' }}
            </p>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        {{ $user?->exists ? 'New Password' : 'Password' }}
                    </label>
                    <input wire:model="password" type="password" autocomplete="new-password"
                        class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none @error('password') border-red-500 @enderror">
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Confirm Password</label>
                    <input wire:model="password_confirmation" type="password" autocomplete="new-password"
                        class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none">
                </div>
            </div>
        </div>

        <div class="pt-2">
            <button type="submit"
                class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
                wire:loading.attr="disabled">
                <span wire:loading.remove>Save</span>
                <span wire:loading>Saving…</span>
            </button>
        </div>
    </form>
</div>
