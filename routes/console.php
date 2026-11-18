<?php

use App\Console\Commands\BackupTenantDatabasesCommand;
use Illuminate\Support\Facades\Schedule;

// Daily tenant database backups at 02:00 AM server time
Schedule::command(BackupTenantDatabasesCommand::class, [
    '--keep' => env('BACKUP_TENANT_KEEP_DAYS', 7),
])->dailyAt('02:00')->withoutOverlapping()->runInBackground();
