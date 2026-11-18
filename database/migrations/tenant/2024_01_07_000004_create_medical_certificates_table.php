<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('medical_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_number', 30)->unique();
            $table->unsignedBigInteger('encounter_id')->nullable()->index();
            $table->unsignedBigInteger('patient_id')->index();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('branch_id')->nullable();

            $table->enum('certificate_type', [
                'sick_leave', 'fit_for_work', 'fitness', 'medical_report', 'other',
            ])->default('sick_leave');

            $table->date('from_date');
            $table->date('to_date')->nullable();         // null = single day
            $table->string('diagnosis_text', 500)->nullable();
            $table->text('notes')->nullable();
            $table->text('instructions')->nullable();

            $table->string('pdf_path')->nullable();     // set once at issuance
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->timestamp('issued_at')->nullable();

            $table->timestamps();

            $table->foreign('encounter_id')->references('id')->on('encounters')->nullOnDelete();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
        });

        Schema::connection('tenant')->create('certificate_number_sequences', function (Blueprint $table) {
            $table->string('date_key', 10)->primary();
            $table->unsignedInteger('last_number')->default(0);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('certificate_number_sequences');
        Schema::connection('tenant')->dropIfExists('medical_certificates');
    }
};
