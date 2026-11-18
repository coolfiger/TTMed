<?php

namespace App\Console\Commands;

use App\Models\Platform\PlatformUser;
use App\Models\Platform\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class DemoSetupCommand extends Command
{
    protected $signature   = 'demo:setup {--fresh : Drop and recreate existing demo databases}';
    protected $description = 'Bootstrap a local SQLite demo environment (dev only)';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('demo:setup must not be run in production.');
            return self::FAILURE;
        }

        $this->info('Setting up TTMed demo environment…');

        // ----------------------------------------------------------------
        // 1. Ensure env vars are written
        // ----------------------------------------------------------------
        $this->patchEnv();

        // Reload config after patching .env
        $this->call('config:clear');

        // ----------------------------------------------------------------
        // 2. Create SQLite files
        // ----------------------------------------------------------------
        $dbDir      = database_path();
        $platformDb = $dbDir . '/platform.sqlite';
        $tenantDb   = $dbDir . '/demo_tenant.sqlite';
        $defaultDb  = $dbDir . '/database.sqlite';

        foreach ([$defaultDb, $platformDb, $tenantDb] as $file) {
            if ($this->option('fresh') && file_exists($file)) {
                unlink($file);
            }
            if (! file_exists($file)) {
                touch($file);
                $this->line("  created: " . basename($file));
            }
        }

        // Point connections at the files we just created
        Config::set('database.connections.platform.driver',   'sqlite');
        Config::set('database.connections.platform.database', $platformDb);
        Config::set('database.connections.tenant.driver',     'sqlite');
        Config::set('database.connections.tenant.database',   $tenantDb);

        DB::purge('platform');
        DB::purge('tenant');

        // ----------------------------------------------------------------
        // 3. Default connection migrations (sessions, cache, jobs tables)
        // ----------------------------------------------------------------
        $this->info('Running default migrations…');
        Artisan::call('migrate', ['--force' => true]);
        $this->line(Artisan::output());

        // ----------------------------------------------------------------
        // 4. Platform migrations
        // ----------------------------------------------------------------
        $this->info('Running platform migrations…');
        Artisan::call('migrate', [
            '--database' => 'platform',
            '--path'     => 'database/migrations/platform',
            '--force'    => true,
        ]);
        $this->line(Artisan::output());

        // ----------------------------------------------------------------
        // 5. Create platform admin
        // ----------------------------------------------------------------
        if (! PlatformUser::on('platform')->where('email', 'admin@demo.local')->exists()) {
            PlatformUser::on('platform')->create([
                'name'      => 'Platform Admin',
                'email'     => 'admin@demo.local',
                'password'  => Hash::make('password'),
                'role'      => 'super_admin',
                'is_active' => true,
            ]);
            $this->line('  platform admin created: admin@demo.local / password');
        }

        // ----------------------------------------------------------------
        // 6. Create demo tenant record
        // ----------------------------------------------------------------
        $tenant = Tenant::on('platform')->where('code', 'demo')->first();

        if (! $tenant) {
            $uuid = (string) Str::uuid();

            $storagePath = storage_path('tenants/' . $uuid);
            @mkdir($storagePath . '/backups',      0750, true);
            @mkdir($storagePath . '/documents',    0750, true);
            @mkdir($storagePath . '/prescriptions',0750, true);

            $tenant = Tenant::on('platform')->create([
                'uuid'             => $uuid,
                'code'             => 'demo',
                'name'             => 'Demo Medical Centre',
                'business_name'    => 'Demo Medical Centre Ltd.',
                'database_name'    => $tenantDb,
                'db_host'          => 'sqlite',
                'db_port'          => 0,
                'db_username'      => 'sqlite',
                'db_password'      => base64_encode(str_repeat('x', 32)), // placeholder, not used for sqlite
                'status'           => 'active',
                'storage_path'     => $storagePath,
                'backup_frequency' => 'disabled',
            ]);

            $this->line("  demo tenant created (uuid: {$uuid})");
        }

        // ----------------------------------------------------------------
        // 7. Tenant migrations + seeders
        // ----------------------------------------------------------------
        $this->info('Running tenant migrations…');
        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path'     => 'database/migrations/tenant',
            '--force'    => true,
        ]);
        $this->line(Artisan::output());

        $this->info('Seeding tenant data…');
        $this->runProcess([PHP_BINARY, 'artisan', 'db:seed', '--class=Database\\Seeders\\TenantDefaultRolesSeeder',    '--database=tenant', '--force']);
        $this->runProcess([PHP_BINARY, 'artisan', 'db:seed', '--class=TenantDefaultCatalogueSeeder','--database=tenant', '--force']);

        // ----------------------------------------------------------------
        // 8. Create demo tenant admin
        // ----------------------------------------------------------------
        $adminRole = \App\Models\Tenant\Role::on('tenant')->where('slug', 'tenant.admin')->first();

        if ($adminRole && ! \App\Models\Tenant\User::on('tenant')->where('username', 'admin')->exists()) {
            $user = \App\Models\Tenant\User::on('tenant')->create([
                'username'             => 'admin',
                'email'                => 'admin@demo.local',
                'name'                 => 'Demo Admin',
                'password'             => Hash::make('password'),
                'is_active'            => true,
                'must_change_password' => false,
            ]);
            $user->roles()->attach($adminRole->id);
            $this->line('  tenant admin created: admin / password');
        }

        // Create a default branch
        if (! \App\Models\Tenant\Branch::on('tenant')->exists()) {
            \App\Models\Tenant\Branch::on('tenant')->create([
                'name'      => 'Demo Medical Centre',
                'is_main'   => true,
                'is_active' => true,
            ]);
        }

        // ----------------------------------------------------------------
        // Done
        // ----------------------------------------------------------------
        $this->newLine();
        $this->info('Demo environment ready.');
        $this->line('');
        $this->line('  Platform admin:  http://localhost:8000/platform/login');
        $this->line('    Email:    admin@demo.local');
        $this->line('    Password: password');
        $this->line('');
        $this->line('  Tenant demo:    http://localhost:8000/dev/tenant/demo  (then /login)');
        $this->line('    Username: admin');
        $this->line('    Password: password');
        $this->line('');
        $this->line('  Run the server: php artisan serve');

        return self::SUCCESS;
    }

    private function runProcess(array $command): void
    {
        $process = new Process($command, base_path());
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });
    }

    private function patchEnv(): void
    {
        $envPath = base_path('.env');
        $env     = file_get_contents($envPath);
        $dbDir   = database_path();

        $patches = [
            'PLATFORM_DB_CONNECTION' => 'sqlite',
            'PLATFORM_DB_DATABASE'   => $dbDir . '/platform.sqlite',
            'TENANT_DB_CONNECTION'   => 'sqlite',
            'TENANT_DB_DATABASE'     => $dbDir . '/demo_tenant.sqlite',
            'TENANT_RESOLUTION'      => 'session',
            'TENANT_ENCRYPTION_KEY'  => 'demo-only-dev-key-not-for-prod!!',
            'APP_URL'                => 'http://localhost:8000',
            'SESSION_DRIVER'         => 'database',
            'CACHE_STORE'            => 'database',
            'QUEUE_CONNECTION'       => 'database',
        ];

        foreach ($patches as $key => $value) {
            if (preg_match("/^{$key}=/m", $env)) {
                $env = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $env);
            } else {
                $env .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $env);
        $this->line('  .env updated');
    }
}
