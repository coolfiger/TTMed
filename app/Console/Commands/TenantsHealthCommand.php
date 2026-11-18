<?php

namespace App\Console\Commands;

use App\Models\Platform\Tenant;
use App\Tenancy\TenantDatabaseManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class TenantsHealthCommand extends Command
{
    protected $signature   = 'tenants:health {--tenant= : Check a specific tenant by code}';
    protected $description = 'Verify all active tenant databases are reachable and fully migrated';

    public function handle(TenantDatabaseManager $dbManager): int
    {
        $query = Tenant::whereIn('status', ['active', 'trial'])
            ->whereNotNull('database_name');

        if ($code = $this->option('tenant')) {
            $query->where('code', $code);
        }

        $tenants = $query->orderBy('code')->get();

        if ($tenants->isEmpty()) {
            $this->warn('No active tenants found.');
            return self::SUCCESS;
        }

        $this->table(
            ['Tenant', 'Status', 'DB Reachable', 'Pending Migrations', 'Note'],
            $tenants->map(function (Tenant $tenant) use ($dbManager) {
                try {
                    $dbManager->connect($tenant);

                    // Verify basic connectivity
                    DB::connection('tenant')->statement('SELECT 1');

                    // Count pending migrations
                    $allFiles    = collect(glob(database_path('migrations/tenant/*.php')))->map(fn ($f) => basename($f, '.php'));
                    $ran         = DB::connection('tenant')->table('migrations')->pluck('migration');
                    $pending     = $allFiles->diff($ran)->count();

                    return [
                        $tenant->code,
                        $tenant->status,
                        '✓ Yes',
                        $pending === 0 ? '✓ None' : "⚠ {$pending} pending",
                        '',
                    ];
                } catch (Throwable $e) {
                    return [
                        $tenant->code,
                        $tenant->status,
                        '✗ No',
                        '—',
                        substr($e->getMessage(), 0, 60),
                    ];
                }
            })->toArray()
        );

        return self::SUCCESS;
    }
}
