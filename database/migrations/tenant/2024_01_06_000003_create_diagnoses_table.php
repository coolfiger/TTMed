<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('diagnoses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('encounter_id')->index();
            $table->unsignedBigInteger('patient_id')->index();
            $table->unsignedBigInteger('icd10_code_id')->nullable();

            // Denormalised for history integrity — survives ICD-10 catalogue updates
            $table->string('icd10_code', 10)->nullable();
            $table->string('diagnosis_text', 500);

            $table->enum('diagnosis_type', ['primary', 'secondary', 'differential'])->default('primary');
            $table->enum('certainty', ['confirmed', 'probable', 'suspected', 'ruled_out'])->default('confirmed');
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('diagnosed_by')->nullable();
            $table->timestamps();

            $table->foreign('encounter_id')->references('id')->on('encounters')->cascadeOnDelete();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('icd10_code_id')->references('id')->on('icd10_codes')->nullOnDelete();
            $table->foreign('diagnosed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('diagnoses');
    }
};
