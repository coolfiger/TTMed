<?php

namespace App\Livewire\Tenant\Billing;

use App\Models\Tenant\Invoice;
use App\Models\Tenant\InvoiceItem;
use App\Models\Tenant\Patient;
use App\Models\Tenant\Service;
use App\Services\BillingService;
use Livewire\Component;

class InvoiceForm extends Component
{
    private BillingService $billingService;

    // Invoice under edit
    public ?int    $invoiceId       = null;
    public ?Invoice $invoice        = null;

    // Create form
    public int     $patientId       = 0;
    public ?int    $encounterId     = null;
    public ?int    $insuranceProviderId = null;
    public string  $notes           = '';

    // Add item form
    public ?int    $selectedServiceId   = null;
    public string  $itemDescription     = '';
    public int     $itemQuantity        = 1;
    public int     $itemUnitPrice       = 0;  // cents
    public float   $itemTaxRate         = 0.0;
    public int     $itemDiscount        = 0;

    // Discount form
    public int     $discountCents       = 0;

    // Service search
    public string  $serviceSearch       = '';
    public array   $serviceResults      = [];

    public string  $successMessage      = '';
    public string  $errorMessage        = '';

    public function boot(BillingService $billingService): void
    {
        $this->billingService = $billingService;
    }

    public function mount(int $patientId, ?int $encounterId = null, ?int $invoiceId = null): void
    {
        $this->checkPermission('billing.view');
        $this->patientId   = $patientId;
        $this->encounterId = $encounterId;
        $this->invoiceId   = $invoiceId;

        if ($invoiceId) {
            $this->invoice = Invoice::on('tenant')
                ->where('id', $invoiceId)
                ->where('branch_id', auth()->guard('tenant')->user()->branch_id ?? 0)
                ->firstOrFail();
        }
    }

    // -------------------------------------------------------------------------
    // Invoice CRUD
    // -------------------------------------------------------------------------

    public function createInvoice(): void
    {
        $this->checkPermission('billing.create');

        $user = auth()->guard('tenant')->user();

        try {
            $this->invoice = $this->billingService->createInvoice(
                patientId:           $this->patientId,
                branchId:            $user->branch_id,
                createdBy:           $user->id,
                encounterId:         $this->encounterId,
                insuranceProviderId: $this->insuranceProviderId ?: null,
                notes:               $this->notes ?: null,
            );
            $this->invoiceId       = $this->invoice->id;
            $this->successMessage  = "Invoice {$this->invoice->invoice_number} created.";
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function searchServices(): void
    {
        if (strlen($this->serviceSearch) < 2) {
            $this->serviceResults = [];
            return;
        }

        $this->serviceResults = Service::on('tenant')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->serviceSearch}%")
                  ->orWhere('code', 'like', "%{$this->serviceSearch}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'code', 'price', 'taxable', 'tax_rate'])
            ->toArray();
    }

    public function selectService(int $serviceId): void
    {
        $service = Service::on('tenant')->find($serviceId);
        if (!$service) {
            return;
        }

        $this->selectedServiceId = $service->id;
        $this->itemDescription   = $service->name;
        $this->itemUnitPrice     = $service->price;
        $this->itemTaxRate       = $service->taxable ? (float) $service->tax_rate : 0.0;
        $this->serviceSearch     = '';
        $this->serviceResults    = [];
    }

    public function addItem(): void
    {
        $this->checkPermission('billing.create');

        if (!$this->invoice || !$this->invoice->isDraft()) {
            $this->errorMessage = 'Invoice is not in draft state.';
            return;
        }

        $this->validate([
            'itemDescription' => 'required|string|max:300',
            'itemQuantity'    => 'required|integer|min:1',
            'itemUnitPrice'   => 'required|integer|min:0',
            'itemTaxRate'     => 'required|numeric|min:0|max:100',
            'itemDiscount'    => 'required|integer|min:0',
        ]);

        try {
            $this->billingService->addItem(
                invoice:        $this->invoice,
                serviceId:      $this->selectedServiceId,
                description:    $this->itemDescription,
                quantity:       $this->itemQuantity,
                unitPriceCents: $this->itemUnitPrice,
                taxRate:        $this->itemTaxRate,
                discountCents:  $this->itemDiscount,
            );

            $this->invoice->refresh();
            $this->resetItemForm();
            $this->successMessage = 'Item added.';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function removeItem(int $itemId): void
    {
        $this->checkPermission('billing.create');

        if (!$this->invoice || !$this->invoice->isDraft()) {
            $this->errorMessage = 'Invoice is not in draft state.';
            return;
        }

        try {
            $this->billingService->removeItem($this->invoice, $itemId);
            $this->invoice->refresh();
            $this->successMessage = 'Item removed.';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function applyDiscount(): void
    {
        $this->checkPermission('billing.create');

        $this->validate(['discountCents' => 'required|integer|min:0']);

        try {
            $this->billingService->applyDiscount($this->invoice, $this->discountCents);
            $this->invoice->refresh();
            $this->successMessage = 'Discount applied.';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function finalizeInvoice(): void
    {
        $this->checkPermission('billing.finalize');

        try {
            $this->billingService->finalizeInvoice(
                $this->invoice,
                auth()->guard('tenant')->id(),
            );
            $this->invoice->refresh();
            $this->successMessage = "Invoice {$this->invoice->invoice_number} finalized.";
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function cancelInvoice(string $reason): void
    {
        $this->checkPermission('billing.cancel');

        try {
            $this->billingService->cancelInvoice(
                $this->invoice,
                auth()->guard('tenant')->id(),
                $reason,
            );
            $this->invoice->refresh();
            $this->successMessage = 'Invoice cancelled.';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resetItemForm(): void
    {
        $this->selectedServiceId = null;
        $this->itemDescription   = '';
        $this->itemQuantity      = 1;
        $this->itemUnitPrice     = 0;
        $this->itemTaxRate       = 0.0;
        $this->itemDiscount      = 0;
        $this->serviceSearch     = '';
        $this->serviceResults    = [];
    }

    private function checkPermission(string $permission): void
    {
        if (!auth()->guard('tenant')->user()?->hasPermission($permission)) {
            abort(403);
        }
    }

    public function render()
    {
        $items = $this->invoice
            ? InvoiceItem::on('tenant')->where('invoice_id', $this->invoice->id)->get()
            : collect();

        $patient = Patient::on('tenant')->find($this->patientId);

        return view('livewire.tenant.billing.invoice-form', [
            'items'   => $items,
            'patient' => $patient,
        ]);
    }
}
