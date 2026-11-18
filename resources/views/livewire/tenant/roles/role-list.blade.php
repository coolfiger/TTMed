<div>
    <div class="flex justify-end mb-6">
        <a href="{{ route('tenant.roles.create') }}"
            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
            New Role
        </a>
    </div>

    <div class="bg-white rounded-lg border border-slate-200">
        <table class="min-w-full divide-y divide-slate-100">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Slug</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Permissions</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Type</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($roles as $role)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-3 text-sm font-medium text-slate-800">{{ $role->name }}</td>
                    <td class="px-6 py-3 text-sm font-mono text-slate-600">{{ $role->slug }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $role->permissions_count }}</td>
                    <td class="px-6 py-3 text-sm">
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                            {{ $role->is_system ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600' }}">
                            {{ $role->is_system ? 'System' : 'Custom' }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-right">
                        @if(!$role->is_system)
                        <a href="{{ route('tenant.roles.edit', $role->id) }}"
                            class="text-xs text-slate-500 hover:text-slate-800">Edit</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-slate-400">No roles.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
