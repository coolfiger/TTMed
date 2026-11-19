<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-base font-semibold text-slate-800">Centre Users</h2>
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search name, username, email…"
            class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none w-72">
    </div>

    <div class="space-y-4">
        @forelse($people as $person)
        <div class="bg-white rounded-lg border border-slate-200">
            <div class="flex items-center justify-between px-6 py-3 border-b border-slate-100 bg-slate-50 rounded-t-lg">
                <div>
                    <span class="text-sm font-semibold text-slate-800">{{ $person['name'] }}</span>
                    <span class="ml-3 text-sm text-slate-500">{{ $person['email'] }}</span>
                </div>
                <span class="text-xs text-slate-400 font-medium">
                    {{ count($person['memberships']) }} {{ count($person['memberships']) === 1 ? 'centre' : 'centres' }}
                </span>
            </div>
            <table class="min-w-full divide-y divide-slate-100">
                <thead>
                    <tr>
                        <th class="px-6 py-2 text-left text-xs font-medium text-slate-400 uppercase">Medical Centre</th>
                        <th class="px-6 py-2 text-left text-xs font-medium text-slate-400 uppercase">Username</th>
                        <th class="px-6 py-2 text-left text-xs font-medium text-slate-400 uppercase">Role</th>
                        <th class="px-6 py-2 text-left text-xs font-medium text-slate-400 uppercase">Status</th>
                        <th class="px-6 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($person['memberships'] as $m)
                    @php $isAdmin = $m['user']->roles->contains('slug', 'tenant.admin'); @endphp
                    <tr class="hover:bg-slate-50 {{ $isAdmin ? 'bg-blue-50' : '' }}">
                        <td class="px-6 py-3 text-sm font-medium text-slate-800">
                            <a href="{{ route('platform.tenants.show', $m['tenant']->uuid) }}" class="hover:underline">
                                {{ $m['tenant']->name }}
                            </a>
                            @if($isAdmin)
                                <span class="ml-1.5 inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">Admin</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-sm font-mono text-slate-600">{{ $m['user']->username }}</td>
                        <td class="px-6 py-3 text-sm text-slate-600">
                            {{ $m['user']->roles->pluck('name')->join(', ') ?: '—' }}
                        </td>
                        <td class="px-6 py-3 text-sm">
                            <span class="{{ $m['user']->is_active ? 'text-green-600' : 'text-red-500' }}">
                                {{ $m['user']->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-right">
                            <a href="{{ route('platform.tenants.users.edit', [$m['tenant']->uuid, $m['user']->id]) }}"
                                class="text-xs text-slate-500 hover:text-slate-800">Edit</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @empty
        <div class="bg-white rounded-lg border border-slate-200 px-6 py-10 text-center text-sm text-slate-400">
            No users found.
        </div>
        @endforelse
    </div>
</div>
