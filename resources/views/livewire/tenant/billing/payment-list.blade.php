<div>
    <div class="flex gap-3 mb-6 justify-between items-end flex-wrap">
        <div class="flex gap-3 flex-wrap">
            <input wire:model.live.debounce.300ms="search" type="search"
                placeholder="Reference or patient…"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm w-64 focus:border-blue-500 focus:outline-none">
            <select wire:model.live="methodFilter"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                <option value="">All Methods</option>
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="cheque">Cheque</option>
                <option value="insurance">Insurance</option>
                <option value="transfer">Transfer</option>
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
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Reference</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Patient</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Invoice #</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Method</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Amount</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Collected By</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($payments as $payment)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 text-sm font-mono text-slate-700">{{ $payment->payment_reference }}</td>
                    <td class="px-4 py-3 text-sm text-slate-800">
                        <a href="{{ route('tenant.patients.show', $payment->patient_id) }}" class="hover:text-blue-600">
                            {{ $payment->patient->full_name ?? '—' }}
                        </a>
                        <div class="text-xs text-slate-400">{{ $payment->patient->patient_number ?? '' }}</div>
                    </td>
                    <td class="px-4 py-3 text-sm font-mono text-slate-500">{{ $payment->invoice->invoice_number ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-slate-600 capitalize">{{ $payment->payment_method }}</td>
                    <td class="px-4 py-3 text-sm text-slate-800 text-right font-medium">{{ $payment->amount_formatted }}</td>
                    <td class="px-4 py-3 text-sm text-slate-500">{{ $payment->collectedBy->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-slate-500">{{ $payment->created_at->format('d M Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-400">No payments found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $payments->links() }}</div>
</div>
