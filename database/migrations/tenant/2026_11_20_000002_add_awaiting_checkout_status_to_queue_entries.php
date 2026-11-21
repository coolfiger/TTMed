<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        if (DB::connection('tenant')->getDriverName() === 'sqlite') {
            return; // SQLite doesn't enforce enum types; no-op
        }
        DB::connection('tenant')->statement("
            ALTER TABLE queue_entries
            MODIFY COLUMN status ENUM(
                'waiting','with_nurse','awaiting_doctor','with_doctor',
                'awaiting_checkout','completed','cancelled','no_show'
            ) NOT NULL DEFAULT 'waiting'
        ");
    }

    public function down(): void
    {
        if (DB::connection('tenant')->getDriverName() === 'sqlite') {
            return;
        }
        DB::connection('tenant')->statement("
            ALTER TABLE queue_entries
            MODIFY COLUMN status ENUM(
                'waiting','with_nurse','awaiting_doctor','with_doctor',
                'completed','cancelled','no_show'
            ) NOT NULL DEFAULT 'waiting'
        ");
    }
};
