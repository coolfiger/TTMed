<?php

namespace App\Livewire\Platform\Users;

use App\Models\Platform\PlatformUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.platform')]
class PlatformUserForm extends Component
{
    public ?PlatformUser $user = null;

    public string $name      = '';
    public string $email     = '';
    public string $role      = 'admin';
    public bool   $is_active = true;

    public string $password         = '';
    public string $password_confirmation = '';

    public bool $isProfile = false;

    public function mount(?PlatformUser $user = null): void
    {
        // /platform/profile loads the authenticated user
        if (request()->routeIs('platform.profile')) {
            $this->isProfile = true;
            $user = Auth::guard('platform')->user();
        }

        if ($user && $user->exists) {
            $this->user      = $user;
            $this->name      = $user->name;
            $this->email     = $user->email;
            $this->role      = $user->role;
            $this->is_active = $user->is_active;
        }
    }

    public function save(): void
    {
        $isNew = ! $this->user?->exists;

        $rules = [
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:platform.platform_users,email' . ($this->user?->id ? ",{$this->user->id}" : ''),
            'role'  => 'required|in:super_admin,admin,support',
        ];

        if ($isNew || $this->password !== '') {
            $rules['password'] = ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()];
        }

        $this->validate($rules);

        $data = [
            'name'      => $this->name,
            'email'     => $this->email,
            'role'      => $this->isProfile ? ($this->user->role) : $this->role,
            'is_active' => $this->isProfile ? ($this->user->is_active) : $this->is_active,
        ];

        if ($this->password !== '') {
            $data['password'] = Hash::make($this->password);
        }

        if ($isNew) {
            PlatformUser::create($data);
            session()->flash('message', 'User created.');
            $this->redirectRoute('platform.users.index');
            return;
        }

        $this->user->update($data);

        session()->flash('message', $this->isProfile ? 'Profile updated.' : 'User updated.');

        if ($this->isProfile) {
            $this->redirectRoute('platform.profile');
        } else {
            $this->redirectRoute('platform.users.index');
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.platform.users.user-form');
    }
}
