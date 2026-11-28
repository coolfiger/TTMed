<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('vaccine_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('short_name', 60)->nullable();
            $table->text('description')->nullable();
            $table->string('disease_target', 255)->nullable();
            $table->enum('type', ['single_dose', 'multi_dose'])->default('multi_dose');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->timestamps();
        });

        Schema::connection('tenant')->create('vaccine_dose_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vaccine_catalog_id')->constrained('vaccine_catalog')->cascadeOnDelete();
            $table->unsignedTinyInteger('dose_number');
            $table->string('dose_label', 60);                // e.g. "Dose 1", "Booster"
            // Schedule basis: 'dob' = weeks from date of birth; 'prev_dose' = weeks from previous dose date
            $table->enum('reference', ['dob', 'prev_dose'])->default('dob');
            $table->unsignedSmallInteger('reference_weeks'); // recommended weeks from reference point
            $table->unsignedSmallInteger('min_weeks')->nullable();
            $table->unsignedSmallInteger('max_weeks')->nullable();
            $table->string('notes', 255)->nullable();
            $table->unique(['vaccine_catalog_id', 'dose_number']);
        });

        Schema::connection('tenant')->create('patient_vaccinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('vaccine_catalog_id')->constrained('vaccine_catalog');
            $table->foreignId('vaccine_dose_definition_id')->constrained('vaccine_dose_definitions');
            $table->unsignedTinyInteger('dose_number');
            $table->foreignId('encounter_id')->nullable()->constrained('encounters')->nullOnDelete();
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('administered_by')->constrained('users');
            $table->date('administered_on');
            $table->string('lot_number', 60)->nullable();
            $table->string('site', 60)->nullable();     // e.g. Left thigh, Right arm
            $table->string('route', 30)->nullable();    // IM, SC, Oral
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'vaccine_catalog_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('patient_vaccinations');
        Schema::connection('tenant')->dropIfExists('vaccine_dose_definitions');
        Schema::connection('tenant')->dropIfExists('vaccine_catalog');
    }
};
