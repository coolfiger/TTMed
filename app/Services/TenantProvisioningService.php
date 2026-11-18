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
        $tenant->update([
            'database_name' => $dbName,
            'db_username'   => $dbUsername,
            'db_password'   => $this->dbManager->encryptPassword($dbPassword),
        ]);

        // 3. Connect to tenant DB and run migrations
        $this->dbManager->connect($tenant);

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path'     => 'database/migrations/tenant',
            '--force'    => true,
        ]);

        // 4. Seed default roles, permissions, and catalogue data
        Artisan::call('db:seed', [
            '--class'    => 'TenantDefaultRolesSeeder',
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
        $this->createDefaultBranch($tenant);

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

    private function createDefaultBranch(Tenant $tenant): void
    {
        \App\Models\Tenant\Branch::on('tenant')->create([
            'name'    => $tenant->name,
            'is_main' => true,
            'is_active' => true,
        ]);
    }
}
