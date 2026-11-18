<div>
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search…"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none w-64">
            <select wire:model.live="statusFilter"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="trial">Trial</option>
                <option value="suspended">Suspended</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
        <a href="{{ route('platform.tenants.create') }}"
            class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
            New Medical Centre
        </a>
    </div>

    <div class="bg-white rounded-lg border border-slate-200">
        <table class="min-w-full divide-y divide-slate-100">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Plan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Expires</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($tenants as $tenant)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-3 text-sm font-medium text-slate-800">
                        <a href="{{ route('platform.tenants.show', $tenant->uuid) }}" class="hover:underline">
                            {{ $tenant->name }}
                        </a>
                    </td>
                    <td class="px-6 py-3 text-sm font-mono text-slate-600">{{ $tenant->code }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $tenant->subscription?->plan?->name ?? '—' }}</td>
                    <td class="px-6 py-3">
                        <select wire:change="updateStatus('{{ $tenant->uuid }}', $event.target.value)"
                            class="rounded border border-slate-200 px-2 py-1 text-xs text-slate-700 focus:outline-none">
                            @foreach(['active','trial','suspended','cancelled'] as $s)
                            <option value="{{ $s }}" @selected($tenant->status === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="px-6 py-3 text-sm text-slate-500">
                        {{ $tenant->subscription?->expires_at?->format('d M Y') ?? '—' }}
                    </td>
                    <td class="px-6 py-3 text-right">
                        <a href="{{ route('platform.tenants.edit', $tenant->uuid) }}"
                            class="text-xs text-slate-500 hover:text-slate-800">Edit</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-slate-400">No results.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-3 border-t border-slate-100">
            {{ $tenants->links() }}
        </div>
    </div>
</div>
