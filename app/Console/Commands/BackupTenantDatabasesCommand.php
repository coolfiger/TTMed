<?php

namespace App\Console\Commands;

use App\Jobs\RunTenantBackupJob;
use App\Models\Platform\Tenant;
use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupTenantDatabasesCommand extends Command
{
    protected $signature = 'tenants:backup
                            {--tenant=    : Back up a single tenant by code (bypasses schedule check)}
                            {--now        : Dispatch immediately, ignore schedule}
                            {--sync       : Run synchronously instead of dispatching to the queue}';

    protected $description = 'Dispatch backup jobs for tenants whose scheduled backup is due';

    public function __construct(private readonly BackupService $backupService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $code  = $this->option('tenant');
        $now   = $this->option('now');
        $sync  = $this->option('sync');
        $at    = now();

        $query = Tenant::where('status', '!=', 'terminated');

        if ($code) {
            $query->where('code', $code);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');
            return self::SUCCESS;
        }

        $dispatched = 0;

        foreach ($tenants as $tenant) {
            if (! $code && ! $now && ! $this->backupService->isDue($tenant, $at)) {
                continue;
            }

            if ($sync) {
                try {
                    $file = $this->backupService->backup($tenant);
                    $this->line("  ✓ {$tenant->code} → {$file}");
                } catch (\Throwable $e) {
                    $this->error("  ✗ {$tenant->code}: {$e->getMessage()}");
                }
            } else {
                RunTenantBackupJob::dispatch($tenant)->onQueue('backups');
                $this->line("  queued: {$tenant->code}");
            }

            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} backup job(s).");

        return self::SUCCESS;
    }
}
