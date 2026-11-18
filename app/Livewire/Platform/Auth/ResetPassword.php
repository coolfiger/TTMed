<?php

namespace App\Livewire\Platform\Auth;

use App\Models\Platform\PlatformUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.platform-guest')]
class ResetPassword extends Component
{
    #[Url]
    public string $token = '';

    #[Url]
    public string $email = '';

    public string $password              = '';
    public string $password_confirmation = '';
    public bool   $done                  = false;

    public function mount(): void
    {
        if (empty($this->token) || empty($this->email)) {
            abort(404);
        }
    }

    public function resetPassword(): void
    {
        $this->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $record = DB::connection('platform')
            ->table('password_reset_tokens')
            ->where('email', $this->email)
            ->first();

        if (! $record || ! Hash::check($this->token, $record->token)) {
            throw ValidationException::withMessages([
                'email' => 'This password reset link is invalid or has expired.',
            ]);
        }

        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::connection('platform')->table('password_reset_tokens')->where('email', $this->email)->delete();
            throw ValidationException::withMessages([
                'email' => 'This password reset link has expired.',
            ]);
        }

        $user = PlatformUser::where('email', $this->email)->firstOrFail();
        $user->password = Hash::make($this->password);
        $user->save();

        DB::connection('platform')->table('password_reset_tokens')->where('email', $this->email)->delete();

        $this->done = true;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.platform.auth.reset-password');
    }
}
