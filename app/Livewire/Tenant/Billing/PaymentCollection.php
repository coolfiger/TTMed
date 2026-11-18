<?php

namespace App\Livewire\Tenant\Billing;

use App\Models\Tenant\CashierShift;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Payment;
use App\Services\BillingService;
use Livewire\Component;

class PaymentCollection extends Component
{
    private BillingService $billingService;

    public int    $invoiceId       = 0;
    public ?Invoice $invoice       = null;

    // Payment form
    public int    $amountCents     = 0;
    public string $method          = 'cash';
    public string $referenceNumber = '';
    public string $paymentNotes    = '';

    public string $successMessage  = '';
    public string $errorMessage    = '';

    public function boot(BillingService $billingService): void
    {
        $this->billingService = $billingService;
    }

    public function mount(int $invoiceId): void
    {
        $this->checkPermission('payments.collect');

        $user = auth()->guard('tenant')->user();

        $this->invoiceId = $invoiceId;
        $this->invoice   = Invoice::on('tenant')
            ->where('id', $invoiceId)
            ->where('branch_id', $user->branch_id ?? 0)
            ->firstOrFail();

        // Pre-fill amount with what is due
        $this->amountCents = $this->invoice->amount_due;
    }

    public function collectPayment(): void
    {
        $this->checkPermission('payments.collect');

        $this->validate([
            'amountCents'     => 'required|integer|min:1',
            'method'          => 'required|in:cash,card,linx,cheque,insurance,waiver,other',
            'referenceNumber' => 'nullable|string|max:100',
            'paymentNotes'    => 'nullable|string|max:300',
        ]);

        $user = auth()->guard('tenant')->user();

        // Find the cashier's open shift for this branch if collecting cash/card/linx
        $shiftId = null;
        if (in_array($this->method, ['cash', 'card', 'linx'])) {
            $shift = CashierShift::on('tenant')
                ->where('cashier_id', $user->id)
                ->where('branch_id', $user->branch_id ?? 0)
                ->where('status', 'open')
                ->first();

            $shiftId = $shift?->id;
        }

        try {
            $this->billingService->collectPayment(
                invoice:         $this->invoice,
                amountCents:     $this->amountCents,
                method:          $this->method,
                collectedBy:     $user->id,
                shiftId:         $shiftId,
                referenceNumber: $this->referenceNumber ?: null,
                notes:           $this->paymentNotes ?: null,
            );

            $this->invoice->refresh();
            $this->amountCents     = $this->invoice->amount_due;
            $this->referenceNumber = '';
            $this->paymentNotes    = '';
            $this->successMessage  = 'Payment recorded.';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function voidPayment(int $paymentId, string $reason): void
    {
        $this->checkPermission('payments.refund');

        $payment = Payment::on('tenant')
            ->where('id', $paymentId)
            ->where('invoice_id', $this->invoiceId)
            ->firstOrFail();

        try {
            $this->billingService->voidPayment(
                $payment,
                auth()->guard('tenant')->id(),
                $reason,
            );

            $this->invoice->refresh();
            $this->amountCents    = $this->invoice->amount_due;
            $this->successMessage = 'Payment voided.';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    private function checkPermission(string $permission): void
    {
        if (!auth()->guard('tenant')->user()?->hasPermission($permission)) {
            abort(403);
        }
    }

    public function render()
    {
        $payments = Payment::on('tenant')
            ->where('invoice_id', $this->invoiceId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('livewire.tenant.billing.payment-collection', [
            'payments' => $payments,
        ]);
    }
}
