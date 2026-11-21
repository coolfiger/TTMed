<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->table('nursing_procedures', function (Blueprint $table) {
            $table->string('performed_by_role', 20)->default('nurse')->after('performed_by');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('nursing_procedures', function (Blueprint $table) {
            $table->dropColumn('performed_by_role');
        });
    }
};
