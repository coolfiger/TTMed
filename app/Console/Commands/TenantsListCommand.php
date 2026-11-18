<?php

namespace App\Console\Commands;

use App\Models\Platform\Tenant;
use Illuminate\Console\Command;

class TenantsListCommand extends Command
{
    protected $signature   = 'tenants:list {--status= : Filter by status}';
    protected $description = 'List all tenants';

    public function handle(): int
    {
        $query = Tenant::with('subscription.plan');

        if ($status = $this->option('status')) {
            $query->where('status', $status);
        }

        $tenants = $query->orderBy('code')->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');
            return self::SUCCESS;
        }

        $rows = $tenants->map(fn (Tenant $t) => [
            $t->code,
            $t->name,
            $t->status,
            $t->subscription?->plan?->name ?? 'None',
            $t->subscription?->expires_at?->format('Y-m-d') ?? '-',
            $t->database_name ?? 'Not provisioned',
        ]);

        $this->table(
            ['Code', 'Name', 'Status', 'Plan', 'Expires', 'Database'],
            $rows
        );

        return self::SUCCESS;
    }
}
