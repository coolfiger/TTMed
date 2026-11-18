<?php

namespace App\Livewire\Tenant\Auth;

use App\Models\Tenant\User;
use App\Notifications\Tenant\ResetPasswordNotification;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class ForgotPassword extends Component
{
    public string $email  = '';
    public bool   $sent   = false;

    public function sendLink(TenantContext $tenantContext): void
    {
        $this->validate(['email' => 'required|email']);

        $user = User::on('tenant')->where('email', $this->email)->first();

        if ($user) {
            $token = Str::random(64);

            DB::connection('tenant')->table('password_reset_tokens')->upsert(
                ['email' => $user->email, 'token' => Hash::make($token), 'created_at' => now()],
                ['email'],
                ['token', 'created_at'],
            );

            $user->notify(new ResetPasswordNotification($token, $tenantContext->get()->code));
        }

        // Always show success to prevent email enumeration
        $this->sent = true;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.tenant.auth.forgot-password');
    }
}
