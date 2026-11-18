<?php

namespace App\Jobs;

use App\Models\Platform\Tenant;
use App\Services\BackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunTenantBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout  = 600;
    public int $tries    = 2;

    public function __construct(public readonly Tenant $tenant) {}

    public function handle(BackupService $backupService): void
    {
        $filename = $backupService->backup($this->tenant);

        Log::info("Tenant backup completed: {$this->tenant->code} → {$filename}");
    }

    public function failed(\Throwable $e): void
    {
        Log::error("Tenant backup failed: {$this->tenant->code}", ['exception' => $e]);
    }
}
