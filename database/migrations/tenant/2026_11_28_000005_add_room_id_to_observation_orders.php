<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->table('observation_orders', function (Blueprint $table) {
            $table->foreignId('room_id')->nullable()->after('branch_id')
                  ->constrained('observation_rooms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('observation_orders', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
            $table->dropColumn('room_id');
        });
    }
};
