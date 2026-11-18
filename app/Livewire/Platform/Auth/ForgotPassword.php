<?php

namespace App\Livewire\Platform\Auth;

use App\Models\Platform\PlatformUser;
use App\Notifications\Platform\ResetPasswordNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.platform-guest')]
class ForgotPassword extends Component
{
    public string $email = '';
    public bool   $sent  = false;

    public function sendLink(): void
    {
        $this->validate(['email' => 'required|email']);

        $user = PlatformUser::where('email', $this->email)->where('is_active', true)->first();

        if ($user) {
            $token = Str::random(64);

            DB::connection('platform')->table('password_reset_tokens')->upsert(
                ['email' => $user->email, 'token' => Hash::make($token), 'created_at' => now()],
                ['email'],
                ['token', 'created_at'],
            );

            $user->notify(new ResetPasswordNotification($token));
        }

        $this->sent = true;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.platform.auth.forgot-password');
    }
}
