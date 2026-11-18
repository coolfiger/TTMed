<div class="p-6 space-y-6">

    @if($successMessage)
        <div class="p-3 bg-green-50 border border-green-200 text-green-800 rounded text-sm">{{ $successMessage }}</div>
    @endif
    @if($errorMessage)
        <div class="p-3 bg-red-50 border border-red-200 text-red-800 rounded text-sm">{{ $errorMessage }}</div>
    @endif

    @if(!$activeShift)
        {{-- Open shift form --}}
        <div class="bg-white border rounded-lg p-5 space-y-4 max-w-sm">
            <h2 class="font-semibold text-gray-800">Open Cashier Shift</h2>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Opening Float (cents)</label>
                <input type="number" wire:model="openingFloat" min="0"
                    class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
            </div>
            <button wire:click="openShift"
                class="px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                Open Shift
            </button>
        </div>
    @else
        {{-- Active shift panel --}}
        <div class="bg-white border rounded-lg p-5 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-gray-800">Active Shift</h2>
                    <div class="text-sm text-gray-500 mt-0.5">
                        Opened {{ $activeShift->opened_at->format('d M Y H:i') }}
                        &nbsp;·&nbsp; Float: ${{ number_format($activeShift->opening_float / 100, 2) }}
                    </div>
                </div>
                <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">Open</span>
            </div>

            @if($summary)
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4 text-sm">
                    <div class="bg-gray-50 rounded p-3">
                        <div class="text-gray-500 text-xs">Transactions</div>
                        <div class="font-bold text-gray-900 text-lg">{{ $summary['transaction_count'] }}</div>
                    </div>
                    <div class="bg-gray-50 rounded p-3">
                        <div class="text-gray-500 text-xs">Total Collected</div>
                        <div class="font-bold text-gray-900 text-lg">${{ number_format($summary['total_collected'] / 100, 2) }}</div>
                    </div>
                    @foreach($summary['by_method'] ?? [] as $method => $amount)
                        <div class="bg-gray-50 rounded p-3">
                            <div class="text-gray-500 text-xs">{{ ucfirst($method) }}</div>
                            <div class="font-bold text-gray-900">${{ number_format($amount / 100, 2) }}</div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Close form --}}
            <div class="border-t pt-4 space-y-4">
                <h3 class="font-medium text-gray-800">Close Shift</h3>
                <div class="grid grid-cols-2 gap-4 max-w-md">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Actual Cash (cents)</label>
                        <input type="number" wire:model="actualCash" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <input type="text" wire:model="closeNotes"
                            class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                    </div>
                </div>
                <button wire:click="closeShift"
                    wire:confirm="Close this shift? This cannot be undone."
                    class="px-4 py-2 bg-orange-600 text-white text-sm rounded hover:bg-orange-700">
                    Close Shift
                </button>
            </div>
        </div>
    @endif

    {{-- Pending approval --}}
    @if($pendingApproval->count() > 0)
        <div class="bg-white border rounded-lg overflow-hidden">
            <div class="px-4 py-3 border-b bg-gray-50 font-semibold text-sm text-gray-700">
                Shifts Awaiting Approval ({{ $pendingApproval->count() }})
            </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Cashier</th>
                        <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Closed</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Expected</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Actual</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Variance</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($pendingApproval as $shift)
                        <tr>
                            <td class="px-4 py-2">{{ $shift->cashier?->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $shift->closed_at?->format('d M H:i') }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format($shift->expected_cash / 100, 2) }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format(($shift->actual_cash ?? 0) / 100, 2) }}</td>
                            <td class="px-4 py-2 text-right {{ ($shift->variance ?? 0) < 0 ? 'text-red-600' : 'text-green-600' }}">
                                {{ $shift->variance_formatted }}
                            </td>
                            <td class="px-4 py-2">
                                <button wire:click="approveShift({{ $shift->id }})"
                                    class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700">
                                    Approve
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Recent shifts --}}
    @if($recentShifts->count() > 0)
        <div class="bg-white border rounded-lg overflow-hidden">
            <div class="px-4 py-3 border-b bg-gray-50 font-semibold text-sm text-gray-700">Recent Shifts</div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Cashier</th>
                        <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Variance</th>
                        <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($recentShifts as $shift)
                        <tr>
                            <td class="px-4 py-2">{{ $shift->cashier?->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $shift->closed_at?->format('d M Y') }}</td>
                            <td class="px-4 py-2 text-right {{ ($shift->variance ?? 0) < 0 ? 'text-red-600' : 'text-green-600' }}">
                                {{ $shift->variance_formatted }}
                            </td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-0.5 rounded text-xs font-medium
                                    {{ $shift->isApproved() ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                    {{ ucfirst($shift->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
