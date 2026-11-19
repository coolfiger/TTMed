<div>
    <div class="flex gap-3 mb-6 justify-between items-end flex-wrap">
        <div class="flex gap-3 flex-wrap">
            <input wire:model.live.debounce.300ms="search" type="search"
                placeholder="Patient name or number…"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm w-64 focus:border-blue-500 focus:outline-none">
            <select wire:model.live="branchFilter"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
            <input wire:model.live="dateFrom" type="date"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
            <input wire:model.live="dateTo" type="date"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
        </div>
    </div>

    <div class="bg-white rounded-lg border border-slate-200">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Rx #</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Patient</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Doctor</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Items</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($prescriptions as $rx)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 text-sm font-mono text-slate-700">{{ $rx->prescription_number }}</td>
                    <td class="px-4 py-3 text-sm text-slate-800">
                        <a href="{{ route('tenant.patients.show', $rx->patient_id) }}" class="hover:text-blue-600">
                            {{ $rx->patient->full_name ?? '—' }}
                        </a>
                        <div class="text-xs text-slate-400">{{ $rx->patient->patient_number ?? '' }}</div>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-600">{{ $rx->doctor->staffProfile->full_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-slate-600">{{ $rx->items->count() }}</td>
                    <td class="px-4 py-3 text-sm text-slate-500">{{ $rx->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-sm">
                        <a href="{{ route('tenant.prescriptions.download', $rx->id) }}"
                            class="text-blue-600 hover:text-blue-800 text-xs">Download</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">No prescriptions found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $prescriptions->links() }}</div>
</div>
