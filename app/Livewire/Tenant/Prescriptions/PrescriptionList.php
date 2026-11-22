<?php

namespace App\Livewire\Tenant\Prescriptions;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Prescription;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app-workspace')]
class PrescriptionList extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $branchFilter = '';
    public string $dateFrom     = '';
    public string $dateTo       = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingBranchFilter(): void { $this->resetPage(); }
    public function updatingDateFrom(): void { $this->resetPage(); }
    public function updatingDateTo(): void { $this->resetPage(); }

    public function render(): \Illuminate\View\View
    {
        $prescriptions = Prescription::on('tenant')
            ->with(['patient', 'doctor.staffProfile', 'items'])
            ->when($this->search, fn ($q) => $q->whereHas('patient', fn ($p) => $p
                ->where('first_name', 'like', "%{$this->search}%")
                ->orWhere('last_name', 'like', "%{$this->search}%")
                ->orWhere('patient_number', 'like', "%{$this->search}%")
            ))
            ->when($this->branchFilter, fn ($q) => $q->where('branch_id', $this->branchFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderByDesc('created_at')
            ->paginate(25);

        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('livewire.tenant.prescriptions.prescription-list', compact('prescriptions', 'branches'));
    }
}
