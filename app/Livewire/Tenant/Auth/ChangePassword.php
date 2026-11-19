<?php

namespace App\Livewire\Tenant\Auth;

use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class ChangePassword extends Component
{
    public string $password              = '';
    public string $password_confirmation = '';

    public function save(AuditService $audit): void
    {
        $this->validate([
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
        ]);

        $user = Auth::guard('tenant')->user();

        $user->update([
            'password'             => Hash::make($this->password),
            'must_change_password' => false,
        ]);

        $audit->logTenant('auth.password_changed', 'User', $user->id, description: 'Forced password change completed.');

        $this->redirectRoute('tenant.dashboard');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.tenant.auth.change-password');
    }
}
