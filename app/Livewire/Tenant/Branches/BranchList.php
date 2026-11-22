<?php

namespace App\Livewire\Tenant\Branches;

use App\Models\Tenant\Branch;
use App\Services\AuditService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app-workspace')]
class BranchList extends Component
{
    public function toggleActive(int $id): void
    {
        $branch = Branch::findOrFail($id);

        if ($branch->is_main && $branch->is_active) {
            $this->addError('toggle', 'The main branch cannot be deactivated.');
            return;
        }

        $branch->update(['is_active' => !$branch->is_active]);

        app(AuditService::class)->logTenant(
            auth()->guard('tenant')->id(),
            'branch.' . ($branch->is_active ? 'activated' : 'deactivated'),
            'Branch',
            $id
        );
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.tenant.branches.branch-list', [
            'branches' => Branch::withCount('staff')->orderByDesc('is_main')->orderBy('name')->get(),
        ]);
    }
}
