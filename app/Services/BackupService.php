<?php

namespace App\Services;

use App\Models\Platform\Tenant;
use App\Tenancy\TenantDatabaseManager;
use Illuminate\Support\Str;

class BackupService
{
    public function __construct(private readonly TenantDatabaseManager $dbManager) {}

    /**
     * Run a database backup for a tenant and return the filename created.
     */
    public function backup(Tenant $tenant): string
    {
        if (! $tenant->storage_path || ! is_dir($tenant->storage_path)) {
            throw new \RuntimeException("Storage path not found for tenant {$tenant->code}");
        }

        $backupDir = rtrim($tenant->storage_path, '/') . '/backups';

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0750, true);
        }

        $filename = 'db_' . now()->format('Y-m-d_His') . '_' . Str::random(6) . '.sql.gz';
        $filepath = $backupDir . '/' . $filename;

        $this->dbManager->connect($tenant);

        $host     = config('database.connections.tenant.host');
        $port     = config('database.connections.tenant.port', 3306);
        $dbName   = $tenant->database_name;
        $username = $tenant->db_username;
        $password = $this->dbManager->decryptPassword($tenant->db_password);

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

        $tenant->backup_last_run_at = now();
        $tenant->save();

        $this->pruneOldBackups($backupDir, (int) ($tenant->backup_retain ?? 7));

        return $filename;
    }

    /**
     * List backup files for a tenant, newest first.
     * Returns array of ['name', 'size', 'created_at'].
     */
    public function listBackups(Tenant $tenant): array
    {
        if (! $tenant->storage_path) {
            return [];
        }

        $backupDir = rtrim($tenant->storage_path, '/') . '/backups';

        if (! is_dir($backupDir)) {
            return [];
        }

        $files = glob($backupDir . '/db_*.sql.gz') ?: [];

        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        return array_map(fn ($f) => [
            'name'       => basename($f),
            'size'       => $this->humanSize(filesize($f)),
            'created_at' => date('Y-m-d H:i', filemtime($f)),
        ], $files);
    }

    /**
     * Return the absolute path to a backup file after validating it belongs to the tenant.
     */
    public function resolveBackupPath(Tenant $tenant, string $filename): string
    {
        if (! $tenant->storage_path) {
            throw new \RuntimeException('No storage path configured.');
        }

        $backupDir = rtrim(realpath($tenant->storage_path), '/') . '/backups';
        $candidate = realpath($backupDir . '/' . $filename);

        if ($candidate === false || ! str_starts_with($candidate, $backupDir . '/')) {
            throw new \InvalidArgumentException('Invalid backup file.');
        }

        if (! file_exists($candidate)) {
            throw new \RuntimeException('Backup file not found.');
        }

        return $candidate;
    }

    /**
     * Whether a tenant's scheduled backup is due at the given time.
     */
    public function isDue(Tenant $tenant, \Carbon\Carbon $at): bool
    {
        $freq = $tenant->backup_frequency ?? 'daily';

        if ($freq === 'disabled') {
            return false;
        }

        if ($at->hour !== (int) ($tenant->backup_hour ?? 2)) {
            return false;
        }

        if ($freq === 'weekly' && $at->dayOfWeek !== (int) ($tenant->backup_day ?? 0)) {
            return false;
        }

        // Don't run twice in the same hour
        if ($tenant->backup_last_run_at && $tenant->backup_last_run_at->diffInMinutes($at) < 60) {
            return false;
        }

        return true;
    }

    private function pruneOldBackups(string $dir, int $keep): void
    {
        $files = glob($dir . '/db_*.sql.gz') ?: [];

        if (count($files) <= $keep) {
            return;
        }

        usort($files, fn ($a, $b) => filemtime($a) <=> filemtime($b));

        foreach (array_slice($files, 0, count($files) - $keep) as $file) {
            @unlink($file);
        }
    }

    private function humanSize(int|false $bytes): string
    {
        if ($bytes === false || $bytes < 0) {
            return '—';
        }

        if ($bytes < 1024) {
            return "{$bytes} B";
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / 1048576, 1) . ' MB';
    }
}
