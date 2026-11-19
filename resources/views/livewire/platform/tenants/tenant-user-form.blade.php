<div class="max-w-2xl space-y-6">
    <div class="flex items-center justify-between mb-2">
        <h2 class="text-base font-semibold text-slate-800">
            {{ $isEdit ? 'Edit User' : 'New User' }} — {{ $tenant->name }}
        </h2>
        <a href="{{ route('platform.tenants.show', $tenant->uuid) }}" class="text-sm text-slate-500 hover:text-slate-800">← Back</a>
    </div>

    <form wire:submit="save" class="space-y-5 bg-white rounded-lg border border-slate-200 p-6">

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Full Name</label>
            <input wire:model="name" type="text" autocomplete="off"
                class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none @error('name') border-red-500 @enderror">
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Username</label>
            <input wire:model="username" type="text" autocomplete="off"
                class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm font-mono shadow-sm focus:border-slate-500 focus:outline-none @error('username') border-red-500 @enderror"
                {{ $isEdit ? 'readonly' : '' }}>
            @error('username') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input wire:model="email" type="email" autocomplete="off"
                class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none @error('email') border-red-500 @enderror">
            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Role --}}
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Role at {{ $tenant->name }}</label>
            <select wire:model="roleId"
                class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none @error('roleId') border-red-500 @enderror">
                <option value="">— No role —</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
            @error('roleId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-6">
            <div class="flex items-center gap-2">
                <input wire:model="is_active" id="is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300">
                <label for="is_active" class="text-sm font-medium text-slate-700">Active</label>
            </div>
            <div class="flex items-center gap-2">
                <input wire:model="must_change_password" id="must_change_password" type="checkbox" class="h-4 w-4 rounded border-slate-300">
                <label for="must_change_password" class="text-sm font-medium text-slate-700">Must change password on next login</label>
            </div>
        </div>

        <div class="pt-2 border-t border-slate-100">
            <p class="text-xs text-slate-500 mb-4">
                {{ $isEdit ? 'Leave blank to keep the current password.' : 'Password is required.' }}
            </p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ $isEdit ? 'New Password' : 'Password' }}</label>
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

    {{-- Other centre memberships --}}
    @if($isEdit)
    <div class="bg-white rounded-lg border border-slate-200">
        <div class="px-6 py-4 border-b border-slate-100">
            <h3 class="text-sm font-semibold text-slate-800">Other Medical Centre Memberships</h3>
            <p class="text-xs text-slate-500 mt-0.5">Other centres where a user with this email address exists.</p>
        </div>
        @if($otherCentres->isNotEmpty())
        <table class="min-w-full divide-y divide-slate-100">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Centre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Username at Centre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($otherCentres as $membership)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-3 text-sm font-medium text-slate-800">
                        <a href="{{ route('platform.tenants.show', $membership['tenant']->uuid) }}" class="hover:underline">
                            {{ $membership['tenant']->name }}
                        </a>
                    </td>
                    <td class="px-6 py-3 text-sm font-mono text-slate-600">{{ $membership['user']->username }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">
                        {{ $membership['user']->roles->pluck('name')->join(', ') ?: '—' }}
                    </td>
                    <td class="px-6 py-3 text-sm">
                        <span class="{{ $membership['user']->is_active ? 'text-green-600' : 'text-red-500' }}">
                            {{ $membership['user']->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-right">
                        <a href="{{ route('platform.tenants.users.edit', [$membership['tenant']->uuid, $membership['user']->id]) }}"
                            class="text-xs text-slate-500 hover:text-slate-800">Edit</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="px-6 py-5 text-sm text-slate-400">This person is not registered at any other medical centre.</p>
        @endif
    </div>
    @endif
</div>
