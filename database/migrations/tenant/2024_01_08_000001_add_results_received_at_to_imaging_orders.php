<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->table('imaging_orders', function (Blueprint $table) {
            if (!Schema::connection('tenant')->hasColumn('imaging_orders', 'results_received_at')) {
                $table->timestamp('results_received_at')->nullable()->after('report_received_at');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('imaging_orders', function (Blueprint $table) {
            $table->dropColumn('results_received_at');
        });
    }
};
