<?php

namespace App\Livewire\Platform\Tenants;

use App\Models\Platform\Tenant;
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

    public function render(): \Illuminate\View\View
    {
        return view('livewire.platform.tenants.tenant-show', [
            'tenant' => $this->tenant->load('subscription.plan'),
        ]);
    }
}
