<?php

namespace App\Livewire\Tenant\Insurance;

use App\Models\Tenant\InsuranceProvider;
use App\Services\AuditService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class InsuranceList extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void { $this->resetPage(); }

    public function toggleActive(int $id): void
    {
        $provider = InsuranceProvider::findOrFail($id);
        $provider->update(['is_active' => !$provider->is_active]);
    }

    public function render(): \Illuminate\View\View
    {
        $providers = InsuranceProvider::query()
            ->when($this->search, fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(25);

        return view('livewire.tenant.insurance.insurance-list', compact('providers'));
    }
}
