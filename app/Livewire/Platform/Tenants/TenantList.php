<?php

namespace App\Livewire\Platform\Tenants;

use App\Models\Platform\Tenant;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.platform')]
class TenantList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updateStatus(string $tenantUuid, string $status): void
    {
        $allowed = ['active', 'suspended', 'cancelled', 'trial'];

        if (! in_array($status, $allowed)) {
            return;
        }

        $tenant = Tenant::where('uuid', $tenantUuid)->firstOrFail();
        $old    = $tenant->status;
        $tenant->update(['status' => $status]);

        app(\App\Services\AuditService::class)->logPlatform(
            'tenant.status_changed',
            'Tenant',
            $tenant->id,
            tenantId: $tenant->id,
            oldValues: ['status' => $old],
            newValues: ['status' => $status],
        );

        session()->flash('message', "Medical centre status updated to {$status}.");
    }

    public function render(): \Illuminate\View\View
    {
        $tenants = Tenant::query()
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%")
                  ->orWhere('contact_email', 'like', "%{$this->search}%");
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->with('subscription.plan')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.platform.tenants.tenant-list', compact('tenants'));
    }
}
