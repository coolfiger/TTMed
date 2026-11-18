<div>
    <div class="flex items-center gap-3 mb-6">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search…"
            class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none w-64">
    </div>

    <div class="bg-white rounded-lg border border-slate-200">
        <table class="min-w-full divide-y divide-slate-100">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">MFA</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Last Login</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($users as $user)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-3 text-sm font-medium text-slate-800">{{ $user->name }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $user->email }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ ucfirst($user->role) }}</td>
                    <td class="px-6 py-3 text-sm">
                        <span class="{{ $user->mfa_enabled ? 'text-green-600' : 'text-slate-400' }}">
                            {{ $user->mfa_enabled ? 'On' : 'Off' }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-sm text-slate-500">
                        {{ $user->last_login_at?->diffForHumans() ?? 'Never' }}
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
