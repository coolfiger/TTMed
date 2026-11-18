<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('consultation_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('encounter_id')->unique();
            $table->unsignedBigInteger('patient_id')->index();
            $table->unsignedBigInteger('doctor_id')->nullable()->index();

            // SOAP
            $table->text('subjective')->nullable();   // history of presenting complaint, patient's own words
            $table->text('objective')->nullable();    // clinical findings from examination
            $table->text('assessment')->nullable();   // clinical impression / working diagnosis
            $table->text('plan')->nullable();         // management plan

            // Additional clinical fields
            $table->text('examination_findings')->nullable();
            $table->text('investigations_ordered')->nullable();  // free-text summary of orders
            $table->text('follow_up_instructions')->nullable();
            $table->integer('follow_up_days')->nullable();      // suggested return in X days

            // Finalisation
            $table->boolean('is_finalized')->default(false);
            $table->unsignedBigInteger('finalized_by')->nullable();
            $table->timestamp('finalized_at')->nullable();

            // Soft integrity: once finalized, record a hash so silent edits are detectable
            $table->string('content_hash', 64)->nullable();

            $table->timestamps();

            $table->foreign('encounter_id')->references('id')->on('encounters')->cascadeOnDelete();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('doctor_id')->references('id')->on('doctors')->nullOnDelete();
            $table->foreign('finalized_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('consultation_notes');
    }
};
