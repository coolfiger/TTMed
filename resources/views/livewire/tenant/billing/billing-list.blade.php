<div>
    <div class="flex gap-3 mb-6 justify-between items-end flex-wrap">
        <div class="flex gap-3 flex-wrap">
            <input wire:model.live.debounce.300ms="search" type="search"
                placeholder="Invoice # or patient…"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm w-64 focus:border-blue-500 focus:outline-none">
            <select wire:model.live="statusFilter"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                <option value="">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="finalized">Finalized</option>
                <option value="partially_paid">Partially Paid</option>
                <option value="paid">Paid</option>
                <option value="cancelled">Cancelled</option>
                <option value="void">Void</option>
            </select>
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
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Invoice #</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Patient</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Branch</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Total</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Balance</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Date</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($invoices as $invoice)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 text-sm font-mono text-slate-700">{{ $invoice->invoice_number }}</td>
                    <td class="px-4 py-3 text-sm text-slate-800">
                        <a href="{{ route('tenant.patients.show', $invoice->patient_id) }}" class="hover:text-blue-600">
                            {{ $invoice->patient->full_name ?? '—' }}
                        </a>
                        <div class="text-xs text-slate-400">{{ $invoice->patient->patient_number ?? '' }}</div>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-500">{{ $invoice->branch->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-slate-700 text-right">{{ $invoice->formattedTotal }}</td>
                    <td class="px-4 py-3 text-sm text-right {{ $invoice->amount_due > 0 ? 'text-red-600' : 'text-slate-500' }}">
                        ${{ number_format($invoice->amount_due / 100, 2) }}
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $invoice->statusBadgeClass }}">
                            {{ $invoice->statusLabel }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-500">{{ $invoice->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-sm">
                        @if($invoice->isPayable())
                        <a href="{{ route('tenant.billing.invoice.edit', [$invoice->patient_id, $invoice->id]) }}"
                            class="text-blue-600 hover:text-blue-800 text-xs">View</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-400">No invoices found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $invoices->links() }}</div>
</div>
