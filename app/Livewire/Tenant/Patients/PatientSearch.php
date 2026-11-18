<?php

namespace App\Livewire\Tenant\Patients;

use App\Models\Tenant\Patient;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class PatientSearch extends Component
{
    use WithPagination;

    public string $search = '';
    public string $dobFilter = '';
    public string $branchFilter = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingDobFilter(): void { $this->resetPage(); }

    public function render(): \Illuminate\View\View
    {
        $patients = Patient::query()
            ->with(['branch', 'primaryDoctor.staffProfile'])
            ->when($this->search, fn ($q) => $q->search($this->search))
            ->when($this->dobFilter, fn ($q) => $q->whereDate('date_of_birth', $this->dobFilter))
            ->when($this->branchFilter, fn ($q) => $q->where('branch_id', $this->branchFilter))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(25);

        $branches = \App\Models\Tenant\Branch::where('is_active', true)->orderBy('name')->get();

        return view('livewire.tenant.patients.patient-search', compact('patients', 'branches'));
    }
}
