<div class="p-6 space-y-6">

    {{-- Header and global filters --}}
    <div class="flex flex-wrap items-end gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Reports</h1>
        </div>
        <div class="ml-auto flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <input type="date" wire:model.live="dateFrom"
                    class="border-gray-300 rounded-md shadow-sm text-sm" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input type="date" wire:model.live="dateTo"
                    class="border-gray-300 rounded-md shadow-sm text-sm" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Branch</label>
                <select wire:model.live="branchId" class="border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="0">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex gap-6">
            @foreach([
                'financial'    => 'Financial Summary',
                'aging'        => 'Invoice Aging',
                'appointments' => 'Appointments',
                'inventory'    => 'Inventory',
                'patients'     => 'Patient Activity',
            ] as $key => $label)
                <button wire:click="setTab('{{ $key }}')"
                    class="pb-3 text-sm font-medium border-b-2 transition-colors
                        {{ $activeTab === $key
                            ? 'border-blue-600 text-blue-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    {{-- ===== FINANCIAL SUMMARY ===== --}}
    @if($activeTab === 'financial')
        <div class="space-y-5">
            {{-- KPI tiles --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @php
                    $tiles = [
                        ['label' => 'Total Invoiced',    'value' => 'TTD ' . number_format($reportData['total_invoiced'] / 100, 2),    'color' => 'blue'],
                        ['label' => 'Collected',         'value' => 'TTD ' . number_format($reportData['total_collected'] / 100, 2),   'color' => 'green'],
                        ['label' => 'Outstanding',       'value' => 'TTD ' . number_format($reportData['total_outstanding'] / 100, 2), 'color' => 'orange'],
                        ['label' => 'Invoices',          'value' => $reportData['invoice_count'],                                      'color' => 'gray'],
                    ];
                @endphp
                @foreach($tiles as $tile)
                    <div class="bg-white border rounded-lg p-4">
                        <div class="text-xs text-gray-500 font-medium uppercase">{{ $tile['label'] }}</div>
                        <div class="mt-1 text-2xl font-bold text-gray-900">{{ $tile['value'] }}</div>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-3 gap-4 text-sm text-center">
                <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                    <div class="text-xs text-green-600 font-medium uppercase">Paid</div>
                    <div class="text-lg font-bold text-green-800">{{ $reportData['paid_count'] }}</div>
                </div>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                    <div class="text-xs text-yellow-600 font-medium uppercase">Partial</div>
                    <div class="text-lg font-bold text-yellow-800">{{ $reportData['partial_count'] }}</div>
                </div>
                <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                    <div class="text-xs text-red-600 font-medium uppercase">Cancelled</div>
                    <div class="text-lg font-bold text-red-800">{{ $reportData['cancelled_count'] }}</div>
                </div>
            </div>

            {{-- Payment method breakdown --}}
            @if($reportData['by_method']->isNotEmpty())
                <div class="bg-white border rounded-lg overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-3 border-b bg-gray-50">
                        <h3 class="font-semibold text-gray-800 text-sm">By Payment Method</h3>
                        <a href="{{ route('tenant.reports.export', 'financial') }}?from={{ $dateFrom }}&to={{ $dateTo }}&branch_id={{ $branchId }}"
                            class="text-xs text-blue-600 hover:underline">Export CSV</a>
                    </div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-2 text-left text-xs text-gray-500 uppercase">Method</th>
                                <th class="px-5 py-2 text-right text-xs text-gray-500 uppercase">Transactions</th>
                                <th class="px-5 py-2 text-right text-xs text-gray-500 uppercase">Amount (TTD)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($reportData['by_method'] as $m)
                                <tr>
                                    <td class="px-5 py-2 font-medium capitalize">{{ str_replace('_', ' ', $m->payment_method) }}</td>
                                    <td class="px-5 py-2 text-right text-gray-600">{{ $m->count }}</td>
                                    <td class="px-5 py-2 text-right text-gray-800 font-semibold">{{ number_format($m->total / 100, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-10 text-gray-400 text-sm">No payment data for this period.</div>
            @endif
        </div>

    {{-- ===== INVOICE AGING ===== --}}
    @elseif($activeTab === 'aging')
        <div class="space-y-5">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($reportData['buckets'] as $label => $bucket)
                    <div class="bg-white border rounded-lg p-4">
                        <div class="text-xs text-gray-500 font-medium uppercase">{{ $label }} days</div>
                        <div class="mt-1 text-xl font-bold text-gray-900">{{ $bucket['count'] }}</div>
                        <div class="text-sm text-gray-500">TTD {{ number_format($bucket['total'] / 100, 2) }}</div>
                    </div>
                @endforeach
            </div>

            @if($reportData['rows']->isNotEmpty())
                <div class="bg-white border rounded-lg overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-3 border-b bg-gray-50">
                        <h3 class="font-semibold text-gray-800 text-sm">Outstanding Invoices</h3>
                        <a href="{{ route('tenant.reports.export', 'aging') }}?branch_id={{ $branchId }}"
                            class="text-xs text-blue-600 hover:underline">Export CSV</a>
                    </div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Invoice</th>
                                <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Patient</th>
                                <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Days</th>
                                <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Amount Due (TTD)</th>
                                <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($reportData['rows'] as $row)
                                <tr>
                                    <td class="px-4 py-2 font-mono text-xs text-gray-700">{{ $row->invoice_number }}</td>
                                    <td class="px-4 py-2">{{ $row->first_name }} {{ $row->last_name }}</td>
                                    <td class="px-4 py-2 text-right
                                        {{ $row->age_days > 90 ? 'text-red-700 font-semibold' : ($row->age_days > 60 ? 'text-orange-700' : 'text-gray-700') }}">
                                        {{ $row->age_days }}
                                    </td>
                                    <td class="px-4 py-2 text-right font-semibold text-gray-900">{{ number_format($row->amount_due / 100, 2) }}</td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-0.5 rounded text-xs capitalize
                                            {{ $row->status === 'partially_paid' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800' }}">
                                            {{ str_replace('_', ' ', $row->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-10 text-gray-400 text-sm">No outstanding invoices.</div>
            @endif
        </div>

    {{-- ===== APPOINTMENTS ===== --}}
    @elseif($activeTab === 'appointments')
        <div class="space-y-5">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white border rounded-lg p-4">
                    <div class="text-xs text-gray-500 font-medium uppercase">Total</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $reportData['total'] }}</div>
                </div>
                <div class="bg-white border rounded-lg p-4">
                    <div class="text-xs text-gray-500 font-medium uppercase">Completed</div>
                    <div class="text-2xl font-bold text-green-700">{{ $reportData['completed'] }}</div>
                </div>
                <div class="bg-white border rounded-lg p-4">
                    <div class="text-xs text-gray-500 font-medium uppercase">No-Show</div>
                    <div class="text-2xl font-bold text-red-700">{{ $reportData['no_show'] }}</div>
                </div>
                <div class="bg-white border rounded-lg p-4">
                    <div class="text-xs text-gray-500 font-medium uppercase">No-Show Rate</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $reportData['no_show_rate'] }}%</div>
                </div>
            </div>

            @if($reportData['by_doctor']->isNotEmpty())
                <div class="bg-white border rounded-lg overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-3 border-b bg-gray-50">
                        <h3 class="font-semibold text-gray-800 text-sm">By Doctor</h3>
                        <a href="{{ route('tenant.reports.export', 'appointments') }}?from={{ $dateFrom }}&to={{ $dateTo }}&branch_id={{ $branchId }}"
                            class="text-xs text-blue-600 hover:underline">Export CSV</a>
                    </div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-2 text-left text-xs text-gray-500 uppercase">Doctor</th>
                                <th class="px-5 py-2 text-right text-xs text-gray-500 uppercase">Total</th>
                                <th class="px-5 py-2 text-right text-xs text-gray-500 uppercase">Completed</th>
                                <th class="px-5 py-2 text-right text-xs text-gray-500 uppercase">No-Show</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($reportData['by_doctor'] as $d)
                                <tr>
                                    <td class="px-5 py-2 font-medium text-gray-900">{{ $d->first_name }} {{ $d->last_name }}</td>
                                    <td class="px-5 py-2 text-right text-gray-700">{{ $d->total }}</td>
                                    <td class="px-5 py-2 text-right text-green-700">{{ $d->completed }}</td>
                                    <td class="px-5 py-2 text-right text-red-700">{{ $d->no_show }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-10 text-gray-400 text-sm">No appointment data for this period.</div>
            @endif
        </div>

    {{-- ===== INVENTORY ===== --}}
    @elseif($activeTab === 'inventory')
        <div class="space-y-5">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white border rounded-lg p-4">
                    <div class="text-xs text-gray-500 font-medium uppercase">Active Items</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $reportData['total_items'] }}</div>
                </div>
                <div class="bg-white border rounded-lg p-4">
                    <div class="text-xs text-gray-500 font-medium uppercase">Low Stock</div>
                    <div class="text-2xl font-bold text-orange-700">{{ $reportData['low_stock_count'] }}</div>
                </div>
                <div class="bg-white border rounded-lg p-4">
                    <div class="text-xs text-gray-500 font-medium uppercase">Out of Stock</div>
                    <div class="text-2xl font-bold text-red-700">{{ $reportData['out_of_stock_count'] }}</div>
                </div>
                <div class="bg-white border rounded-lg p-4">
                    <div class="text-xs text-gray-500 font-medium uppercase">Stock Value (TTD)</div>
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($reportData['stock_value_cents'] / 100, 2) }}</div>
                </div>
            </div>

            @if($reportData['low_stock_items']->isNotEmpty())
                <div class="bg-white border rounded-lg overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-3 border-b bg-gray-50">
                        <h3 class="font-semibold text-gray-800 text-sm">Low / Out of Stock</h3>
                        <a href="{{ route('tenant.reports.export', 'inventory') }}?branch_id={{ $branchId }}"
                            class="text-xs text-blue-600 hover:underline">Export CSV</a>
                    </div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Item</th>
                                <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Category</th>
                                <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">On Hand</th>
                                <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Reorder At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($reportData['low_stock_items'] as $item)
                                <tr class="{{ $item->quantity_on_hand === 0 ? 'bg-red-50' : '' }}">
                                    <td class="px-4 py-2">
                                        <div class="font-medium text-gray-900">{{ $item->name }}</div>
                                        <div class="text-gray-400 text-xs">{{ $item->code }}</div>
                                    </td>
                                    <td class="px-4 py-2 text-gray-600">{{ $item->category_name ?? '—' }}</td>
                                    <td class="px-4 py-2 text-right font-semibold
                                        {{ $item->quantity_on_hand === 0 ? 'text-red-700' : 'text-orange-700' }}">
                                        {{ $item->quantity_on_hand }} {{ $item->unit }}
                                    </td>
                                    <td class="px-4 py-2 text-right text-gray-600">{{ $item->reorder_level }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($reportData['top_dispensed']->isNotEmpty())
                <div class="bg-white border rounded-lg overflow-hidden">
                    <div class="px-5 py-3 border-b bg-gray-50">
                        <h3 class="font-semibold text-gray-800 text-sm">Top 10 Dispensed (Period)</h3>
                    </div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Item</th>
                                <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Total Dispensed</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($reportData['top_dispensed'] as $item)
                                <tr>
                                    <td class="px-4 py-2 font-medium text-gray-900">{{ $item->name }}
                                        <span class="text-gray-400 text-xs ml-1">{{ $item->code }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-right text-gray-700">{{ $item->total_dispensed }} {{ $item->unit }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    {{-- ===== PATIENT ACTIVITY ===== --}}
    @elseif($activeTab === 'patients')
        <div class="space-y-5">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div class="bg-white border rounded-lg p-4">
                    <div class="text-xs text-gray-500 font-medium uppercase">New Patients</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $reportData['new_patients'] }}</div>
                </div>
                <div class="bg-white border rounded-lg p-4">
                    <div class="text-xs text-gray-500 font-medium uppercase">Total Encounters</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $reportData['total_encounters'] }}</div>
                </div>
            </div>

            @if($reportData['encounter_by_type']->isNotEmpty())
                <div class="bg-white border rounded-lg overflow-hidden">
                    <div class="px-5 py-3 border-b bg-gray-50">
                        <h3 class="font-semibold text-gray-800 text-sm">Encounters by Type</h3>
                    </div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-2 text-left text-xs text-gray-500 uppercase">Type</th>
                                <th class="px-5 py-2 text-right text-xs text-gray-500 uppercase">Count</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($reportData['encounter_by_type'] as $t)
                                <tr>
                                    <td class="px-5 py-2 capitalize font-medium text-gray-800">{{ str_replace('_', ' ', $t->encounter_type) }}</td>
                                    <td class="px-5 py-2 text-right text-gray-700">{{ $t->count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-10 text-gray-400 text-sm">No encounter data for this period.</div>
            @endif
        </div>
    @endif

</div>
