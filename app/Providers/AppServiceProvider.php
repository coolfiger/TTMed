<?php

namespace App\Providers;

use App\Services\AppointmentService;
use App\Services\BillingService;
use App\Services\BackupService;
use App\Services\InventoryService;
use App\Services\ReportService;
use App\Services\AuditService;
use App\Services\CentreSettingsService;
use App\Services\Hl7MessageService;
use App\Services\ImagingOrderService;
use App\Services\LabOrderService;
use App\Services\ConsultationService;
use App\Services\DocumentService;
use App\Services\MedicalCertificateService;
use App\Services\NursingService;
use App\Services\PrescriptionService;
use App\Services\PatientNumberService;
use App\Services\PatientDuplicateDetector;
use App\Services\QueueNumberService;
use App\Services\QueueService;
use App\Auth\TenantUserProvider;
use App\Http\Middleware\EnsureTenantActive;
use App\Http\Middleware\ResolveTenant;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantDatabaseManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->singleton(TenantDatabaseManager::class);
        $this->app->singleton(AuditService::class);
        $this->app->singleton(CentreSettingsService::class);
        $this->app->singleton(PatientNumberService::class);
        $this->app->singleton(PatientDuplicateDetector::class);
        $this->app->singleton(QueueNumberService::class);
        $this->app->singleton(QueueService::class);
        $this->app->singleton(Hl7MessageService::class);
        $this->app->singleton(LabOrderService::class);
        $this->app->singleton(ImagingOrderService::class);
        $this->app->singleton(NursingService::class);
        $this->app->singleton(ConsultationService::class);
        $this->app->singleton(AppointmentService::class);
        $this->app->singleton(BillingService::class);
        $this->app->singleton(BackupService::class);
        $this->app->singleton(InventoryService::class);
        $this->app->singleton(ReportService::class);
        $this->app->singleton(PrescriptionService::class);
        $this->app->singleton(DocumentService::class);
        $this->app->singleton(MedicalCertificateService::class);
    }

    public function boot(): void
    {
        // Prevent mass assignment in production
        if ($this->app->isProduction()) {
            \Illuminate\Database\Eloquent\Model::preventSilentlyDiscardingAttributes();
        }

        // Custom user provider that guarantees the tenant DB connection is live
        // before every user retrieval — guards against middleware ordering edge cases.
        Auth::provider('tenant_eloquent', function ($app, array $config) {
            return new TenantUserProvider(
                $app->make(TenantContext::class),
                $app->make(TenantDatabaseManager::class),
                $app['hash'],
                $config['model'],
            );
        });

        // Ensure Livewire's internal update route runs through tenant resolution,
        // so wire:submit / wire:click work on tenant subdomains.
        Livewire::setUpdateRoute(function ($handle) {
            return \Illuminate\Support\Facades\Route::post('/livewire/update', $handle)
                ->middleware(['web', ResolveTenant::class, EnsureTenantActive::class]);
        });
    }
}
