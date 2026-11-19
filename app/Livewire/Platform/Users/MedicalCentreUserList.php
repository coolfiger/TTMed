<?php

namespace App\Livewire\Platform\Users;

use App\Models\Platform\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Tenancy\TenantDatabaseManager;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.platform')]
class MedicalCentreUserList extends Component
{
    public string $search = '';

    public function render(TenantDatabaseManager $dbManager): \Illuminate\View\View
    {
        $tenants = Tenant::whereIn('status', ['active', 'trial'])->orderBy('name')->get();

        // Collect every user record across all tenants, keyed by email
        $byEmail = [];

        foreach ($tenants as $tenant) {
            try {
                $dbManager->connect($tenant);

                $query = TenantUser::on('tenant')->with('roles');

                if ($this->search) {
                    $term = $this->search;
                    $query->where(function ($q) use ($term) {
                        $q->where('name', 'like', "%{$term}%")
                          ->orWhere('username', 'like', "%{$term}%")
                          ->orWhere('email', 'like', "%{$term}%");
                    });
                }

                foreach ($query->get() as $user) {
                    $email = strtolower($user->email);

                    if (! isset($byEmail[$email])) {
                        $byEmail[$email] = [
                            'name'        => $user->name,
                            'email'       => $user->email,
                            'memberships' => [],
                        ];
                    }

                    $byEmail[$email]['memberships'][] = [
                        'tenant'   => $tenant,
                        'user'     => $user,
                    ];
                }
            } catch (\Throwable) {
                continue;
            }
        }

        // Sort by name
        usort($byEmail, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return view('livewire.platform.users.medical-centre-user-list', [
            'people' => $byEmail,
        ]);
    }
}
