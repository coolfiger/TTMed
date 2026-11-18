<div class="max-w-2xl">
    <form wire:submit="save" class="space-y-5 bg-white rounded-lg border border-slate-200 p-6">
        <div class="grid grid-cols-2 gap-5">
            <div class="col-span-2">
                <label class="block text-sm font-medium text-slate-700">Full Name</label>
                <input wire:model="name" type="text"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('name') border-red-500 @enderror">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Username</label>
                <input wire:model="username" type="text"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm font-mono shadow-sm focus:border-blue-500 focus:outline-none @error('username') border-red-500 @enderror"
                    {{ $user ? 'readonly' : '' }}>
                @error('username') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Email (optional)</label>
                <input wire:model="email" type="email"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('email') border-red-500 @enderror">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-slate-700">
                    Password {{ $user ? '(leave blank to keep current)' : '' }}
                </label>
                <input wire:model="password" type="password"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('password') border-red-500 @enderror">
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Roles</label>
            <div class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto border border-slate-200 rounded-md p-3">
                @foreach($roles as $role)
                <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input wire:model="selectedRoles" type="checkbox" value="{{ $role->id }}"
                        class="rounded border-slate-300 text-blue-600">
                    {{ $role->name }}
                </label>
                @endforeach
            </div>
            @error('selectedRoles') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-2">
            <input wire:model="isActive" id="isActive" type="checkbox"
                class="rounded border-slate-300 text-blue-600">
            <label for="isActive" class="text-sm text-slate-700">Account active</label>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit"
                class="rounded-md bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                {{ $user ? 'Update User' : 'Create User' }}
            </button>
            <a href="{{ route('tenant.users.index') }}"
                class="rounded-md px-5 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
                Cancel
            </a>
        </div>
    </form>
</div>
