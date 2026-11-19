<?php

use App\Livewire\Platform\Auth\Login as PlatformLogin;
use App\Livewire\Platform\Auth\ForgotPassword as PlatformForgotPassword;
use App\Livewire\Platform\Auth\ResetPassword as PlatformResetPassword;
use App\Livewire\Platform\Dashboard\PlatformDashboard;
use App\Livewire\Platform\Tenants\TenantForm;
use App\Livewire\Platform\Tenants\TenantList;
use App\Livewire\Platform\Tenants\TenantShow;
use App\Livewire\Platform\Tenants\TenantUserForm;
use App\Livewire\Platform\Plans\PlanList;
use App\Livewire\Platform\Plans\PlanForm;
use App\Livewire\Platform\Users\PlatformUserList;
use App\Livewire\Platform\Users\PlatformUserForm;
use Illuminate\Support\Facades\Route;

// Root redirect for platform subdomain
Route::get('/', fn () => redirect()->route('platform.login'));

Route::prefix('platform')->name('platform.')->group(function () {

    // Guest routes
    Route::middleware(['guest:platform', 'throttle:10,1'])->group(function () {
        Route::get('/login',           PlatformLogin::class)->name('login');
        Route::get('/forgot-password', PlatformForgotPassword::class)->name('password.request');
        Route::get('/reset-password',  PlatformResetPassword::class)->name('password.reset');
    });

    // Authenticated platform admin routes
    Route::middleware('auth:platform')->group(function () {
        Route::get('/dashboard', PlatformDashboard::class)->name('dashboard');
        Route::get('/logout', function () {
            auth()->guard('platform')->logout();
            session()->invalidate();
            session()->regenerateToken();
            return redirect()->route('platform.login');
        })->name('logout');

        // Tenants
        Route::get('/tenants',               TenantList::class)->name('tenants.index');
        Route::get('/tenants/create',        TenantForm::class)->name('tenants.create');
        Route::get('/tenants/{uuid}',        TenantShow::class)->name('tenants.show');
        Route::get('/tenants/{uuid}/edit',   TenantForm::class)->name('tenants.edit');

        // Tenant user management (platform admin managing users within a medical centre)
        Route::get('/tenants/{tenantUuid}/users/create',       TenantUserForm::class)->name('tenants.users.create');
        Route::get('/tenants/{tenantUuid}/users/{userId}/edit', TenantUserForm::class)->name('tenants.users.edit');

        // Subscription plans
        Route::get('/plans',              PlanList::class)->name('plans.index');
        Route::get('/plans/create',       PlanForm::class)->name('plans.create');
        Route::get('/plans/{uuid}/edit',  PlanForm::class)->name('plans.edit');

        // Platform users
        Route::get('/users',              PlatformUserList::class)->name('users.index');
        Route::get('/users/create',       PlatformUserForm::class)->name('users.create');
        Route::get('/users/{id}/edit',    PlatformUserForm::class)->name('users.edit');

        // My profile
        Route::get('/profile', PlatformUserForm::class)->name('profile');
    });
});
