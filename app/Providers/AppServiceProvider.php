<?php

namespace App\Providers;

use App\Services\AuditService;
use App\Services\CentreSettingsService;
use App\Services\PatientNumberService;
use App\Services\PatientDuplicateDetector;
use App\Services\QueueNumberService;
use App\Services\QueueService;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantDatabaseManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

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
    }

    public function boot(): void
    {
        // Prevent mass assignment in production
        if ($this->app->isProduction()) {
            \Illuminate\Database\Eloquent\Model::preventSilentlyDiscardingAttributes();
        }
    }
}
