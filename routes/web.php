<?php

use App\Livewire\Tenant\Auth\Login as TenantLogin;
use App\Livewire\Tenant\Dashboard\TenantDashboard;
use App\Livewire\Tenant\Users\UserList;
use App\Livewire\Tenant\Users\UserForm;
use App\Livewire\Tenant\Roles\RoleList;
use App\Livewire\Tenant\Roles\RoleForm;
use App\Livewire\Tenant\Patients\PatientSearch;
use App\Livewire\Tenant\Patients\PatientForm;
use App\Livewire\Tenant\Patients\PatientProfile;
use App\Livewire\Tenant\Branches\BranchList;
use App\Livewire\Tenant\Branches\BranchForm;
use App\Livewire\Tenant\Settings\CentreSettings;
use App\Livewire\Tenant\Staff\StaffList;
use App\Livewire\Tenant\Staff\StaffForm;
use App\Livewire\Tenant\Services\ServiceList;
use App\Livewire\Tenant\Services\ServiceForm;
use App\Livewire\Tenant\Insurance\InsuranceList;
use App\Livewire\Tenant\Insurance\InsuranceForm;
use App\Livewire\Tenant\Reception\ReceptionDashboard;
use App\Livewire\Tenant\Reception\QueueManager;
use App\Livewire\Tenant\Nursing\NurseDashboard;
use App\Livewire\Tenant\Doctor\DoctorDashboard;
use App\Http\Controllers\Tenant\LabInboundController;
use App\Http\Controllers\Tenant\ImagingInboundController;
use App\Http\Controllers\Tenant\DocumentDownloadController;
use App\Http\Controllers\Tenant\PrescriptionDownloadController;
use App\Http\Controllers\Tenant\Smtp2goWebhookController;
use App\Livewire\Tenant\Prescriptions\PrescriptionForm;
use App\Livewire\Tenant\Documents\PatientDocuments;
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

        // Patient management
        Route::middleware('permission:patients.view')->group(function () {
            Route::get('/patients',                  PatientSearch::class)->name('tenant.patients.index');
            Route::get('/patients/{id}',             PatientProfile::class)->name('tenant.patients.show');
        });
        Route::middleware('permission:patients.create')->group(function () {
            Route::get('/patients/create',           PatientForm::class)->name('tenant.patients.create');
        });
        Route::middleware('permission:patients.edit')->group(function () {
            Route::get('/patients/{id}/edit',        PatientForm::class)->name('tenant.patients.edit');
        });

        // Branch management
        Route::middleware('permission:branches.view')->group(function () {
            Route::get('/branches', BranchList::class)->name('tenant.branches.index');
        });
        Route::middleware('permission:branches.manage')->group(function () {
            Route::get('/branches/create',           BranchForm::class)->name('tenant.branches.create');
            Route::get('/branches/{branch}/edit',    BranchForm::class)->name('tenant.branches.edit');
        });

        // Centre settings
        Route::middleware('permission:settings.view')->group(function () {
            Route::get('/settings', CentreSettings::class)->name('tenant.settings.index');
        });

        // Staff management
        Route::middleware('permission:staff.view')->group(function () {
            Route::get('/staff', StaffList::class)->name('tenant.staff.index');
        });
        Route::middleware('permission:staff.manage')->group(function () {
            Route::get('/staff/create',           StaffForm::class)->name('tenant.staff.create');
            Route::get('/staff/{staff}/edit',     StaffForm::class)->name('tenant.staff.edit');
        });

        // Service catalogue
        Route::middleware('permission:services.view')->group(function () {
            Route::get('/services', ServiceList::class)->name('tenant.services.index');
        });
        Route::middleware('permission:services.manage')->group(function () {
            Route::get('/services/create',           ServiceForm::class)->name('tenant.services.create');
            Route::get('/services/{service}/edit',   ServiceForm::class)->name('tenant.services.edit');
        });

        // Insurance providers
        Route::middleware('permission:insurance.view')->group(function () {
            Route::get('/insurance', InsuranceList::class)->name('tenant.insurance.index');
        });
        Route::middleware('permission:insurance.manage')->group(function () {
            Route::get('/insurance/create',              InsuranceForm::class)->name('tenant.insurance.create');
            Route::get('/insurance/{provider}/edit',     InsuranceForm::class)->name('tenant.insurance.edit');
        });

        // Reception and queue
        Route::middleware('permission:queue.view')->group(function () {
            Route::get('/reception',      ReceptionDashboard::class)->name('tenant.reception.dashboard');
            Route::get('/queue',          QueueManager::class)->name('tenant.queue.manage');
        });

        // Nursing station
        Route::middleware('permission:nursing.assess')->group(function () {
            Route::get('/nursing', NurseDashboard::class)->name('tenant.nursing.dashboard');
        });

        // Doctor consultation
        Route::middleware('permission:encounters.view')->group(function () {
            Route::get('/consultations', DoctorDashboard::class)->name('tenant.consultations.dashboard');
        });

        // Prescriptions
        Route::middleware('permission:prescriptions.create')->group(function () {
            Route::get('/prescriptions/encounter/{encounterId}', PrescriptionForm::class)
                ->name('tenant.prescriptions.encounter');
        });
        Route::middleware('permission:prescriptions.view')->group(function () {
            Route::get('/prescriptions/{id}/download', PrescriptionDownloadController::class)
                ->name('tenant.prescriptions.download');
        });

        // Patient documents
        Route::middleware('permission:documents.view')->group(function () {
            Route::get('/patients/{patientId}/documents', PatientDocuments::class)
                ->name('tenant.documents.index');
            Route::get('/documents/{id}/download', DocumentDownloadController::class)
                ->name('tenant.documents.download');
        });
    });

    // Integration inbound webhooks (no session auth — HMAC-verified)
    Route::prefix('api')->group(function () {
        Route::post('/lab/inbound/hl7',      [LabInboundController::class,     'receiveHl7'])->name('tenant.lab.inbound.hl7');
        Route::post('/lab/inbound/fhir',     [LabInboundController::class,     'receiveFhir'])->name('tenant.lab.inbound.fhir');
        Route::post('/imaging/inbound/hl7',  [ImagingInboundController::class, 'receiveHl7'])->name('tenant.imaging.inbound.hl7');
        Route::post('/imaging/inbound/vepro',[ImagingInboundController::class, 'receiveVepro'])->name('tenant.imaging.inbound.vepro');
        Route::post('/smtp2go/webhook', Smtp2goWebhookController::class)->name('tenant.smtp2go.webhook');
    });
});
