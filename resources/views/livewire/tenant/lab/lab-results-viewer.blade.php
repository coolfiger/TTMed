<div class="p-6 space-y-4">

    @if($successMessage)
        <div class="p-3 bg-green-50 border border-green-200 text-green-800 rounded text-sm">{{ $successMessage }}</div>
    @endif
    @if($errorMessage)
        <div class="p-3 bg-red-50 border border-red-200 text-red-800 rounded text-sm">{{ $errorMessage }}</div>
    @endif

    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-gray-800">Lab Results History</h2>
        <select wire:model.live="filterStatus" class="border-gray-300 rounded-md shadow-sm text-sm">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="specimen_collected">Specimen Collected</option>
            <option value="sent_to_lab">Sent to Lab</option>
            <option value="processing">Processing</option>
            <option value="results_available">Results Available</option>
            <option value="results_reviewed">Reviewed</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>

    @forelse($orders as $order)
        <div class="bg-white border rounded-lg overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 bg-gray-50 border-b">
                <div class="flex items-center gap-4">
                    <span class="font-semibold text-gray-900 text-sm">{{ $order->order_number }}</span>
                    <span class="px-2 py-0.5 rounded text-xs font-medium
                        @if(in_array($order->status, ['results_available']))
                            bg-green-100 text-green-800
                        @elseif(in_array($order->status, ['results_reviewed']))
                            bg-gray-100 text-gray-700
                        @elseif($order->status === 'cancelled')
                            bg-red-100 text-red-700
                        @else
                            bg-blue-100 text-blue-800
                        @endif">
                        {{ $order->status_label }}
                    </span>
                    <span class="text-xs text-gray-400">{{ $order->ordered_at?->format('M d, Y') }}</span>
                </div>
                @if($order->status === 'results_available')
                    <button wire:click="acknowledgeResults({{ $order->id }})"
                        class="px-4 py-1.5 bg-green-600 text-white text-xs font-medium rounded hover:bg-green-700">
                        Mark Reviewed
                    </button>
                @endif
            </div>

            @if($order->items->isNotEmpty())
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase font-medium">Test</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase font-medium">Result</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase font-medium">Reference</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase font-medium">Flag</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($order->items as $item)
                            @php $result = $order->results->firstWhere('lab_order_item_id', $item->id); @endphp
                            <tr class="{{ $result?->is_critical ? 'bg-red-50' : '' }}">
                                <td class="px-4 py-2 text-gray-800">{{ $item->test_name }}</td>
                                <td class="px-4 py-2">
                                    @if($result)
                                        <span class="font-medium">{{ $result->value }}</span>
                                        @if($result->value_unit)
                                            <span class="text-gray-500 ml-1 text-xs">{{ $result->value_unit }}</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400 italic text-xs">Pending</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-gray-500 text-xs">
                                    {{ $result?->reference_range ?? '—' }}
                                </td>
                                <td class="px-4 py-2">
                                    @if($result)
                                        <span class="px-2 py-0.5 rounded text-xs font-medium
                                            {{ in_array($result->flag, ['critical_low','critical_high']) ? 'bg-red-100 text-red-800' :
                                               in_array($result->flag, ['low','high','abnormal']) ? 'bg-yellow-100 text-yellow-800' :
                                               'bg-green-100 text-green-700' }}">
                                            {{ ucfirst(str_replace('_', ' ', $result->flag)) }}
                                            @if($result->is_critical) ⚠ @endif
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="px-5 py-3 text-sm text-gray-400 italic">No items.</div>
            @endif
        </div>
    @empty
        <div class="text-center py-12 text-gray-400 text-sm">No lab orders found.</div>
    @endforelse

    <div>{{ $orders->links() }}</div>
</div>
