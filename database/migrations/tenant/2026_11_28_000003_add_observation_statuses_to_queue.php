<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    protected $connection = 'tenant';

    // MySQL ENUM requires a full column restatement to add values.
    // The queue_entries.status column was created as VARCHAR in the
    // original migration, so this is a no-op at the DB level — we
    // just document the new accepted values here for review purposes.
    // The application-layer constants and transition map in QueueEntry
    // enforce the valid values.

    public function up(): void
    {
        // No schema change needed — status is stored as VARCHAR.
    }

    public function down(): void
    {
        // Nothing to revert.
    }
};
