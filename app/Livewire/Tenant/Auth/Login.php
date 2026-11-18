<?php

namespace App\Livewire\Tenant\Auth;

use App\Models\Tenant\User;
use App\Services\AuditService;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class Login extends Component
{
    public string $username  = '';
    public string $password  = '';
    public bool   $remember  = false;
    public string $mfaCode   = '';
    public bool   $showMfa   = false;
    private ?User $pendingUser = null;

    public function authenticate(TenantContext $tenantContext, AuditService $audit): void
    {
        $this->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $this->checkRateLimit();

        $user = User::on('tenant')
            ->where('username', $this->username)
            ->first();

        if (! $user || ! Hash::check($this->password, $user->password)) {
            if ($user) {
                $user->incrementLoginAttempts();
                if ($user->login_attempts >= 5) {
                    $user->lockAccount();
                }
            }

            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'username' => 'These credentials do not match our records.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'username' => 'Your account has been deactivated.',
            ]);
        }

        if ($user->isLocked()) {
            throw ValidationException::withMessages([
                'username' => 'Your account is temporarily locked. Please try again later.',
            ]);
        }

        if ($user->mfa_enabled) {
            $this->showMfa   = true;
            session(['mfa_pending_user' => $user->id]);
            return;
        }

        $this->completeLogin($user, $tenantContext, $audit);
    }

    public function verifyMfa(TenantContext $tenantContext, AuditService $audit): void
    {
        $this->validate(['mfaCode' => 'required|digits:6']);

        $userId = session('mfa_pending_user');
        $user   = User::on('tenant')->find($userId);

        if (! $user) {
            $this->showMfa = false;
            return;
        }

        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);

        if (! $google2fa->verifyKey($user->mfa_secret, $this->mfaCode)) {
            throw ValidationException::withMessages([
                'mfaCode' => 'Invalid verification code.',
            ]);
        }

        session()->forget('mfa_pending_user');
        $this->completeLogin($user, $tenantContext, $audit);
    }

    private function completeLogin(User $user, TenantContext $tenantContext, AuditService $audit): void
    {
        RateLimiter::clear($this->throttleKey());
        $user->resetLoginAttempts();

        session([
            'tenant_id'   => $tenantContext->id(),
            'tenant_uuid' => $tenantContext->uuid(),
        ]);

        Auth::guard('tenant')->login($user, $this->remember);

        session()->regenerate();

        $audit->logTenant('auth.login', 'User', $user->id, description: "Login from {$_SERVER['REMOTE_ADDR']}");

        if ($user->must_change_password) {
            $this->redirectRoute('tenant.password.change');
            return;
        }

        $this->redirectIntended(route('tenant.dashboard'));
    }

    private function checkRateLimit(): void
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey(), 10)) {
            $seconds = RateLimiter::availableIn($this->throttleKey());

            throw ValidationException::withMessages([
                'username' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }
    }

    private function throttleKey(): string
    {
        return 'login:' . strtolower($this->username) . ':' . request()->ip();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.tenant.auth.login');
    }
}
