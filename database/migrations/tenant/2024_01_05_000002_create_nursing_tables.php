<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('nursing_assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('encounter_id')->unique();
            $table->unsignedBigInteger('patient_id')->index();
            $table->unsignedBigInteger('nurse_id')->nullable();

            $table->text('chief_complaint')->nullable();
            $table->text('nursing_notes')->nullable();
            $table->text('allergies_confirmed')->nullable();
            $table->boolean('allergies_reviewed')->default(false);
            $table->boolean('medications_reviewed')->default(false);
            $table->text('medications_noted')->nullable();

            $table->enum('status', ['in_progress', 'completed'])->default('in_progress');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('encounter_id')->references('id')->on('encounters')->cascadeOnDelete();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('nurse_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::connection('tenant')->create('nursing_procedures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('encounter_id')->index();
            $table->unsignedBigInteger('patient_id')->index();
            $table->unsignedBigInteger('performed_by')->nullable();

            $table->string('procedure_type', 100);
            $table->string('procedure_name', 200);
            $table->text('details')->nullable();
            $table->text('outcome')->nullable();
            $table->string('site', 100)->nullable();
            $table->timestamp('performed_at')->nullable();
            $table->timestamps();

            $table->foreign('encounter_id')->references('id')->on('encounters')->cascadeOnDelete();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('performed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('nursing_procedures');
        Schema::connection('tenant')->dropIfExists('nursing_assessments');
    }
};
