<?php

use App\Console\Commands\BackupTenantDatabasesCommand;
use Illuminate\Support\Facades\Schedule;

// Runs every hour; BackupService::isDue() checks each tenant's configured schedule.
Schedule::command(BackupTenantDatabasesCommand::class)
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();
