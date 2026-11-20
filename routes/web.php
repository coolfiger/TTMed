<?php

use App\Livewire\Tenant\Auth\Login as TenantLogin;
use App\Livewire\Tenant\Auth\ForgotPassword as TenantForgotPassword;
use App\Livewire\Tenant\Auth\ResetPassword as TenantResetPassword;
use App\Livewire\Tenant\Auth\ChangePassword as TenantChangePassword;
use App\Livewire\Tenant\Dashboard\TenantDashboard;
use App\Livewire\Tenant\Users\UserList;
use App\Livewire\Tenant\Users\UserForm;
use App\Livewire\Tenant\Roles\RoleList;
use App\Livewire\Tenant\Roles\RoleForm;
use App\Livewire\Tenant\Patients\PatientSearch;
use App\Livewire\Tenant\Patients\PatientForm;
use App\Livewire\Tenant\Patients\PatientProfile;
use App\Http\Controllers\Tenant\PatientIntakeController;
use App\Livewire\Tenant\Branches\BranchList;
use App\Livewire\Tenant\Branches\BranchForm;
use App\Livewire\Tenant\Settings\CentreSettings;
use App\Livewire\Tenant\Settings\IntegrationSettings;
use App\Livewire\Tenant\Settings\TemplateManager;
use App\Livewire\Tenant\Imaging\ImagingWorkqueue;
use App\Livewire\Tenant\Documents\DocumentTracker;
use App\Http\Controllers\Tenant\BrandingLogoController;
use App\Livewire\Tenant\Staff\StaffList;
use App\Livewire\Tenant\Staff\StaffForm;
use App\Livewire\Tenant\Services\ServiceList;
use App\Livewire\Tenant\Services\ServiceForm;
use App\Livewire\Tenant\Insurance\InsuranceList;
use App\Livewire\Tenant\Insurance\InsuranceForm;
use App\Http\Controllers\Tenant\InsuranceFormTemplateController;
use App\Livewire\Tenant\Reception\ReceptionDashboard;
use App\Livewire\Tenant\Reception\QueueManager;
use App\Livewire\Tenant\Nursing\NurseDashboard;
use App\Livewire\Tenant\Doctor\DoctorDashboard;
use App\Http\Controllers\Tenant\LabInboundController;
use App\Http\Controllers\Tenant\ImagingInboundController;
use App\Http\Controllers\Tenant\DocumentDownloadController;
use App\Http\Controllers\Tenant\PrescriptionDownloadController;
use App\Http\Controllers\Tenant\LabAttachmentDownloadController;
use App\Http\Controllers\Tenant\ImagingReportDownloadController;
use App\Http\Controllers\Tenant\Smtp2goWebhookController;
use App\Livewire\Tenant\Prescriptions\PrescriptionForm;
use App\Livewire\Tenant\Prescriptions\PrescriptionList;
use App\Livewire\Tenant\Documents\PatientDocuments;
use App\Livewire\Tenant\Billing\BillingList;
use App\Livewire\Tenant\Billing\CashierShiftManager;
use App\Livewire\Tenant\Billing\InvoiceForm;
use App\Livewire\Tenant\Billing\PaymentCollection;
use App\Livewire\Tenant\Billing\PaymentList;
use App\Livewire\Tenant\Appointments\AppointmentCalendar;
use App\Livewire\Tenant\Appointments\DoctorScheduleConfig;
use App\Livewire\Tenant\Appointments\FollowUpDashboard;
use App\Livewire\Tenant\Lab\LabOrderForm;
use App\Livewire\Tenant\Lab\LabResultsViewer;
use App\Livewire\Tenant\Inventory\InventoryManager;
use App\Livewire\Tenant\Reports\ReportDashboard;
use App\Http\Controllers\Tenant\ReportExportController;
use App\Http\Controllers\Tenant\BackupDownloadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Application Routes
|--------------------------------------------------------------------------
| These routes are served from {code}.ttmed.app subdomains.
| All routes pass through ResolveTenant and EnsureTenantActive middleware.
|
*/

