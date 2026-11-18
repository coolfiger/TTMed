<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->table('tenants', function (Blueprint $table) {
            $table->enum('backup_frequency', ['disabled', 'daily', 'weekly'])->default('daily')->after('storage_path');
            $table->tinyInteger('backup_hour')->unsigned()->default(2)->after('backup_frequency');
            $table->tinyInteger('backup_day')->unsigned()->default(0)->after('backup_hour'); // 0=Sun … 6=Sat (weekly only)
            $table->unsignedSmallInteger('backup_retain')->default(7)->after('backup_day');
            $table->timestamp('backup_last_run_at')->nullable()->after('backup_retain');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->table('tenants', function (Blueprint $table) {
            $table->dropColumn(['backup_frequency', 'backup_hour', 'backup_day', 'backup_retain', 'backup_last_run_at']);
        });
    }
};
