<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('encounter_instructions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('encounter_id')->unique();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->json('lines');
            $table->timestamps();

            $table->foreign('encounter_id')->references('id')->on('encounters')->cascadeOnDelete();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('encounter_instructions');
    }
};