// Local development only: set tenant in session so subdomain routing is not required
// Visit /dev/tenant/{code} to switch tenant, then browse to /login
if (app()->environment('local')) {
    Route::get('/dev/tenant/{code}', function (string $code) {
        $tenant = \App\Models\Platform\Tenant::on('platform')->where('code', $code)->firstOrFail();
        session([
            'tenant_uuid' => $tenant->uuid,
            'tenant_id'   => $tenant->id,
        ]);
        app(\App\Tenancy\TenantContext::class)->set($tenant);
        app(\App\Tenancy\TenantDatabaseManager::class)->connect($tenant);
        return redirect('/login');
    })->name('dev.switch-tenant');
}

Route::middleware(['resolve.tenant', 'tenant.active'])->group(function () {

    // Patient intake form — public, PIN-verified, single-use token
    Route::get('/intake/{token}',  [PatientIntakeController::class, 'show'])->name('tenant.patient.intake');
    Route::post('/intake/{token}', [PatientIntakeController::class, 'submit'])->name('tenant.patient.intake.submit')
        ->middleware('throttle:10,5');

    // New patient registration via QR — public, single-use tokenised link
    Route::get('/register/{token}',  [PatientIntakeController::class, 'showRegistration'])->name('tenant.patient.register');
    Route::post('/register/{token}', [PatientIntakeController::class, 'submitRegistration'])->name('tenant.patient.register.submit')
        ->middleware('throttle:10,5');

    // Branding logo — public within tenant (non-sensitive)
    Route::get('/branding/logo/{type}', BrandingLogoController::class)
        ->where('type', 'login|sidebar')
        ->name('tenant.branding.logo');

    // Guest routes (no tenant auth required, but tenant must be resolved)
    Route::middleware(['guest:tenant', 'throttle:10,1'])->group(function () {
        Route::get('/login',           TenantLogin::class)->name('tenant.login');
        Route::get('/forgot-password', TenantForgotPassword::class)->name('tenant.password.request');
        Route::get('/reset-password',  TenantResetPassword::class)->name('tenant.password.reset');
        Route::get('/',                fn () => redirect()->route('tenant.login'));
    });

    // Authenticated tenant routes
    Route::middleware('auth:tenant')->group(function () {

        Route::get('/change-password', TenantChangePassword::class)->name('tenant.password.change');

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
            Route::get('/patients/{id}',             PatientProfile::class)->name('tenant.patients.show')->whereNumber('id');
        });
        Route::middleware('permission:patients.create')->group(function () {
            Route::get('/patients/create',           PatientForm::class)->name('tenant.patients.create');
        });
        Route::middleware('permission:patients.edit')->group(function () {
            Route::get('/patients/{id}/edit',        PatientForm::class)->name('tenant.patients.edit')->whereNumber('id');
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
        Route::middleware('permission:settings.manage')->group(function () {
            Route::get('/settings/integrations', IntegrationSettings::class)->name('tenant.settings.integrations');
            Route::get('/settings/templates',    TemplateManager::class)->name('tenant.settings.templates');
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
            Route::post('/insurance/{provider}/templates',      [InsuranceFormTemplateController::class, 'upload'])->name('tenant.insurance.templates.upload');
            Route::delete('/insurance/templates/{template}',   [InsuranceFormTemplateController::class, 'destroy'])->name('tenant.insurance.templates.destroy');
        });
        Route::middleware('permission:insurance.view')->group(function () {
            Route::get('/insurance/templates/{template}/download', [InsuranceFormTemplateController::class, 'download'])->name('tenant.insurance.templates.download');
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
        Route::middleware('permission:prescriptions.view')->group(function () {
            Route::get('/prescriptions', PrescriptionList::class)->name('tenant.prescriptions.index');
        });
        Route::middleware('permission:prescriptions.create')->group(function () {
            Route::get('/prescriptions/encounter/{encounterId}', PrescriptionForm::class)
                ->name('tenant.prescriptions.encounter');
        });
        Route::middleware('permission:prescriptions.view')->group(function () {
            Route::get('/prescriptions/{id}/download', PrescriptionDownloadController::class)
                ->name('tenant.prescriptions.download');
        });

        // Appointments
        Route::middleware('permission:appointments.view')->group(function () {
            Route::get('/appointments',              AppointmentCalendar::class)->name('tenant.appointments.calendar');
            Route::get('/appointments/follow-ups',  FollowUpDashboard::class)->name('tenant.appointments.follow-ups');
        });
        Route::middleware('permission:appointments.manage')->group(function () {
            Route::get('/appointments/schedule/{doctorId}', DoctorScheduleConfig::class)
                ->name('tenant.appointments.schedule');
        });

        // Billing
        Route::middleware('permission:billing.view')->group(function () {
            Route::get('/billing', BillingList::class)->name('tenant.billing.index');
            Route::get('/billing/payments', PaymentList::class)->name('tenant.payments.index');
            Route::get('/billing/invoice/{patientId}', InvoiceForm::class)
                ->name('tenant.billing.invoice.create');
            Route::get('/billing/invoice/{patientId}/{invoiceId}', InvoiceForm::class)
                ->name('tenant.billing.invoice.edit');
            Route::get('/billing/invoice/{invoiceId}/payment', PaymentCollection::class)
                ->name('tenant.billing.payment');
        });

        // Cash management
        Route::middleware('permission:shifts.view')->group(function () {
            Route::get('/billing/shifts', CashierShiftManager::class)
                ->name('tenant.billing.shifts');
        });

        // Lab
        Route::middleware('permission:lab.orders.create')->group(function () {
            Route::get('/lab/order/{encounterId}/{patientId}', LabOrderForm::class)
                ->name('tenant.lab.order.create');
            Route::get('/lab/order/{encounterId}/{patientId}/{orderId}', LabOrderForm::class)
                ->name('tenant.lab.order.view');
        });
        Route::middleware('permission:lab.orders.create')->group(function () {
            Route::get('/lab/results/{patientId}', LabResultsViewer::class)
                ->name('tenant.lab.results');
        });

        // Inventory
        Route::middleware('permission:inventory.view')->group(function () {
            Route::get('/inventory', InventoryManager::class)
                ->name('tenant.inventory.index');
        });

        // Reports
        Route::middleware('permission:reports.operational')->group(function () {
            Route::get('/reports', ReportDashboard::class)->name('tenant.reports.index');
        });
        Route::middleware('permission:reports.export')->group(function () {
            Route::get('/reports/export/{report}', ReportExportController::class)->name('tenant.reports.export');
        });

        // Backup download — permission enforced inside the controller
        Route::get('/settings/backups/{filename}', BackupDownloadController::class)
            ->where('filename', 'db_[0-9A-Za-z_\-]+\.sql\.gz')
            ->name('tenant.settings.backup.download');

        // Lab attachment PDF viewer
        Route::middleware('permission:lab.orders.create')->group(function () {
            Route::get('/lab-attachments/{id}/view', LabAttachmentDownloadController::class)
                ->name('tenant.lab.attachment.view');
        });

        // Imaging report PDF viewer
        Route::middleware('permission:encounters.view')->group(function () {
            Route::get('/imaging-reports/{id}/view', ImagingReportDownloadController::class)
                ->name('tenant.imaging.report.view');
        });

        // Patient documents
        Route::middleware('permission:documents.view')->group(function () {
            Route::get('/patients/{patientId}/documents', PatientDocuments::class)
                ->name('tenant.documents.index');
            Route::get('/documents/{id}/download', DocumentDownloadController::class)
                ->name('tenant.documents.download');
        });

        // Imaging workqueue
        Route::middleware('permission:imaging.view')->group(function () {
            Route::get('/imaging', ImagingWorkqueue::class)->name('tenant.imaging.index');
        });

        // Document tracker
        Route::middleware('permission:documents.view')->group(function () {
            Route::get('/document-tracker', DocumentTracker::class)->name('tenant.document.tracker');
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
