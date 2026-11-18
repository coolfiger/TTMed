<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('vital_signs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id')->index();
            $table->unsignedBigInteger('encounter_id')->nullable()->index();
            $table->unsignedBigInteger('recorded_by')->nullable();

            // Cardiovascular
            $table->unsignedSmallInteger('systolic_bp')->nullable();
            $table->unsignedSmallInteger('diastolic_bp')->nullable();
            $table->unsignedSmallInteger('pulse_rate')->nullable();
            $table->string('pulse_rhythm', 20)->nullable(); // regular, irregular

            // Respiratory
            $table->unsignedSmallInteger('respiratory_rate')->nullable();
            $table->unsignedSmallInteger('oxygen_saturation')->nullable(); // %

            // Temperature
            $table->decimal('temperature', 4, 1)->nullable();
            $table->string('temperature_site', 20)->nullable(); // oral, axillary, rectal, tympanic

            // Anthropometrics
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->decimal('bmi', 5, 2)->nullable(); // computed on save

            // Glucose / other
            $table->decimal('blood_glucose', 5, 1)->nullable(); // mmol/L
            $table->string('blood_glucose_timing', 20)->nullable(); // fasting, random, post_meal

            // Pain
            $table->unsignedTinyInteger('pain_score')->nullable(); // 0-10

            $table->text('notes')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('encounter_id')->references('id')->on('encounters')->nullOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('vital_signs');
    }
};
