<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-base font-semibold text-slate-800">Medical Centre Users</h2>
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search name, username, email…"
            class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none w-72">
    </div>

    <div class="bg-white rounded-lg border border-slate-200">
        <table class="min-w-full divide-y divide-slate-100">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Username</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Medical Centre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Roles</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($users as $user)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-3 text-sm font-medium text-slate-800">{{ $user->name }}</td>
                    <td class="px-6 py-3 text-sm font-mono text-slate-600">{{ $user->username }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $user->email }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">
                        <a href="{{ route('platform.tenants.show', $user->_tenant->uuid) }}" class="hover:underline">
                            {{ $user->_tenant->name }}
                        </a>
                    </td>
                    <td class="px-6 py-3 text-sm text-slate-600">
                        {{ $user->roles->pluck('name')->join(', ') ?: '—' }}
                    </td>
                    <td class="px-6 py-3 text-sm">
                        <span class="{{ $user->is_active ? 'text-green-600' : 'text-red-500' }}">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-right">
                        <a href="{{ route('platform.tenants.users.edit', [$user->_tenant->uuid, $user->id]) }}"
                            class="text-xs text-slate-500 hover:text-slate-800">Edit</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-8 text-center text-sm text-slate-400">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
