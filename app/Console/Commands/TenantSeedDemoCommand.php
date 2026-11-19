<?php

namespace App\Console\Commands;

use App\Models\Platform\Tenant;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantDatabaseManager;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Console\Command;

class TenantSeedDemoCommand extends Command
{
    protected $signature   = 'tenant:seed-demo {code : Tenant code (e.g. shiva)}';
    protected $description = 'Seed demo data into a specific tenant database';

    public function handle(TenantDatabaseManager $dbManager, TenantContext $ctx): int
    {
        $code   = $this->argument('code');
        $tenant = Tenant::on('platform')->where('code', $code)->first();

        if (! $tenant) {
            $this->error("Tenant '{$code}' not found.");
            return self::FAILURE;
        }

        $this->info("Connecting to tenant: {$tenant->name} ({$tenant->code})");
        $dbManager->connect($tenant);
        $ctx->set($tenant);

        $this->info('Running DemoDataSeeder…');
        $seeder = new DemoDataSeeder();
        $seeder->setCommand($this);
        $seeder->run();

        $this->newLine();
        $this->info('Done. Log in at https://shiva.ttmed.app/login');
        $this->line('  Username: rsharma  Password: Demo1234!  (tenant admin / Dr. Ramesh Sharma)');

        return self::SUCCESS;
    }
}
