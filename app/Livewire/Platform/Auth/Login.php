<?php

namespace App\Livewire\Platform\Auth;

use App\Models\Platform\PlatformUser;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.platform-guest')]
class Login extends Component
{
    public string $email    = '';
    public string $password = '';
    public string $mfaCode  = '';
    public bool   $showMfa  = false;

    public function authenticate(AuditService $audit): void
    {
        $this->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $throttleKey = 'platform-login:' . strtolower($this->email) . ':' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => "Too many attempts. Try again in {$seconds} seconds.",
            ]);
        }

        $user = PlatformUser::where('email', $this->email)->where('is_active', true)->first();

        if (! $user || ! Hash::check($this->password, $user->password)) {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        if ($user->mfa_enabled) {
            $this->showMfa = true;
            session(['platform_mfa_pending' => $user->id]);
            return;
        }

        $this->completePlatformLogin($user, $audit, $throttleKey);
    }

    public function verifyMfa(AuditService $audit): void
    {
        $this->validate(['mfaCode' => 'required|digits:6']);

        $userId = session('platform_mfa_pending');
        $user   = PlatformUser::find($userId);

        if (! $user) {
            $this->showMfa = false;
            return;
        }

        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);

        if (! $google2fa->verifyKey($user->mfa_secret, $this->mfaCode)) {
            throw ValidationException::withMessages(['mfaCode' => 'Invalid code.']);
        }

        session()->forget('platform_mfa_pending');
        $this->completePlatformLogin($user, $audit, 'platform-login:' . $user->email . ':' . request()->ip());
    }

    private function completePlatformLogin(PlatformUser $user, AuditService $audit, string $throttleKey): void
    {
        RateLimiter::clear($throttleKey);

        $user->last_login_at = now();
        $user->last_login_ip = request()->ip();
        $user->save();

        Auth::guard('platform')->login($user);
        session()->regenerate();

        $audit->logPlatform('auth.login', 'PlatformUser', $user->id);

        $this->redirectRoute('platform.dashboard');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.platform.auth.login');
    }
}
