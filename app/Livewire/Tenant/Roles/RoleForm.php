<?php

namespace App\Livewire\Tenant\Roles;

use App\Models\Tenant\Permission;
use App\Models\Tenant\Role;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app-workspace')]
class RoleForm extends Component
{
    public ?Role $role = null;

    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public bool $isSystem = false;
    public array $selectedPermissions = [];

    public function mount(?int $id = null): void
    {
        if ($id) {
            $this->role = Role::with('permissions')->findOrFail($id);
            $this->fill([
                'name'                => $this->role->name,
                'slug'                => $this->role->slug,
                'description'         => $this->role->description ?? '',
                'isSystem'            => $this->role->is_system,
                'selectedPermissions' => $this->role->permissions->pluck('id')->toArray(),
            ]);
        }
    }

    public function save(): void
    {
        if ($this->role?->is_system) {
            $this->addError('slug', 'System roles cannot be modified.');
            return;
        }

        $this->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|alpha_dash',
        ]);

        $data = [
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
        ];

        if ($this->role) {
            $this->role->update($data);
        } else {
            $this->role = Role::create($data);
        }

        $this->role->permissions()->sync($this->selectedPermissions);

        session()->flash('message', 'Role saved.');
        $this->redirect(route('tenant.roles.index'));
    }

    public function render(): \Illuminate\View\View
    {
        $permissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        return view('livewire.tenant.roles.role-form', [
            'permissionGroups' => $permissions,
        ]);
    }
}
