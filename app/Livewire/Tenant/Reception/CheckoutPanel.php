<?php

namespace App\Livewire\Tenant\Reception;

use App\Models\Tenant\Invoice;
use App\Models\Tenant\QueueEntry;
use App\Models\Tenant\Service;
use App\Services\BillingService;
use App\Services\QueueService;
use Livewire\Component;

class CheckoutPanel extends Component
{
    protected $listeners = ['open-checkout' => 'openForEvent'];

    public bool   $open         = false;
    public ?int   $queueEntryId = null;
    public string $patientName  = '';

    public ?Invoice $invoice = null;

    public string $paymentMethod  = 'cash';
    public string $amountInput    = '';   // dollars, user types e.g. "150.00"
    public string $referenceNumber = '';
    public string $errorMessage   = '';
    public bool   $paid           = false;

    protected BillingService $billing;
    protected QueueService   $queue;

    public function boot(BillingService $billing, QueueService $queue): void
    {
        $this->billing = $billing;
        $this->queue   = $queue;
    }

    public function openForEvent(array $data): void
    {
        $this->openFor((int) $data['id'], (string) ($data['name'] ?? ''));
    }

    public function openFor(int $queueEntryId, string $patientName): void
    {
        $this->checkPermission('billing.create');

        $this->queueEntryId  = $queueEntryId;
        $this->patientName   = $patientName;
        $this->paymentMethod = 'cash';
        $this->amountInput   = '';
        $this->referenceNumber = '';
        $this->errorMessage  = '';
        $this->paid          = false;

        $entry = $this->loadEntry();
        if (!$entry) {
            return;
        }

        // Find or create draft invoice for this encounter
        $existing = Invoice::on('tenant')
            ->where('encounter_id', $entry->encounter_id)
            ->whereIn('status', ['draft', 'finalized', 'partially_paid'])
            ->latest()
            ->first();

        $user = auth()->guard('tenant')->user();

        if ($existing) {
            $this->invoice = $existing;
        } else {
            $this->invoice = $this->billing->createInvoice(
                patientId:   $entry->patient_id,
                branchId:    $entry->branch_id,
                createdBy:   $user->id,
                encounterId: $entry->encounter_id,
            );

            // Auto-load General Consultation service by visit type
            $visitType = $entry->visit_type ?? 'walk_in';
            $service   = Service::on('tenant')
                ->where('is_active', true)
                ->where(function ($q) use ($visitType) {
                    $q->where('name', 'like', '%consultation%')
                      ->orWhere('name', 'like', '%' . str_replace('_', ' ', $visitType) . '%');
                })
                ->orderByRaw("CASE WHEN name LIKE '%general%' THEN 0 ELSE 1 END")
                ->first();

            if ($service) {
                $this->billing->addItem(
                    invoice:        $this->invoice,
                    serviceId:      $service->id,
                    description:    $service->name,
                    quantity:       1,
                    unitPriceCents: $service->price,
                    taxRate:        $service->taxable ? (float) $service->tax_rate : 0.0,
                    discountCents:  0,
                );
                $this->invoice->refresh();
            }

            // Auto-finalize so payment can be collected immediately
            if ($this->invoice->total > 0) {
                $this->billing->finalizeInvoice($this->invoice, $user->id);
                $this->invoice->refresh();
            }
        }

        $this->amountInput = number_format($this->invoice->amount_due / 100, 2);
        $this->open        = true;
    }

    public function collectAndClose(): void
    {
        $this->checkPermission('billing.create');

        $this->errorMessage = '';

        if (!$this->invoice) {
            $this->errorMessage = 'No invoice found.';
            return;
        }

        $amountCents = (int) round((float) str_replace(',', '', $this->amountInput) * 100);

        if ($amountCents <= 0) {
            $this->errorMessage = 'Enter a valid payment amount.';
            return;
        }

        if ($amountCents > $this->invoice->amount_due) {
            $this->errorMessage = 'Amount exceeds balance due of ' . $this->invoice->formatted_amount_due . '.';
            return;
        }

        $user  = auth()->guard('tenant')->user();
        $entry = $this->loadEntry();

        if (!$entry) {
            $this->errorMessage = 'Queue entry not found.';
            return;
        }

        try {
            $this->billing->collectPayment(
                invoice:         $this->invoice,
                amountCents:     $amountCents,
                method:          $this->paymentMethod,
                collectedBy:     $user->id,
                referenceNumber: $this->referenceNumber ?: null,
            );

            $this->invoice->refresh();

            // Transition queue entry to completed
            if ($this->invoice->amount_due === 0) {
                $this->queue->transition($entry, QueueEntry::STATUS_COMPLETED, $user->id, 'Checkout complete');
                $this->paid = true;
            } else {
                // Partial payment — keep open, show remaining
                $this->amountInput  = number_format($this->invoice->amount_due / 100, 2);
                $this->errorMessage = 'Partial payment recorded. Balance due: ' . $this->invoice->formatted_amount_due;
            }
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function close(): void
    {
        $this->open         = false;
        $this->queueEntryId = null;
        $this->invoice      = null;
        $this->paid         = false;
        $this->errorMessage = '';

        if ($this->paid) {
            $this->dispatch('queue-updated');
        }
    }

    private function loadEntry(): ?QueueEntry
    {
        if (!$this->queueEntryId) {
            return null;
        }

        $user = auth()->guard('tenant')->user();

        return QueueEntry::on('tenant')
            ->where('branch_id', $user->branch_id)
            ->find($this->queueEntryId);
    }

    private function checkPermission(string $perm): void
    {
        if (!auth()->guard('tenant')->user()?->hasPermission($perm)) {
            abort(403);
        }
    }

    public function render()
    {
        return view('livewire.tenant.reception.checkout-panel');
    }
}
