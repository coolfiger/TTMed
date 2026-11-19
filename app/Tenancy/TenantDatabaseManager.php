<?php

namespace App\Tenancy;

use App\Models\Platform\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantDatabaseManager
{
    /**
     * Configure and establish the 'tenant' database connection
     * for the given tenant.
     */
    public function connect(Tenant $tenant): void
    {
        // SQLite mode: db_host sentinel 'sqlite' used in local/demo environments
        if ($tenant->db_host === 'sqlite' || env('TENANT_DB_CONNECTION') === 'sqlite') {
            Config::set('database.connections.tenant', [
                'driver'                  => 'sqlite',
                'database'                => $tenant->database_name,
                'prefix'                  => '',
                'foreign_key_constraints' => true,
            ]);
            DB::purge('tenant');
            DB::reconnect('tenant');
            return;
        }

        $password = $this->decryptPassword($tenant->db_password);

        Config::set('database.connections.tenant', [
            'driver'    => 'mysql',
            'host'      => $tenant->db_host,
            'port'      => $tenant->db_port,
            'database'  => $tenant->database_name,
            'username'  => $tenant->db_username,
            'password'  => $password,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => true,
            'engine'    => 'InnoDB',
        ]);

        // Purge any existing connection so the new config takes effect
        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    /**
     * Create a new tenant database and dedicated MySQL user.
     * Uses the provisioner credentials from config.
     */
    public function createTenantDatabase(string $databaseName, string $dbUsername, string $dbPassword): void
    {
        $provisioner = $this->provisionerConnection();

        $provisioner->statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $provisioner->statement("CREATE USER IF NOT EXISTS '{$dbUsername}'@'localhost' IDENTIFIED BY '{$dbPassword}'");
        $provisioner->statement("GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, REFERENCES, CREATE TEMPORARY TABLES ON `{$databaseName}`.* TO '{$dbUsername}'@'localhost'");
        $provisioner->statement('FLUSH PRIVILEGES');
    }

    /**
     * Drop a tenant database and its user (for deprovisioning).
     * Requires explicit confirmation flag to prevent accidental use.
     */
    public function dropTenantDatabase(string $databaseName, string $dbUsername, bool $confirm = false): void
    {
        if (! $confirm) {
            throw new \RuntimeException('Explicit confirmation required to drop a tenant database.');
        }

        $provisioner = $this->provisionerConnection();
        $provisioner->statement("DROP DATABASE IF EXISTS `{$databaseName}`");
        $provisioner->statement("DROP USER IF EXISTS '{$dbUsername}'@'localhost'");
        $provisioner->statement('FLUSH PRIVILEGES');
    }

    private function provisionerConnection(): \Illuminate\Database\Connection
    {
        $cfg = config('tenancy.provisioner');

        Config::set('database.connections.provisioner', [
            'driver'   => 'mysql',
            'host'     => $cfg['host'],
            'port'     => $cfg['port'],
            'database' => '',
            'username' => $cfg['username'],
            'password' => $cfg['password'],
            'charset'  => 'utf8mb4',
        ]);

        DB::purge('provisioner');

        return DB::connection('provisioner');
    }

    public function encryptPassword(string $password): string
    {
        $key = $this->encryptionKey();
        $iv  = random_bytes(16);
        $encrypted = openssl_encrypt($password, 'AES-256-CBC', $key, 0, $iv);

        return base64_encode($iv . $encrypted);
    }

    public function decryptPassword(string $encrypted): string
    {
        $key  = $this->encryptionKey();
        $data = base64_decode($encrypted);
        $iv   = substr($data, 0, 16);
        $data = substr($data, 16);

        return openssl_decrypt($data, 'AES-256-CBC', $key, 0, $iv);
    }

    private function encryptionKey(): string
    {
        $key = config('tenancy.encryption_key');

        if (! $key || strlen($key) < 32) {
            throw new \RuntimeException('TENANT_ENCRYPTION_KEY must be set and at least 32 characters.');
        }

        return substr($key, 0, 32);
    }
}
