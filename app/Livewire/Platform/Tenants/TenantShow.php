<?php

namespace App\Livewire\Platform\Tenants;

use App\Models\Platform\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Tenancy\TenantDatabaseManager;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.platform')]
class TenantShow extends Component
{
    public Tenant $tenant;

    public function mount(string $uuid): void
    {
        $this->tenant = Tenant::where('uuid', $uuid)->firstOrFail();
    }

    public function render(TenantDatabaseManager $dbManager): \Illuminate\View\View
    {
        $this->tenant->load('subscription.plan');

        $dbManager->connect($this->tenant);
        $tenantUsers = TenantUser::on('tenant')->with('roles')->orderBy('name')->get();

        return view('livewire.platform.tenants.tenant-show', [
            'tenant'      => $this->tenant,
            'tenantUsers' => $tenantUsers,
        ]);
    }
}
