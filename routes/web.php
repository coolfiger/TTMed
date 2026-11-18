<?php

use App\Livewire\Tenant\Auth\Login as TenantLogin;
use App\Livewire\Tenant\Dashboard\TenantDashboard;
use App\Livewire\Tenant\Users\UserList;
use App\Livewire\Tenant\Users\UserForm;
use App\Livewire\Tenant\Roles\RoleList;
use App\Livewire\Tenant\Roles\RoleForm;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Application Routes
|--------------------------------------------------------------------------
| These routes are served from {code}.ttmed.app subdomains.
| All routes pass through ResolveTenant and EnsureTenantActive middleware.
|
*/

Route::middleware(['resolve.tenant', 'tenant.active'])->group(function () {

    // Guest routes (no tenant auth required, but tenant must be resolved)
    Route::middleware('guest:tenant')->group(function () {
        Route::get('/login', TenantLogin::class)->name('tenant.login');
        Route::get('/',      fn () => redirect()->route('tenant.login'));
    });

    // Authenticated tenant routes
    Route::middleware('auth:tenant')->group(function () {

        Route::get('/logout', function () {
            auth()->guard('tenant')->logout();
            session()->forget(['tenant_id', 'tenant_uuid']);
            session()->invalidate();
            session()->regenerateToken();
            return redirect()->route('tenant.login');
        })->name('tenant.logout');

        Route::get('/dashboard', TenantDashboard::class)->name('tenant.dashboard');

        // User management (requires users.manage permission)
        Route::middleware('permission:users.manage')->group(function () {
            Route::get('/users',               UserList::class)->name('tenant.users.index');
            Route::get('/users/create',        UserForm::class)->name('tenant.users.create');
            Route::get('/users/{user}/edit',   UserForm::class)->name('tenant.users.edit');
        });

        // Role management
        Route::middleware('permission:roles.manage')->group(function () {
            Route::get('/roles',             RoleList::class)->name('tenant.roles.index');
            Route::get('/roles/create',      RoleForm::class)->name('tenant.roles.create');
            Route::get('/roles/{role}/edit', RoleForm::class)->name('tenant.roles.edit');
        });
    });
});
