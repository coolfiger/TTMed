<?php

namespace App\Livewire\Platform\Tenants;

use App\Models\Platform\Tenant;
use App\Models\Tenant\Role as TenantRole;
use App\Models\Tenant\User as TenantUser;
use App\Tenancy\TenantDatabaseManager;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.platform')]
class TenantUserForm extends Component
{
    public Tenant $tenant;
    public ?TenantUser $tenantUser = null;

    public string $name      = '';
    public string $username  = '';
    public string $email     = '';
    public bool   $is_active = true;
    public bool   $must_change_password = true;
    public string $password  = '';
    public string $password_confirmation = '';
    public ?int   $roleId    = null;

    public bool $isEdit = false;

    public function mount(string $tenantUuid, ?int $userId = null, TenantDatabaseManager $dbManager): void
    {
        $this->tenant = Tenant::where('uuid', $tenantUuid)->firstOrFail();
        $dbManager->connect($this->tenant);

        if ($userId) {
            $this->tenantUser = TenantUser::on('tenant')->with('roles')->findOrFail($userId);
            $this->isEdit     = true;
            $this->name       = $this->tenantUser->name;
            $this->username   = $this->tenantUser->username;
            $this->email      = $this->tenantUser->email;
            $this->is_active  = $this->tenantUser->is_active;
            $this->must_change_password = $this->tenantUser->must_change_password;
            $this->roleId     = $this->tenantUser->roles->first()?->id;
        }
    }

    public function save(TenantDatabaseManager $dbManager): void
    {
        $dbManager->connect($this->tenant);

        $uniqueEmail    = 'unique:tenant.users,email' . ($this->tenantUser ? ",{$this->tenantUser->id}" : '');
        $uniqueUsername = 'unique:tenant.users,username' . ($this->tenantUser ? ",{$this->tenantUser->id}" : '');

        $rules = [
            'name'      => 'required|string|max:150',
            'username'  => ['required', 'string', 'max:50', 'alpha_dash', $uniqueUsername],
            'email'     => ['required', 'email', 'max:150', $uniqueEmail],
            'is_active' => 'boolean',
            'roleId'    => 'nullable|integer',
        ];

        if (! $this->isEdit || $this->password !== '') {
            $rules['password'] = ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()];
        }

        $this->validate($rules);

        $data = [
            'name'                 => $this->name,
            'username'             => $this->username,
            'email'                => $this->email,
            'is_active'            => $this->is_active,
            'must_change_password' => $this->must_change_password,
        ];

        if ($this->password !== '') {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->isEdit) {
            $this->tenantUser->update($data);
            if ($this->roleId) {
                $this->tenantUser->roles()->sync([$this->roleId]);
            } else {
                $this->tenantUser->roles()->detach();
            }
            session()->flash('message', 'User updated.');
        } else {
            $newUser = TenantUser::on('tenant')->create($data);
            if ($this->roleId) {
                $newUser->roles()->attach($this->roleId);
            }
            session()->flash('message', 'User created.');
        }

        $this->redirectRoute('platform.tenants.show', ['uuid' => $this->tenant->uuid]);
    }

    public function render(TenantDatabaseManager $dbManager): \Illuminate\View\View
    {
        $dbManager->connect($this->tenant);
        $roles = TenantRole::on('tenant')->orderBy('name')->get();

        // Find this person's other centre memberships (by email)
        $otherCentres = collect();
        if ($this->isEdit && $this->email) {
            $allTenants = Tenant::whereIn('status', ['active', 'trial'])
                ->where('id', '!=', $this->tenant->id)
                ->get();

            foreach ($allTenants as $otherTenant) {
                try {
                    $dbManager->connect($otherTenant);
                    $match = TenantUser::on('tenant')
                        ->with('roles')
                        ->where('email', $this->tenantUser->email)
                        ->first();

                    if ($match) {
                        $otherCentres->push([
                            'tenant' => $otherTenant,
                            'user'   => $match,
                        ]);
                    }
                } catch (\Throwable) {
                    continue;
                }
            }

            // Reconnect to this tenant for any subsequent queries
            $dbManager->connect($this->tenant);
        }

        return view('livewire.platform.tenants.tenant-user-form', compact('roles', 'otherCentres'));
    }
}
