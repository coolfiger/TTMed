<?php

namespace App\Livewire\Tenant\Staff;

use App\Models\Tenant\Branch;
use App\Models\Tenant\StaffProfile;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app-workspace')]
class StaffList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $branchFilter = '';
    public string $roleFilter = '';

    public function updatingSearch(): void  { $this->resetPage(); }
    public function updatingBranchFilter(): void { $this->resetPage(); }

    public function render(): \Illuminate\View\View
    {
        $staff = StaffProfile::query()
            ->with(['user.roles', 'branch', 'doctor'])
            ->when($this->search, fn ($q) => $q
                ->where('first_name', 'like', "%{$this->search}%")
                ->orWhere('last_name', 'like', "%{$this->search}%"))
            ->when($this->branchFilter, fn ($q) => $q->where('branch_id', $this->branchFilter))
            ->orderBy('last_name')
            ->paginate(25);

        return view('livewire.tenant.staff.staff-list', [
            'staff'    => $staff,
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
