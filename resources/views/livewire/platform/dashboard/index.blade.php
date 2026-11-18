<div>
    <div class="grid grid-cols-2 gap-6 lg:grid-cols-5 mb-8">
        @foreach([
            ['label' => 'Total Tenants',  'value' => $stats['total_tenants'],  'color' => 'slate'],
            ['label' => 'Active',          'value' => $stats['active_tenants'], 'color' => 'green'],
            ['label' => 'Trial',           'value' => $stats['trial'],          'color' => 'yellow'],
            ['label' => 'Suspended',       'value' => $stats['suspended'],      'color' => 'red'],
            ['label' => 'Active Subs',     'value' => $stats['active_subs'],    'color' => 'blue'],
        ] as $stat)
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ $stat['label'] }}</p>
            <p class="mt-1 text-3xl font-semibold text-slate-900">{{ $stat['value'] }}</p>
        </div>
        @endforeach
    </div>

    <div class="bg-white rounded-lg border border-slate-200">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-800">Recent Medical Centres</h2>
            <a href="{{ route('platform.tenants.index') }}" class="text-xs text-slate-500 hover:text-slate-800">View all →</a>
        </div>
        <table class="min-w-full divide-y divide-slate-100">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Plan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($recentTenants as $tenant)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-3 text-sm text-slate-800">
                        <a href="{{ route('platform.tenants.show', $tenant->uuid) }}" class="hover:underline font-medium">
                            {{ $tenant->name }}
                        </a>
                    </td>
                    <td class="px-6 py-3 text-sm font-mono text-slate-600">{{ $tenant->code }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $tenant->subscription?->plan?->name ?? '—' }}</td>
                    <td class="px-6 py-3">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                            {{ match($tenant->status) {
                                'active'    => 'bg-green-50 text-green-700 ring-1 ring-green-200',
                                'trial'     => 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-200',
                                'suspended' => 'bg-red-50 text-red-700 ring-1 ring-red-200',
                                default     => 'bg-slate-100 text-slate-600',
                            } }}">
                            {{ ucfirst($tenant->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-sm text-slate-500">{{ $tenant->created_at->format('d M Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-slate-400">No tenants yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
