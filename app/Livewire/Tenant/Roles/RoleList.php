<?php

namespace App\Livewire\Tenant\Roles;

use App\Models\Tenant\Role;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class RoleList extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.tenant.roles.role-list', [
            'roles' => Role::withCount('permissions')->orderBy('name')->get(),
        ]);
    }
}
