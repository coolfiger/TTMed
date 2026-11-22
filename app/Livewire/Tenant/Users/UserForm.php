<?php

namespace App\Livewire\Tenant\Users;

use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app-workspace')]
class UserForm extends Component
{
    public ?User $user = null;

    public string $name = '';
    public string $username = '';
    public string $email = '';
    public string $password = '';
    public ?int $branchId = null;
    public array $selectedRoles = [];
    public bool $isActive = true;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $this->user = User::findOrFail($id);
            $this->fill([
                'name'          => $this->user->name,
                'username'      => $this->user->username,
                'email'         => $this->user->email ?? '',
                'branchId'      => $this->user->branch_id,
                'selectedRoles' => $this->user->roles->pluck('id')->toArray(),
                'isActive'      => $this->user->is_active,
            ]);
        }
    }

    public function save(): void
    {
        $rules = [
            'name'          => 'required|string|max:150',
            'username'      => 'required|string|max:50|alpha_dash',
            'email'         => 'nullable|email|max:150',
            'selectedRoles' => 'required|array|min:1',
        ];

        if (!$this->user) {
            $rules['password'] = 'required|string|min:8';
            $rules['username'] .= '|unique:tenant.users,username';
        } else {
            $rules['password'] = 'nullable|string|min:8';
        }

        $this->validate($rules);

        $data = [
            'name'      => $this->name,
            'username'  => $this->username,
            'email'     => $this->email ?: null,
            'branch_id' => $this->branchId,
            'is_active' => $this->isActive,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->user) {
            $this->user->update($data);
        } else {
            $this->user = User::create($data);
        }

        $this->user->roles()->sync($this->selectedRoles);

        session()->flash('message', 'User saved.');
        $this->redirect(route('tenant.users.index'));
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.tenant.users.user-form', [
            'roles' => Role::orderBy('name')->get(),
        ]);
    }
}
