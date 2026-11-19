<?php

namespace App\Services;

use App\Models\Platform\Tenant;
use App\Tenancy\TenantDatabaseManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TenantProvisioningService
{
    public function __construct(
        private TenantDatabaseManager $dbManager,
    ) {}

    /**
     * Fully provision a new tenant:
     * - Create the MySQL database and user
     * - Run all tenant migrations
     * - Seed default roles, permissions, and initial admin user
     * - Create the tenant file storage directory
     */
    public function provision(Tenant $tenant, array $adminUser): void
    {
        Log::info("Provisioning tenant: {$tenant->code}");

        $dbPassword = Str::random(32);
        $dbUsername = 'ttmed_t_' . $tenant->code;
        $dbName     = config('tenancy.db_prefix') . $tenant->code;

        // 1. Create the database and MySQL user
        $this->dbManager->createTenantDatabase($dbName, $dbUsername, $dbPassword);

        // 2. Store encrypted credentials on the tenant record
        $provCfg = config('tenancy.provisioner');
        $tenant->update([
            'database_name' => $dbName,
            'db_host'       => $provCfg['host'],
            'db_port'       => $provCfg['port'],
            'db_username'   => $dbUsername,
            'db_password'   => $this->dbManager->encryptPassword($dbPassword),
        ]);
        $tenant->refresh();

        // 3. Connect to tenant DB and run migrations
        $this->dbManager->connect($tenant);

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path'     => 'database/migrations/tenant',
            '--force'    => true,
        ]);

        // 4. Seed default roles, permissions, and catalogue data
        Artisan::call('db:seed', [
            '--class'    => 'Database\\Seeders\\TenantDefaultRolesSeeder',
            '--database' => 'tenant',
            '--force'    => true,
        ]);

        Artisan::call('db:seed', [
            '--class'    => 'TenantDefaultCatalogueSeeder',
            '--database' => 'tenant',
            '--force'    => true,
        ]);

        Artisan::call('db:seed', [
            '--class'    => 'Database\\Seeders\\Tenant\\Icd10CodeSeeder',
            '--database' => 'tenant',
            '--force'    => true,
        ]);

        Artisan::call('db:seed', [
            '--class'    => 'Database\\Seeders\\Tenant\\MedicationSeeder',
            '--database' => 'tenant',
            '--force'    => true,
        ]);

        // 5. Create the initial admin user in the tenant DB
        $this->createAdminUser($adminUser);

        // 6. Create the default branch
        $this->createDefaultBranch($tenant, $adminUser['branchName'] ?? $tenant->name);

        // 7. Create tenant file storage directory
        $storagePath = config('tenancy.storage_root') . '/' . $tenant->uuid;
        if (! is_dir($storagePath)) {
            mkdir($storagePath, 0750, true);

            foreach (['documents', 'prescriptions', 'images', 'certificates', 'backups'] as $dir) {
                mkdir($storagePath . '/' . $dir, 0750, true);
            }
        }

        $tenant->update([
            'storage_path' => $storagePath,
            'status'       => 'trial',
        ]);

        Log::info("Tenant {$tenant->code} provisioned successfully.");
    }

    private function createAdminUser(array $data): void
    {
        $adminRole = \App\Models\Tenant\Role::on('tenant')
            ->where('slug', 'tenant.admin')
            ->firstOrFail();

        $user = \App\Models\Tenant\User::on('tenant')->create([
            'username'              => $data['username'],
            'email'                 => $data['email'],
            'name'                  => $data['name'],
            'password'              => Hash::make($data['password']),
            'is_active'             => true,
            'must_change_password'  => true,
        ]);

        $user->roles()->attach($adminRole->id);
    }

    private function createDefaultBranch(Tenant $tenant, string $branchName): void
    {
        \App\Models\Tenant\Branch::on('tenant')->create([
            'name'      => $branchName,
            'is_main'   => true,
            'is_active' => true,
        ]);
    }

    /**
     * Fully deprovision a tenant:
     * - Drop the tenant database and MySQL user
     * - Delete tenant file storage directory
     * - Delete all platform records (subscriptions, tenant row)
     */
    public function deprovision(Tenant $tenant): void
    {
        Log::warning("Deprovisioning tenant: {$tenant->code} (id={$tenant->id})");

        // Drop DB and MySQL user
        try {
            $this->dbManager->dropTenantDatabase($tenant->database_name, $tenant->db_username, confirm: true);
        } catch (\Throwable $e) {
            Log::error("Failed to drop tenant database for {$tenant->code}: {$e->getMessage()}");
        }

        // Remove file storage directory
        $storagePath = config('tenancy.storage_root') . '/' . $tenant->uuid;
        if (is_dir($storagePath)) {
            $this->deleteDirectory($storagePath);
        }

        // Delete platform records
        $tenant->subscription()?->delete();
        $tenant->delete();

        Log::warning("Tenant {$tenant->code} deprovisioned.");
    }

    private function deleteDirectory(string $path): void
    {
        foreach (scandir($path) as $item) {
            if ($item === '.' || $item === '..') continue;
            $full = $path . '/' . $item;
            is_dir($full) ? $this->deleteDirectory($full) : unlink($full);
        }
        rmdir($path);
    }
}
