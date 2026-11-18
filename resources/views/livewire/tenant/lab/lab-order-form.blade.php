<div class="p-6 space-y-6">

    @if($successMessage)
        <div class="p-3 bg-green-50 border border-green-200 text-green-800 rounded text-sm">{{ $successMessage }}</div>
    @endif
    @if($errorMessage)
        <div class="p-3 bg-red-50 border border-red-200 text-red-800 rounded text-sm">{{ $errorMessage }}</div>
    @endif

    @if($patient)
        <div class="text-sm text-gray-600">Patient: <span class="font-semibold">{{ $patient->full_name }}</span></div>
    @endif

    @if(!$order)
        {{-- Order form --}}
        <div class="bg-white border rounded-lg p-5 space-y-4">
            <h2 class="font-semibold text-gray-800">New Lab Order</h2>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Order Type</label>
                    <select wire:model="orderType" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="routine">Routine</option>
                        <option value="stat">STAT</option>
                        <option value="fasting">Fasting</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Clinical Notes</label>
                    <input type="text" wire:model="clinicalNotes"
                        class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                </div>
            </div>

            {{-- Test search --}}
            <div x-data="{ open: false }" class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search Tests / Panels</label>
                <input type="text"
                    wire:model.live.debounce.300ms="testSearch"
                    @focus="open = true" @blur="setTimeout(() => open = false, 200)"
                    placeholder="Type test name or code..."
                    class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                <div x-show="open && $wire.testResults.length > 0"
                    class="absolute z-10 w-full mt-1 bg-white border rounded-md shadow-lg max-h-48 overflow-y-auto">
                    @foreach($testResults as $t)
                        <button type="button"
                            wire:click="selectTest({{ $t['id'] }}, '{{ $t['type'] }}', '{{ addslashes($t['name']) }}', '{{ $t['code'] }}')"
                            class="w-full text-left px-4 py-2 text-sm hover:bg-blue-50 border-b last:border-0">
                            <span class="font-medium">{{ $t['name'] }}</span>
                            <span class="ml-2 px-1.5 py-0.5 rounded text-xs
                                {{ $t['type'] === 'panel' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ $t['type'] === 'panel' ? 'Panel' : 'Test' }}
                            </span>
                            @if($t['specimen'])
                                <span class="text-gray-400 ml-2 text-xs">{{ $t['specimen'] }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Selected items --}}
            @if(!empty($selectedItems))
                <div class="border rounded divide-y">
                    @foreach($selectedItems as $i => $item)
                        <div class="flex items-center justify-between px-4 py-2 text-sm">
                            <div>
                                <span class="font-medium">{{ $item['name'] }}</span>
                                <span class="ml-2 text-gray-400 text-xs">{{ $item['code'] }}</span>
                            </div>
                            <button wire:click="removeTest({{ $i }})" class="text-red-500 hover:text-red-700 text-xs">Remove</button>
                        </div>
                    @endforeach
                </div>
            @endif

            <button wire:click="placeOrder"
                class="px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700">
                Place Order
            </button>
        </div>
    @else
        {{-- Active order --}}
        <div class="bg-white border rounded-lg p-5 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="font-bold text-gray-900">{{ $order->order_number }}</div>
                    <span class="inline-block mt-1 px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                        {{ $order->status_label }}
                    </span>
                </div>
                @if($order->status === 'pending')
                    <button wire:click="markCollected"
                        class="px-4 py-2 bg-orange-600 text-white text-sm rounded hover:bg-orange-700">
                        Mark Specimen Collected
                    </button>
                @endif
            </div>

            {{-- Items and results --}}
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Test</th>
                        <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Result</th>
                        <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Flag</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($items as $item)
                        @php $result = $results->firstWhere('lab_order_item_id', $item->id); @endphp
                        <tr>
                            <td class="px-4 py-2 font-medium text-gray-900">{{ $item->test_name }}</td>
                            <td class="px-4 py-2">
                                @if($result)
                                    {{ $result->value }} {{ $result->value_unit }}
                                    @if($result->reference_range)
                                        <span class="text-gray-400 text-xs ml-1">({{ $result->reference_range }})</span>
                                    @endif
                                @else
                                    <span class="text-gray-400 italic">Pending</span>
                                @endif
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
                            <td class="px-4 py-2">
                                @if(!$result && in_array($order->status, ['specimen_collected','sent_to_lab','processing','partial_results']))
                                    <button wire:click="openResultEntry({{ $item->id }})"
                                        class="px-3 py-1 bg-blue-100 text-blue-700 text-xs rounded hover:bg-blue-200">
                                        Enter Result
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Result entry form --}}
            @if($resultItemId)
                <div class="border rounded-lg p-4 bg-blue-50 space-y-3">
                    <h4 class="font-medium text-gray-800 text-sm">Enter Result</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Value *</label>
                            <input type="text" wire:model="resultValue"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Unit</label>
                            <input type="text" wire:model="resultUnit"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Flag *</label>
                            <select wire:model="resultFlag" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                                <option value="normal">Normal</option>
                                <option value="low">Low</option>
                                <option value="high">High</option>
                                <option value="critical_low">Critical Low</option>
                                <option value="critical_high">Critical High</option>
                                <option value="abnormal">Abnormal</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Reference Range</label>
                            <input type="text" wire:model="resultRange"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="resultCritical" class="rounded border-gray-300" />
                        Mark as critical finding
                    </label>
                    <div class="flex gap-2">
                        <button wire:click="saveResult"
                            class="px-4 py-1.5 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                            Save Result
                        </button>
                        <button wire:click="$set('resultItemId', null)"
                            class="px-4 py-1.5 bg-gray-100 text-gray-700 text-sm rounded hover:bg-gray-200">
                            Cancel
                        </button>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
