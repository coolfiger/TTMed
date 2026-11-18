<?php

namespace App\Services;

use App\Models\Tenant\CashierShift;
use App\Models\Tenant\InsuranceClaim;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\InvoiceItem;
use App\Models\Tenant\Payment;
use App\Models\Tenant\Service;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class BillingService
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    // =========================================================================
    // Invoices
    // =========================================================================

    public function createInvoice(
        int     $patientId,
        int     $branchId,
        int     $createdBy,
        ?int    $encounterId           = null,
        ?int    $insuranceProviderId   = null,
        ?string $notes                 = null,
    ): Invoice {
        return DB::connection('tenant')->transaction(function () use (
            $patientId, $branchId, $createdBy, $encounterId, $insuranceProviderId, $notes
        ) {
            $number = $this->nextInvoiceNumber();

            $invoice = Invoice::on('tenant')->create([
                'invoice_number'       => $number,
                'patient_id'           => $patientId,
                'branch_id'            => $branchId,
                'encounter_id'         => $encounterId,
                'insurance_provider_id'=> $insuranceProviderId,
                'status'               => 'draft',
                'subtotal'             => 0,
                'tax_total'            => 0,
                'discount'             => 0,
                'total'                => 0,
                'amount_paid'          => 0,
                'amount_due'           => 0,
                'insurance_portion'    => 0,
                'patient_portion'      => 0,
                'notes'                => $notes,
                'created_by'           => $createdBy,
            ]);

            $this->auditService->logTenant(
                'invoice.created',
                'invoice',
                $invoice->id,
                null,
                null,
                "Invoice {$number} created for patient #{$patientId}",
            );

            return $invoice;
        });
    }

    public function addItem(
        Invoice $invoice,
        ?int    $serviceId,
        string  $description,
        int     $quantity,
        int     $unitPriceCents,
        float   $taxRate    = 0.0,
        int     $discountCents = 0,
    ): InvoiceItem {
        if (!$invoice->isDraft()) {
            throw new LogicException('Cannot add items to a non-draft invoice.');
        }
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }
        if ($unitPriceCents < 0) {
            throw new InvalidArgumentException('Unit price cannot be negative.');
        }

        return DB::connection('tenant')->transaction(function () use (
            $invoice, $serviceId, $description, $quantity, $unitPriceCents, $taxRate, $discountCents
        ) {
            $subtotal  = $quantity * $unitPriceCents;
            $taxAmount = (int) round($subtotal * ($taxRate / 100));
            $lineTotal = $subtotal + $taxAmount - $discountCents;

            if ($lineTotal < 0) {
                $lineTotal = 0;
            }

            $item = InvoiceItem::on('tenant')->create([
                'invoice_id'  => $invoice->id,
                'service_id'  => $serviceId,
                'description' => $description,
                'quantity'    => $quantity,
                'unit_price'  => $unitPriceCents,
                'tax_rate'    => $taxRate,
                'tax_amount'  => $taxAmount,
                'discount'    => $discountCents,
                'line_total'  => $lineTotal,
            ]);

            $this->recalculateTotals($invoice);

            return $item;
        });
    }

    public function addServiceItem(Invoice $invoice, int $serviceId, int $quantity = 1): InvoiceItem
    {
        $service = Service::on('tenant')->findOrFail($serviceId);

        return $this->addItem(
            invoice:        $invoice,
            serviceId:      $service->id,
            description:    $service->name,
            quantity:       $quantity,
            unitPriceCents: $service->price,
            taxRate:        $service->taxable ? (float) $service->tax_rate : 0.0,
        );
    }

    public function removeItem(Invoice $invoice, int $itemId): void
    {
        if (!$invoice->isDraft()) {
            throw new LogicException('Cannot remove items from a non-draft invoice.');
        }

        DB::connection('tenant')->transaction(function () use ($invoice, $itemId) {
            $item = InvoiceItem::on('tenant')
                ->where('invoice_id', $invoice->id)
                ->where('id', $itemId)
                ->firstOrFail();

            $item->delete();
            $this->recalculateTotals($invoice);
        });
    }

    public function applyDiscount(Invoice $invoice, int $discountCents): void
    {
        if (!$invoice->isDraft()) {
            throw new LogicException('Cannot modify a non-draft invoice.');
        }
        if ($discountCents < 0) {
            throw new InvalidArgumentException('Discount cannot be negative.');
        }
        if ($discountCents > $invoice->subtotal + $invoice->tax_total) {
            throw new InvalidArgumentException('Discount cannot exceed the invoice total.');
        }

        DB::connection('tenant')->transaction(function () use ($invoice, $discountCents) {
            $invoice->discount  = $discountCents;
            $invoice->total     = max(0, $invoice->subtotal + $invoice->tax_total - $discountCents);
            $invoice->amount_due = max(0, $invoice->total - $invoice->amount_paid);
            $this->splitPortions($invoice);
            $invoice->save();
        });
    }

    public function finalizeInvoice(Invoice $invoice, int $finalizedBy): void
    {
        if (!$invoice->isDraft()) {
            throw new LogicException('Only draft invoices can be finalized.');
        }

        $items = InvoiceItem::on('tenant')->where('invoice_id', $invoice->id)->count();
        if ($items === 0) {
            throw new LogicException('Cannot finalize an invoice with no items.');
        }

        DB::connection('tenant')->transaction(function () use ($invoice, $finalizedBy) {
            $invoice->status       = 'finalized';
            $invoice->finalized_by = $finalizedBy;
            $invoice->finalized_at = now();
            $invoice->save();

            $this->auditService->logTenant(
                'invoice.finalized',
                'invoice',
                $invoice->id,
                null,
                null,
                "Invoice {$invoice->invoice_number} finalized. Total: {$invoice->total}c",
            );
        });
    }

    public function cancelInvoice(Invoice $invoice, int $cancelledBy, string $reason): void
    {
        if ($invoice->isCancelled() || $invoice->isVoid()) {
            throw new LogicException('Invoice is already cancelled.');
        }
        if ($invoice->amount_paid > 0) {
            throw new LogicException('Cannot cancel an invoice with recorded payments. Void it instead.');
        }

        DB::connection('tenant')->transaction(function () use ($invoice, $cancelledBy, $reason) {
            $invoice->status               = 'cancelled';
            $invoice->cancelled_by         = $cancelledBy;
            $invoice->cancelled_at         = now();
            $invoice->cancellation_reason  = $reason;
            $invoice->save();

            $this->auditService->logTenant(
                'invoice.cancelled',
                'invoice',
                $invoice->id,
                null,
                null,
                "Invoice {$invoice->invoice_number} cancelled: {$reason}",
            );
        });
    }

    // =========================================================================
    // Payments
    // =========================================================================

    public function collectPayment(
        Invoice $invoice,
        int     $amountCents,
        string  $method,
        int     $collectedBy,
        ?int    $shiftId            = null,
        ?string $referenceNumber    = null,
        ?string $notes              = null,
    ): Payment {
        if (!in_array($invoice->status, ['finalized', 'partially_paid'])) {
            throw new LogicException('Payment can only be collected on finalized or partially-paid invoices.');
        }
        if ($amountCents <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }
        if ($amountCents > $invoice->amount_due) {
            throw new InvalidArgumentException('Payment amount exceeds the amount due.');
        }

        $allowed = ['cash', 'card', 'linx', 'cheque', 'insurance', 'waiver', 'other'];
        if (!in_array($method, $allowed)) {
            throw new InvalidArgumentException("Invalid payment method: {$method}.");
        }

        return DB::connection('tenant')->transaction(function () use (
            $invoice, $amountCents, $method, $collectedBy, $shiftId, $referenceNumber, $notes
        ) {
            $reference = $this->nextPaymentReference();

            $payment = Payment::on('tenant')->create([
                'payment_reference' => $reference,
                'invoice_id'        => $invoice->id,
                'patient_id'        => $invoice->patient_id,
                'branch_id'         => $invoice->branch_id,
                'cashier_shift_id'  => $shiftId,
                'payment_method'    => $method,
                'amount'            => $amountCents,
                'reference_number'  => $referenceNumber,
                'notes'             => $notes,
                'collected_by'      => $collectedBy,
                'voided'            => false,
            ]);

            $invoice->amount_paid += $amountCents;
            $invoice->amount_due   = max(0, $invoice->total - $invoice->amount_paid);
            $invoice->status       = $invoice->amount_due === 0 ? 'paid' : 'partially_paid';
            $invoice->save();

            $this->auditService->logTenant(
                'payment.collected',
                'payment',
                $payment->id,
                null,
                null,
                "Payment {$reference} of {$amountCents}c via {$method} on invoice {$invoice->invoice_number}",
            );

            return $payment;
        });
    }

    public function voidPayment(Payment $payment, int $voidedBy, string $reason): void
    {
        if ($payment->isVoided()) {
            throw new LogicException('Payment is already voided.');
        }

        DB::connection('tenant')->transaction(function () use ($payment, $voidedBy, $reason) {
            $invoice = Invoice::on('tenant')->lockForUpdate()->find($payment->invoice_id);

            $payment->voided    = true;
            $payment->voided_by = $voidedBy;
            $payment->voided_at = now();
            $payment->void_reason = $reason;
            $payment->save();

            if ($invoice) {
                $invoice->amount_paid = max(0, $invoice->amount_paid - $payment->amount);
                $invoice->amount_due  = max(0, $invoice->total - $invoice->amount_paid);

                if ($invoice->isPaid() || $invoice->isPartiallyPaid()) {
                    $invoice->status = $invoice->amount_paid > 0 ? 'partially_paid' : 'finalized';
                }

                $invoice->save();
            }

            $this->auditService->logTenant(
                'payment.voided',
                'payment',
                $payment->id,
                null,
                null,
                "Payment {$payment->payment_reference} voided: {$reason}",
            );
        });
    }

    // =========================================================================
    // Insurance claims
    // =========================================================================

    public function attachInsuranceClaim(
        Invoice $invoice,
        int     $insuranceProviderId,
        int     $claimedAmountCents,
        ?string $claimReference = null,
    ): InsuranceClaim {
        if ($invoice->isDraft()) {
            throw new LogicException('Finalize the invoice before attaching an insurance claim.');
        }

        if (InsuranceClaim::on('tenant')->where('invoice_id', $invoice->id)->exists()) {
            throw new LogicException('An insurance claim already exists for this invoice.');
        }

        return InsuranceClaim::on('tenant')->create([
            'invoice_id'           => $invoice->id,
            'insurance_provider_id'=> $insuranceProviderId,
            'claim_reference'      => $claimReference,
            'status'               => 'pending',
            'claimed_amount'       => $claimedAmountCents,
        ]);
    }

    public function updateClaimStatus(
        InsuranceClaim $claim,
        string         $status,
        int            $paidAmountCents = 0,
    ): void {
        $allowed = ['pending', 'submitted', 'approved', 'partial', 'rejected', 'paid'];
        if (!in_array($status, $allowed)) {
            throw new InvalidArgumentException("Invalid claim status: {$status}.");
        }

        $claim->status      = $status;
        $claim->paid_amount = $paidAmountCents;

        if ($status === 'paid' || $status === 'partial') {
            $claim->paid_date = now()->toDateString();
        }

        $claim->save();
    }

    // =========================================================================
    // Cashier shifts
    // =========================================================================

    public function openShift(int $branchId, int $cashierId, int $openingFloatCents): CashierShift
    {
        $existing = CashierShift::on('tenant')
            ->where('branch_id', $branchId)
            ->where('cashier_id', $cashierId)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            throw new LogicException('This cashier already has an open shift at this branch.');
        }

        return CashierShift::on('tenant')->create([
            'branch_id'      => $branchId,
            'cashier_id'     => $cashierId,
            'status'         => 'open',
            'opened_at'      => now(),
            'opening_float'  => $openingFloatCents,
        ]);
    }

    public function closeShift(CashierShift $shift, int $actualCashCents, ?string $notes = null): void
    {
        if (!$shift->isOpen()) {
            throw new LogicException('Shift is not open.');
        }

        DB::connection('tenant')->transaction(function () use ($shift, $actualCashCents, $notes) {
            $cashPayments = Payment::on('tenant')
                ->where('cashier_shift_id', $shift->id)
                ->where('payment_method', 'cash')
                ->where('voided', false)
                ->sum('amount');

            $expectedCash = $shift->opening_float + $cashPayments;
            $variance     = $actualCashCents - $expectedCash;

            $shift->status        = 'closed';
            $shift->closed_at     = now();
            $shift->actual_cash   = $actualCashCents;
            $shift->expected_cash = $expectedCash;
            $shift->closing_float = $actualCashCents;
            $shift->variance      = $variance;
            $shift->notes         = $notes;
            $shift->save();
        });
    }

    public function approveShift(CashierShift $shift, int $approvedBy): void
    {
        if (!$shift->isClosed()) {
            throw new LogicException('Only closed shifts can be approved.');
        }

        $shift->status      = 'approved';
        $shift->approved_by = $approvedBy;
        $shift->approved_at = now();
        $shift->save();

        $this->auditService->logTenant(
            'shift.approved',
            'cashier_shift',
            $shift->id,
            null,
            null,
            "Shift #{$shift->id} approved by user #{$approvedBy}",
        );
    }

    public function getShiftSummary(CashierShift $shift): array
    {
        $payments = Payment::on('tenant')
            ->where('cashier_shift_id', $shift->id)
            ->where('voided', false)
            ->get();

        $byMethod = $payments->groupBy('payment_method')
            ->map(fn ($grp) => $grp->sum('amount'))
            ->toArray();

        return [
            'total_collected' => $payments->sum('amount'),
            'by_method'       => $byMethod,
            'transaction_count' => $payments->count(),
            'expected_cash'   => $shift->expected_cash,
            'actual_cash'     => $shift->actual_cash,
            'variance'        => $shift->variance,
        ];
    }

    // =========================================================================
    // Internal helpers
    // =========================================================================

    private function recalculateTotals(Invoice $invoice): void
    {
        $items = InvoiceItem::on('tenant')->where('invoice_id', $invoice->id)->get();

        $subtotal = $items->sum(fn ($i) => $i->quantity * $i->unit_price);
        $taxTotal = $items->sum('tax_amount');
        $total    = max(0, $subtotal + $taxTotal - $invoice->discount);
        $amountDue = max(0, $total - $invoice->amount_paid);

        $invoice->subtotal  = $subtotal;
        $invoice->tax_total = $taxTotal;
        $invoice->total     = $total;
        $invoice->amount_due = $amountDue;
        $this->splitPortions($invoice);
        $invoice->save();
    }

    private function splitPortions(Invoice $invoice): void
    {
        // If insurance is attached, split based on claimed amount vs patient remainder
        $claim = InsuranceClaim::on('tenant')->where('invoice_id', $invoice->id)->first();

        if ($claim) {
            $insurancePortion        = min($claim->claimed_amount, $invoice->total);
            $invoice->insurance_portion = $insurancePortion;
            $invoice->patient_portion   = max(0, $invoice->total - $insurancePortion);
        } else {
            $invoice->insurance_portion = 0;
            $invoice->patient_portion   = $invoice->total;
        }
    }

    private function nextInvoiceNumber(): string
    {
        $dateKey = now()->format('Ymd');

        $seq = DB::connection('tenant')->table('invoice_number_sequences')
            ->lockForUpdate()
            ->where('date_key', $dateKey)
            ->first();

        if ($seq) {
            $next = $seq->last_number + 1;
            DB::connection('tenant')->table('invoice_number_sequences')
                ->where('date_key', $dateKey)
                ->update(['last_number' => $next]);
        } else {
            $next = 1;
            DB::connection('tenant')->table('invoice_number_sequences')
                ->insert(['date_key' => $dateKey, 'last_number' => 1]);
        }

        return 'INV-' . $dateKey . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    private function nextPaymentReference(): string
    {
        $dateKey = now()->format('Ymd');

        $seq = DB::connection('tenant')->table('payment_reference_sequences')
            ->lockForUpdate()
            ->where('date_key', $dateKey)
            ->first();

        if ($seq) {
            $next = $seq->last_number + 1;
            DB::connection('tenant')->table('payment_reference_sequences')
                ->where('date_key', $dateKey)
                ->update(['last_number' => $next]);
        } else {
            $next = 1;
            DB::connection('tenant')->table('payment_reference_sequences')
                ->insert(['date_key' => $dateKey, 'last_number' => 1]);
        }

        return 'PAY-' . $dateKey . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
