<?php

namespace App\Livewire\Tenant\Billing;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Invoice;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class BillingList extends Component
{
    use WithPagination;

    public string $search        = '';
    public string $statusFilter  = '';
    public string $branchFilter  = '';
    public string $dateFrom      = '';
    public string $dateTo        = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }
    public function updatingBranchFilter(): void { $this->resetPage(); }
    public function updatingDateFrom(): void { $this->resetPage(); }
    public function updatingDateTo(): void { $this->resetPage(); }

    public function render(): \Illuminate\View\View
    {
        $invoices = Invoice::on('tenant')
            ->with(['patient', 'branch'])
            ->when($this->search, fn ($q) => $q
                ->where('invoice_number', 'like', "%{$this->search}%")
                ->orWhereHas('patient', fn ($p) => $p
                    ->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")
                    ->orWhere('patient_number', 'like', "%{$this->search}%")
                )
            )
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->branchFilter, fn ($q) => $q->where('branch_id', $this->branchFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderByDesc('created_at')
            ->paginate(25);

        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('livewire.tenant.billing.billing-list', compact('invoices', 'branches'));
    }
}
