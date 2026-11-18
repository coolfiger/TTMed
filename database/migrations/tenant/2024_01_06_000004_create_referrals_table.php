<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('encounter_id')->index();
            $table->unsignedBigInteger('patient_id')->index();
            $table->unsignedBigInteger('referred_by')->nullable();

            $table->string('referred_to_name', 200);       // specialist or facility name
            $table->string('referred_to_specialty', 150)->nullable();
            $table->string('referred_to_facility', 200)->nullable();
            $table->enum('urgency', ['routine', 'urgent', 'emergency'])->default('routine');
            $table->text('reason');
            $table->text('clinical_summary')->nullable();
            $table->text('instructions_to_patient')->nullable();

            $table->enum('status', ['pending', 'sent', 'accepted', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('appointment_date')->nullable();
            $table->text('feedback_notes')->nullable();    // response/feedback from specialist

            $table->timestamps();

            $table->foreign('encounter_id')->references('id')->on('encounters')->cascadeOnDelete();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('referred_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('referrals');
    }
};
