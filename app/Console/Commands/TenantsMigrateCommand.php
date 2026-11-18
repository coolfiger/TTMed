<?php

namespace App\Console\Commands;

use App\Models\Platform\Tenant;
use App\Tenancy\TenantDatabaseManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class TenantsMigrateCommand extends Command
{
    protected $signature   = 'tenants:migrate {--tenant= : Migrate a specific tenant by code} {--force}';
    protected $description = 'Run pending tenant database migrations across all active tenants';

    public function handle(TenantDatabaseManager $dbManager): int
    {
        $query = Tenant::whereIn('status', ['active', 'trial'])
            ->whereNotNull('database_name');

        if ($code = $this->option('tenant')) {
            $query->where('code', $code);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warn('No active tenants found.');
            return self::SUCCESS;
        }

        $this->info("Migrating {$tenants->count()} tenant(s)...");

        $success = 0;
        $failed  = 0;

        foreach ($tenants as $tenant) {
            $this->line("  → {$tenant->code} ({$tenant->name})");

            try {
                $dbManager->connect($tenant);

                Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path'     => 'database/migrations/tenant',
                    '--force'    => $this->option('force') || $this->getLaravel()->isProduction(),
                ]);

                $output = trim(Artisan::output());
                if ($output) {
                    $this->line("    {$output}");
                }

                $success++;
            } catch (\Throwable $e) {
                $this->error("    FAILED: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Done. {$success} succeeded, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
