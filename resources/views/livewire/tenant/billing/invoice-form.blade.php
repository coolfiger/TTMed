<div class="p-6 space-y-6">

    {{-- Messages --}}
    @if($successMessage)
        <div class="p-3 bg-green-50 border border-green-200 text-green-800 rounded text-sm">{{ $successMessage }}</div>
    @endif
    @if($errorMessage)
        <div class="p-3 bg-red-50 border border-red-200 text-red-800 rounded text-sm">{{ $errorMessage }}</div>
    @endif

    {{-- Patient header --}}
    @if($patient)
        <div class="text-sm text-gray-600">
            Patient: <span class="font-semibold text-gray-900">{{ $patient->full_name }}</span>
            &nbsp;·&nbsp; {{ $patient->patient_number }}
        </div>
    @endif

    {{-- No invoice yet — create form --}}
    @if(!$invoice)
        <div class="bg-white border rounded-lg p-5 space-y-4">
            <h2 class="font-semibold text-gray-800">New Invoice</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea wire:model="notes" rows="2"
                    class="w-full border-gray-300 rounded-md shadow-sm text-sm"></textarea>
            </div>

            <button wire:click="createInvoice"
                class="px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                Create Invoice
            </button>
        </div>
    @else
        {{-- Invoice header --}}
        <div class="bg-white border rounded-lg p-5 flex items-start justify-between">
            <div>
                <div class="text-lg font-bold text-gray-900">{{ $invoice->invoice_number }}</div>
                <span class="inline-block px-2 py-0.5 rounded text-xs font-medium
                    bg-{{ $invoice->status_color }}-100 text-{{ $invoice->status_color }}-800">
                    {{ $invoice->status_label }}
                </span>
            </div>
            <div class="text-right text-sm text-gray-600 space-y-1">
                <div>Subtotal: <strong>{{ '$' . number_format($invoice->subtotal / 100, 2) }}</strong></div>
                <div>Tax: <strong>{{ '$' . number_format($invoice->tax_total / 100, 2) }}</strong></div>
                @if($invoice->discount > 0)
                    <div>Discount: <strong class="text-red-600">-{{ '$' . number_format($invoice->discount / 100, 2) }}</strong></div>
                @endif
                <div class="text-base font-bold text-gray-900 border-t pt-1">Total: {{ $invoice->total_formatted }}</div>
                <div class="text-green-700">Paid: {{ $invoice->amount_paid_formatted }}</div>
                <div class="text-orange-700">Due: {{ $invoice->amount_due_formatted }}</div>
            </div>
        </div>

        {{-- Line items --}}
        <div class="bg-white border rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Description</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Qty</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Unit Price</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Tax</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Total</th>
                        @if($invoice->isDraft())
                            <th class="px-4 py-2"></th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($items as $item)
                        <tr>
                            <td class="px-4 py-2 text-gray-900">{{ $item->description }}</td>
                            <td class="px-4 py-2 text-right">{{ $item->quantity }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format($item->unit_price / 100, 2) }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format($item->tax_amount / 100, 2) }}</td>
                            <td class="px-4 py-2 text-right font-medium">{{ $item->line_total_formatted }}</td>
                            @if($invoice->isDraft())
                                <td class="px-4 py-2">
                                    <button wire:click="removeItem({{ $item->id }})"
                                        wire:confirm="Remove this item?"
                                        class="text-red-500 hover:text-red-700 text-xs">Remove</button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-400 text-sm">No items yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoice->isDraft())
            {{-- Add item form --}}
            <div class="bg-white border rounded-lg p-5 space-y-4">
                <h3 class="font-semibold text-gray-800">Add Item</h3>

                {{-- Service search --}}
                <div x-data="{ open: false }" class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search Service</label>
                    <input type="text"
                        wire:model.live.debounce.300ms="serviceSearch"
                        wire:focus="$set('serviceResults', [])"
                        @focus="open = true" @blur="setTimeout(() => open = false, 200)"
                        placeholder="Type service name..."
                        class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                    <div x-show="open && $wire.serviceResults.length > 0"
                        class="absolute z-10 w-full mt-1 bg-white border rounded-md shadow-lg max-h-48 overflow-y-auto">
                        @foreach($serviceResults as $svc)
                            <button type="button"
                                wire:click="selectService({{ $svc['id'] }})"
                                class="w-full text-left px-4 py-2 text-sm hover:bg-blue-50 border-b last:border-0">
                                <span class="font-medium">{{ $svc['name'] }}</span>
                                <span class="text-gray-500 ml-2 text-xs">{{ $svc['code'] }}</span>
                                <span class="text-gray-700 ml-2">${{ number_format($svc['price'] / 100, 2) }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                        <input type="text" wire:model="itemDescription"
                            class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Qty *</label>
                        <input type="number" wire:model="itemQuantity" min="1"
                            class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Price (cents) *</label>
                        <input type="number" wire:model="itemUnitPrice" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tax Rate %</label>
                        <input type="number" wire:model="itemTaxRate" min="0" max="100" step="0.01"
                            class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount (cents)</label>
                        <input type="number" wire:model="itemDiscount" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                    </div>
                </div>

                <button wire:click="addItem"
                    class="px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                    Add Item
                </button>
            </div>

            {{-- Discount --}}
            <div class="bg-white border rounded-lg p-5 space-y-4">
                <h3 class="font-semibold text-gray-800">Invoice Discount</h3>
                <div class="flex gap-3 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount (cents)</label>
                        <input type="number" wire:model="discountCents" min="0"
                            class="w-48 border-gray-300 rounded-md shadow-sm text-sm" />
                    </div>
                    <button wire:click="applyDiscount"
                        class="px-4 py-2 bg-gray-600 text-white text-sm rounded hover:bg-gray-700">
                        Apply
                    </button>
                </div>
            </div>

            {{-- Finalize --}}
            @if($items->count() > 0)
                <div class="flex gap-3">
                    <button wire:click="finalizeInvoice"
                        wire:confirm="Finalize this invoice? It cannot be edited after finalization."
                        class="px-5 py-2 bg-green-600 text-white text-sm font-medium rounded hover:bg-green-700">
                        Finalize Invoice
                    </button>
                    <button wire:click="cancelInvoice(prompt('Cancellation reason:'))"
                        class="px-5 py-2 bg-red-100 text-red-700 text-sm font-medium rounded hover:bg-red-200">
                        Cancel Invoice
                    </button>
                </div>
            @endif
        @endif
    @endif
</div>
