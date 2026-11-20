<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->table('encounters', function (Blueprint $table) {
            $table->timestamp('ended_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('encounters', function (Blueprint $table) {
            $table->dropColumn('ended_at');
        });
    }
};
