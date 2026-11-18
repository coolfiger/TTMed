<div class="max-w-2xl">
    <form wire:submit="save" class="space-y-5 bg-white rounded-lg border border-slate-200 p-6">
        @if($role?->is_system)
        <div class="rounded-md bg-yellow-50 p-4 text-sm text-yellow-800 ring-1 ring-yellow-200">
            System roles cannot be modified.
        </div>
        @endif

        <div>
            <label class="block text-sm font-medium text-slate-700">Role Name</label>
            <input wire:model="name" type="text" {{ $role?->is_system ? 'disabled' : '' }}
                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('name') border-red-500 @enderror">
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Slug</label>
            <input wire:model="slug" type="text" {{ $role?->is_system ? 'disabled' : '' }}
                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm font-mono shadow-sm focus:border-blue-500 focus:outline-none @error('slug') border-red-500 @enderror">
            @error('slug') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Permissions</label>
            @foreach($permissionGroups as $module => $permissions)
            <div class="mb-4">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">{{ $module }}</p>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($permissions as $permission)
                    <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                        <input wire:model="selectedPermissions" type="checkbox"
                            value="{{ $permission->id }}"
                            {{ $role?->is_system ? 'disabled' : '' }}
                            class="rounded border-slate-300 text-blue-600">
                        {{ $permission->name }}
                    </label>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        @if(!$role?->is_system)
        <div class="flex gap-3 pt-2">
            <button type="submit"
                class="rounded-md bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                {{ $role ? 'Update Role' : 'Create Role' }}
            </button>
            <a href="{{ route('tenant.roles.index') }}"
                class="rounded-md px-5 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
                Cancel
            </a>
        </div>
        @endif
    </form>
</div>
