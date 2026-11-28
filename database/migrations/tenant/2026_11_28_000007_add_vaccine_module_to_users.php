<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->table('users', function (Blueprint $table) {
            $table->boolean('vaccine_module_enabled')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('users', function (Blueprint $table) {
            $table->dropColumn('vaccine_module_enabled');
        });
    }
};
