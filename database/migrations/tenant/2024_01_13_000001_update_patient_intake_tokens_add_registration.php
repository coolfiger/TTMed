<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->table('patient_intake_tokens', function (Blueprint $table) {
            $table->string('type', 20)->default('intake')->after('id');
            $table->unsignedBigInteger('patient_id')->nullable()->change();
            $table->string('pin', 4)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('patient_intake_tokens', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
