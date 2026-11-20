<?php

namespace App\Console\Commands;

use App\Models\Platform\Tenant;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantDatabaseManager;
use Database\Seeders\Tenant\DocumentTemplateSeeder;
use Illuminate\Console\Command;

class TenantSeedTemplatesCommand extends Command
{
    protected $signature   = 'tenant:seed-templates {code : Tenant code (e.g. shiva)}';
    protected $description = 'Seed default document templates into a specific tenant database';

    public function handle(TenantDatabaseManager $dbManager, TenantContext $ctx): int
    {
        $code   = $this->argument('code');
        $tenant = Tenant::on('platform')->where('code', $code)->first();

        if (!$tenant) {
            $this->error("Tenant '{$code}' not found.");
            return self::FAILURE;
        }

        $this->info("Connecting to tenant: {$tenant->name} ({$tenant->code})");
        $dbManager->connect($tenant);
        $ctx->set($tenant);

        $this->info('Seeding default document templates…');
        $seeder = new DocumentTemplateSeeder();
        $seeder->setCommand($this);
        $seeder->run();

        $count = count(\App\Models\Tenant\DocumentTemplate::TYPES);
        $this->info("Done. Default templates created for all {$count} document types.");

        return self::SUCCESS;
    }
}
