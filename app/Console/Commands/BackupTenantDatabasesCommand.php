<?php

namespace App\Console\Commands;

use App\Models\Platform\Tenant;
use App\Tenancy\TenantDatabaseManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BackupTenantDatabasesCommand extends Command
{
    protected $signature = 'tenants:backup
                            {--tenant= : Backup a single tenant by code}
                            {--keep=7  : Number of daily backups to retain per tenant}';

    protected $description = 'Dump each tenant database to its secure storage directory';

    public function __construct(private readonly TenantDatabaseManager $dbManager)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $keep = (int) $this->option('keep');
        $code = $this->option('tenant');

        $query = Tenant::where('status', '!=', 'terminated');

        if ($code) {
            $query->where('code', $code);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');
            return self::SUCCESS;
        }

        $this->info("Backing up {$tenants->count()} tenant(s)…");

        $failed = 0;

        foreach ($tenants as $tenant) {
            try {
                $this->backupTenant($tenant, $keep);
                $this->line("  ✓ {$tenant->code}");
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->code}: {$e->getMessage()}");
                Log::error("Backup failed for tenant {$tenant->code}", ['exception' => $e]);
                $failed++;
            }
        }

        $this->info('Done. Failed: ' . $failed);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function backupTenant(Tenant $tenant, int $keep): void
    {
        if (! $tenant->storage_path || ! is_dir($tenant->storage_path)) {
            throw new \RuntimeException("Storage path not found for tenant {$tenant->code}");
        }

        $backupDir = rtrim($tenant->storage_path, '/') . '/backups';

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0750, true);
        }

        $filename  = 'db_' . now()->format('Y-m-d_His') . '_' . Str::random(6) . '.sql.gz';
        $filepath  = $backupDir . '/' . $filename;

        $this->dbManager->connect($tenant);

        $host     = config('database.connections.tenant.host');
        $port     = config('database.connections.tenant.port', 3306);
        $dbName   = $tenant->database_name;
        $username = $tenant->db_username;
        $password = $this->dbManager->decryptPassword($tenant->db_password);

        // Build the mysqldump command — credentials passed via environment to avoid shell history exposure
        $command = sprintf(
            'mysqldump --single-transaction --quick --lock-tables=false -h %s -P %s -u %s %s | gzip > %s',
            escapeshellarg($host),
            (int) $port,
            escapeshellarg($username),
            escapeshellarg($dbName),
            escapeshellarg($filepath),
        );

        $env = 'MYSQL_PWD=' . escapeshellarg($password);

        exec("{$env} {$command}", $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \RuntimeException("mysqldump exited with code {$exitCode}");
        }

        // Rotate old backups
        $this->pruneOldBackups($backupDir, $keep);
    }

    private function pruneOldBackups(string $dir, int $keep): void
    {
        $files = glob($dir . '/db_*.sql.gz');

        if (! $files || count($files) <= $keep) {
            return;
        }

        // Sort oldest first
        usort($files, fn ($a, $b) => filemtime($a) <=> filemtime($b));

        $toDelete = array_slice($files, 0, count($files) - $keep);

        foreach ($toDelete as $file) {
            @unlink($file);
        }
    }
}
