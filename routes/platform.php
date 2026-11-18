<?php

use App\Livewire\Platform\Auth\Login as PlatformLogin;
use App\Livewire\Platform\Dashboard\PlatformDashboard;
use App\Livewire\Platform\Tenants\TenantForm;
use App\Livewire\Platform\Tenants\TenantList;
use App\Livewire\Platform\Tenants\TenantShow;
use App\Livewire\Platform\Plans\PlanList;
use App\Livewire\Platform\Plans\PlanForm;
use App\Livewire\Platform\Users\PlatformUserList;
use Illuminate\Support\Facades\Route;

Route::prefix('platform')->name('platform.')->group(function () {

    // Guest routes
    Route::middleware('guest:platform')->group(function () {
        Route::get('/login', PlatformLogin::class)->name('login');
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
        Route::get('/tenants',           TenantList::class)->name('tenants.index');
        Route::get('/tenants/create',    TenantForm::class)->name('tenants.create');
        Route::get('/tenants/{tenant}',  TenantShow::class)->name('tenants.show');
        Route::get('/tenants/{tenant}/edit', TenantForm::class)->name('tenants.edit');

        // Subscription plans
        Route::get('/plans',          PlanList::class)->name('plans.index');
        Route::get('/plans/create',   PlanForm::class)->name('plans.create');
        Route::get('/plans/{plan}/edit', PlanForm::class)->name('plans.edit');

        // Platform users
        Route::get('/users', PlatformUserList::class)->name('users.index');
    });
});
