<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('patient_intake_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->string('token', 64)->unique();
            $table->string('pin', 4);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['token', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('patient_intake_tokens');
    }
};
