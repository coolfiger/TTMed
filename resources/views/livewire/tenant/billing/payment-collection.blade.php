<div class="p-6 space-y-6">

    @if($successMessage)
        <div class="p-3 bg-green-50 border border-green-200 text-green-800 rounded text-sm">{{ $successMessage }}</div>
    @endif
    @if($errorMessage)
        <div class="p-3 bg-red-50 border border-red-200 text-red-800 rounded text-sm">{{ $errorMessage }}</div>
    @endif

    @if($invoice)
        {{-- Invoice summary --}}
        <div class="bg-white border rounded-lg p-5">
            <div class="flex justify-between items-start">
                <div>
                    <div class="font-bold text-gray-900">{{ $invoice->invoice_number }}</div>
                    <span class="inline-block px-2 py-0.5 rounded text-xs font-medium
                        bg-{{ $invoice->status_color }}-100 text-{{ $invoice->status_color }}-800 mt-1">
                        {{ $invoice->status_label }}
                    </span>
                </div>
                <div class="text-right text-sm space-y-1">
                    <div>Total: <strong>{{ $invoice->total_formatted }}</strong></div>
                    <div class="text-green-700">Paid: {{ $invoice->amount_paid_formatted }}</div>
                    <div class="text-orange-700 font-semibold text-base">Due: {{ $invoice->amount_due_formatted }}</div>
                </div>
            </div>
        </div>

        {{-- Payment form --}}
        @if($invoice->isOpen() && $invoice->amount_due > 0)
            <div class="bg-white border rounded-lg p-5 space-y-4">
                <h3 class="font-semibold text-gray-800">Record Payment</h3>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount (cents) *</label>
                        <input type="number" wire:model="amountCents" min="1" max="{{ $invoice->amount_due }}"
                            class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Method *</label>
                        <select wire:model="method" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="linx">LINX</option>
                            <option value="cheque">Cheque</option>
                            <option value="insurance">Insurance</option>
                            <option value="waiver">Waiver</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reference No.</label>
                        <input type="text" wire:model="referenceNumber"
                            class="w-full border-gray-300 rounded-md shadow-sm text-sm"
                            placeholder="Card auth / cheque no." />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <input type="text" wire:model="paymentNotes"
                            class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                    </div>
                </div>

                <button wire:click="collectPayment"
                    class="px-5 py-2 bg-green-600 text-white text-sm font-medium rounded hover:bg-green-700">
                    Record Payment
                </button>
            </div>
        @endif

        {{-- Payment history --}}
        @if($payments->count() > 0)
            <div class="bg-white border rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b bg-gray-50 font-semibold text-sm text-gray-700">
                    Payment History
                </div>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Reference</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Method</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Amount</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($payments as $payment)
                            <tr class="{{ $payment->isVoided() ? 'opacity-50 line-through' : '' }}">
                                <td class="px-4 py-2 font-mono text-xs">{{ $payment->payment_reference }}</td>
                                <td class="px-4 py-2">{{ $payment->method_label }}</td>
                                <td class="px-4 py-2 text-right font-medium">{{ $payment->amount_formatted }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $payment->created_at->format('d M Y H:i') }}</td>
                                <td class="px-4 py-2">
                                    @if($payment->isVoided())
                                        <span class="text-red-600 text-xs">Voided</span>
                                    @else
                                        <span class="text-green-600 text-xs">Valid</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2">
                                    @if(!$payment->isVoided())
                                        <button
                                            wire:click="voidPayment({{ $payment->id }}, prompt('Void reason:'))"
                                            class="text-red-500 hover:text-red-700 text-xs">
                                            Void
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</div>
