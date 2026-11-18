<div>
    <div class="flex items-center justify-between mb-6">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search users…"
            class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none w-64">
        <a href="{{ route('tenant.users.create') }}"
            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
            New User
        </a>
    </div>

    <div class="bg-white rounded-lg border border-slate-200">
        <table class="min-w-full divide-y divide-slate-100">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Username</th>
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
                    <td class="px-6 py-3 text-sm text-slate-600">
                        {{ $user->roles->pluck('name')->join(', ') ?: '—' }}
                    </td>
                    <td class="px-6 py-3">
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                            {{ $user->is_active ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-right">
                        <a href="{{ route('tenant.users.edit', $user->id) }}"
                            class="text-xs text-slate-500 hover:text-slate-800">Edit</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-slate-400">No users.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-3 border-t border-slate-100">{{ $users->links() }}</div>
    </div>
</div>
