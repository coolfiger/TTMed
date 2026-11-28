<?php

namespace App\Livewire\Tenant\Prescriptions;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Prescription;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app-workspace')]
class PrescriptionList extends Component
{
    use WithPagination;

    public string $patientSearch = '';
    public string $rxSearch      = '';
    public string $statusFilter  = '';
    public string $branchFilter  = '';
    public string $dateFrom      = '';
    public string $dateTo        = '';

    public function updatingPatientSearch(): void { $this->resetPage(); }
    public function updatingRxSearch(): void      { $this->resetPage(); }
    public function updatingStatusFilter(): void  { $this->resetPage(); }
    public function updatingBranchFilter(): void  { $this->resetPage(); }
    public function updatingDateFrom(): void      { $this->resetPage(); }
    public function updatingDateTo(): void        { $this->resetPage(); }

    public function render(): \Illuminate\View\View
    {
        $user = Auth::guard('tenant')->user();

        $prescriptions = Prescription::on('tenant')
            ->with(['patient', 'doctor.staffProfile', 'items'])
            ->when($this->patientSearch, fn ($q) => $q->whereHas('patient', fn ($p) => $p
                ->where('first_name', 'like', "%{$this->patientSearch}%")
                ->orWhere('last_name', 'like', "%{$this->patientSearch}%")
                ->orWhere('patient_number', 'like', "%{$this->patientSearch}%")
            ))
            ->when($this->rxSearch, fn ($q) => $q->where('prescription_number', 'like', "%{$this->rxSearch}%"))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->branchFilter, fn ($q) => $q->where('branch_id', $this->branchFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderByDesc('created_at')
            ->paginate(25);

        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        $canCreate = $user?->hasPermission('prescriptions.create');

        $counts = [
            'draft'     => Prescription::on('tenant')->where('status', 'draft')->count(),
            'issued'    => Prescription::on('tenant')->where('status', 'issued')->count(),
            'cancelled' => Prescription::on('tenant')->where('status', 'cancelled')->count(),
        ];

        return view('livewire.tenant.prescriptions.prescription-list',
            compact('prescriptions', 'branches', 'canCreate', 'counts'));
    }
}
