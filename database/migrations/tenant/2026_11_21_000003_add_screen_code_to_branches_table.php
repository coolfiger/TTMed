<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->table('branches', function (Blueprint $table) {
            $table->string('screen_code', 20)->nullable()->unique()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('branches', function (Blueprint $table) {
            $table->dropColumn('screen_code');
        });
    }
};
