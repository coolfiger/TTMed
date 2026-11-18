<div>
    <div class="flex justify-end mb-6">
        <a href="{{ route('platform.plans.create') }}"
            class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
            New Plan
        </a>
    </div>

    <div class="bg-white rounded-lg border border-slate-200">
        <table class="min-w-full divide-y divide-slate-100">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Plan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Users</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Storage</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Monthly (TTD)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Annual (TTD)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($plans as $plan)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-3 text-sm font-medium text-slate-800">{{ $plan->name }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $plan->user_limit }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $plan->storage_limit_gb }} GB</td>
                    <td class="px-6 py-3 text-sm text-slate-600">${{ number_format($plan->monthly_price / 100, 2) }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">${{ number_format($plan->annual_price / 100, 2) }}</td>
                    <td class="px-6 py-3 text-sm">
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                            {{ $plan->is_active ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $plan->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-right">
                        <a href="{{ route('platform.plans.edit', $plan->uuid) }}"
                            class="text-xs text-slate-500 hover:text-slate-800">Edit</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-8 text-center text-sm text-slate-400">No plans yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
