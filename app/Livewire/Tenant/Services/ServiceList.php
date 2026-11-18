<?php

namespace App\Livewire\Tenant\Services;

use App\Models\Tenant\Service;
use App\Models\Tenant\ServiceCategory;
use App\Services\AuditService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ServiceList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $categoryFilter = '';
    public string $statusFilter = 'active';

    public function updatingSearch(): void { $this->resetPage(); }

    public function toggleActive(int $id): void
    {
        $service = Service::findOrFail($id);
        $service->update(['is_active' => !$service->is_active]);

        app(AuditService::class)->logTenant(
            auth()->guard('tenant')->id(),
            'service.' . ($service->is_active ? 'activated' : 'deactivated'),
            'Service',
            $id
        );
    }

    public function render(): \Illuminate\View\View
    {
        $services = Service::query()
            ->with('category')
            ->when($this->search, fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%"))
            ->when($this->categoryFilter, fn ($q) => $q->where('category_id', $this->categoryFilter))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(30);

        return view('livewire.tenant.services.service-list', [
            'services'   => $services,
            'categories' => ServiceCategory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
