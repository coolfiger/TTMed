<?php

namespace App\Livewire\Platform\Users;

use App\Models\Platform\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Tenancy\TenantDatabaseManager;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.platform')]
class MedicalCentreUserList extends Component
{
    public string $search = '';

    public function render(TenantDatabaseManager $dbManager): \Illuminate\View\View
    {
        $tenants = Tenant::whereIn('status', ['active', 'trial'])->orderBy('name')->get();

        $users = collect();

        foreach ($tenants as $tenant) {
            try {
                $dbManager->connect($tenant);

                $query = TenantUser::on('tenant')
                    ->with('roles')
                    ->orderBy('name');

                if ($this->search) {
                    $query->where(function ($q) {
                        $q->where('name', 'like', "%{$this->search}%")
                          ->orWhere('username', 'like', "%{$this->search}%")
                          ->orWhere('email', 'like', "%{$this->search}%");
                    });
                }

                $tenantUsers = $query->get()->map(function ($u) use ($tenant) {
                    $u->_tenant = $tenant;
                    return $u;
                });

                $users = $users->merge($tenantUsers);
            } catch (\Throwable) {
                continue;
            }
        }

        if ($this->search) {
            $users = $users->filter(fn ($u) =>
                str_contains(strtolower($u->name), strtolower($this->search)) ||
                str_contains(strtolower($u->username), strtolower($this->search)) ||
                str_contains(strtolower($u->email), strtolower($this->search))
            );
        }

        return view('livewire.platform.users.medical-centre-user-list', [
            'users' => $users->sortBy('name')->values(),
        ]);
    }
}
