<?php

namespace App\Livewire\Platform\Tenants;

use App\Models\Platform\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\AuditService;
use App\Services\TenantProvisioningService;
use App\Tenancy\TenantDatabaseManager;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.platform')]
class TenantShow extends Component
{
    public Tenant $tenant;

    public bool   $confirmingDelete = false;
    public string $deleteConfirmCode = '';

    public function mount(string $uuid): void
    {
        $this->tenant = Tenant::where('uuid', $uuid)->firstOrFail();
    }

    public function deleteTenant(TenantProvisioningService $provisioner, AuditService $audit): void
    {
        if ($this->deleteConfirmCode !== $this->tenant->code) {
            $this->addError('deleteConfirmCode', 'Code does not match.');
            return;
        }

        $audit->logPlatform('tenant.deleted', 'Tenant', $this->tenant->id, $this->tenant->id, $this->tenant->toArray());

        $provisioner->deprovision($this->tenant);

        session()->flash('message', "Medical centre deleted.");
        $this->redirectRoute('platform.tenants.index');
    }

    public function render(TenantDatabaseManager $dbManager): \Illuminate\View\View
    {
        $this->tenant->load('subscription.plan');

        try {
            $dbManager->connect($this->tenant);
            $tenantUsers = TenantUser::on('tenant')->with('roles')->orderBy('name')->get();
        } catch (\Throwable) {
            $tenantUsers = collect();
        }

        return view('livewire.platform.tenants.tenant-show', [
            'tenant'      => $this->tenant,
            'tenantUsers' => $tenantUsers,
        ]);
    }
}
