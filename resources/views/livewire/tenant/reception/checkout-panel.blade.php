<div>
    @if($open)
    {{-- Backdrop --}}
    <div class="fixed inset-0 z-40 bg-black/40" wire:click="close"></div>

    {{-- Slide-over --}}
    <div class="fixed inset-y-0 right-0 z-50 w-full max-w-md bg-white shadow-2xl flex flex-col">

        {{-- Header --}}
        <div class="flex-shrink-0 px-6 py-4 bg-purple-600 flex items-center justify-between">
            <div>
                <p class="text-base font-semibold text-white">Checkout</p>
                <p class="text-sm text-purple-200 mt-0.5">{{ $patientName }}</p>
            </div>
            <button wire:click="close" class="text-purple-200 hover:text-white transition text-2xl leading-none">&times;</button>
        </div>

        @if($paid)
        {{-- Success state --}}
        <div class="flex-1 flex flex-col items-center justify-center p-8 text-center">
            <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <p class="text-lg font-semibold text-slate-800">Payment Complete</p>
            <p class="text-sm text-slate-500 mt-1">{{ $patientName }} has been checked out.</p>
            @if($invoice)
            <p class="text-xs text-slate-400 mt-1">Invoice {{ $invoice->invoice_number }}</p>
            @endif
            <button wire:click="close" class="mt-6 px-5 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition">
                Done
            </button>
        </div>

        @else
        {{-- Invoice summary --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-6">

            @if($invoice)
            {{-- Line items --}}
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Invoice {{ $invoice->invoice_number }}</p>
                <div class="rounded-xl border border-slate-200 overflow-hidden">
                    @foreach($invoice->items as $item)
                    <div class="flex items-center justify-between px-4 py-3 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                        <div class="flex-1 min-w-0 mr-4">
                            <p class="text-sm text-slate-800">{{ $item->description }}</p>
                            @if($item->quantity > 1)
                            <p class="text-xs text-slate-400">× {{ $item->quantity }}</p>
                            @endif
                        </div>
                        <p class="text-sm font-semibold text-slate-800 flex-shrink-0">${{ number_format($item->line_total / 100, 2) }}</p>
                    </div>
                    @endforeach
                    @if($invoice->items->isEmpty())
                    <div class="px-4 py-3 text-sm text-slate-400">No items on invoice.</div>
                    @endif
                </div>
            </div>

            {{-- Totals --}}
            <div class="rounded-xl border border-slate-200 px-4 py-3 space-y-2">
                @if($invoice->tax_total > 0)
                <div class="flex justify-between text-sm text-slate-500">
                    <span>Subtotal</span>
                    <span>${{ number_format($invoice->subtotal / 100, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm text-slate-500">
                    <span>Tax</span>
                    <span>${{ number_format($invoice->tax_total / 100, 2) }}</span>
                </div>
                @endif
                @if($invoice->discount > 0)
                <div class="flex justify-between text-sm text-slate-500">
                    <span>Discount</span>
                    <span>−${{ number_format($invoice->discount / 100, 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between text-sm font-bold text-slate-800 pt-1 border-t border-slate-100">
                    <span>Total</span>
                    <span>${{ number_format($invoice->total / 100, 2) }}</span>
                </div>
                @if($invoice->amount_paid > 0)
                <div class="flex justify-between text-sm text-emerald-600">
                    <span>Paid</span>
                    <span>−${{ number_format($invoice->amount_paid / 100, 2) }}</span>
                </div>
                <div class="flex justify-between text-base font-bold text-purple-700 pt-1 border-t border-slate-100">
                    <span>Balance Due</span>
                    <span>${{ number_format($invoice->amount_due / 100, 2) }}</span>
                </div>
                @endif
            </div>
            @endif

            {{-- Error --}}
            @if($errorMessage)
            <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ $errorMessage }}
            </div>
            @endif

            {{-- Payment form --}}
            @if($invoice && $invoice->amount_due > 0)
            <div class="space-y-4">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Collect Payment</p>

                <div>
                    <label class="form-label">Method</label>
                    <select wire:model="paymentMethod" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500/10">
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
                    <label class="form-label">Amount ($)</label>
                    <input type="number" step="0.01" min="0.01"
                           wire:model="amountInput"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500/10" />
                </div>

                @if(in_array($paymentMethod, ['card', 'linx', 'cheque']))
                <div>
                    <label class="form-label">Reference / Auth No. <span class="text-slate-400 font-normal">(optional)</span></label>
                    <input type="text" wire:model="referenceNumber"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500/10" />
                </div>
                @endif
            </div>
            @endif

        </div>

        {{-- Footer --}}
        <div class="flex-shrink-0 px-6 py-4 border-t border-slate-200 flex gap-3">
            <button wire:click="close" class="flex-1 px-4 py-2 rounded-lg border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition">
                Cancel
            </button>
            @if($invoice && $invoice->amount_due > 0)
            <button wire:click="collectAndClose"
                    wire:loading.attr="disabled"
                    wire:target="collectAndClose"
                    class="flex-1 px-4 py-2 rounded-lg bg-purple-600 text-white text-sm font-semibold hover:bg-purple-700 transition disabled:opacity-50">
                <span wire:loading.remove wire:target="collectAndClose">Collect Payment</span>
                <span wire:loading wire:target="collectAndClose">Processing…</span>
            </button>
            @elseif($invoice && $invoice->amount_due === 0 && !$paid)
            <button wire:click="collectAndClose" class="flex-1 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition">
                Mark Checked Out
            </button>
            @endif
        </div>
        @endif

    </div>
    @endif
</div>
