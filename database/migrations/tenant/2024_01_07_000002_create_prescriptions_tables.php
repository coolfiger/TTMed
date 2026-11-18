<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->string('prescription_number', 30)->unique();
            $table->unsignedBigInteger('encounter_id')->index();
            $table->unsignedBigInteger('patient_id')->index();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('branch_id')->nullable();

            $table->enum('status', ['draft', 'issued', 'cancelled'])->default('draft');

            $table->boolean('allergy_checked')->default(false);
            $table->text('allergy_check_notes')->nullable();

            $table->string('pdf_path')->nullable();             // absolute private path; set only once at issuance

            $table->unsignedBigInteger('issued_by')->nullable();
            $table->timestamp('issued_at')->nullable();

            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();

            $table->timestamps();

            $table->foreign('encounter_id')->references('id')->on('encounters')->cascadeOnDelete();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
        });

        Schema::connection('tenant')->create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prescription_id')->index();
            $table->unsignedBigInteger('medication_id')->nullable();    // nullable: freetext fallback

            // Denormalized at prescription time for historical accuracy
            $table->string('medication_name', 200);
            $table->string('strength', 100)->nullable();
            $table->string('form', 50)->nullable();
            $table->string('route', 50)->nullable();

            $table->string('dose', 100);                        // e.g. "1 tablet", "5ml"
            $table->string('frequency', 100);                   // e.g. "twice daily", "every 8 hours"
            $table->unsignedSmallInteger('duration')->nullable();
            $table->enum('duration_unit', ['days', 'weeks', 'months'])->default('days');
            $table->string('quantity', 50)->nullable();         // e.g. "21 tablets"
            $table->text('instructions')->nullable();
            $table->unsignedTinyInteger('refills_allowed')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('prescription_id')->references('id')->on('prescriptions')->cascadeOnDelete();
        });

        Schema::connection('tenant')->create('prescription_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->index();
            $table->string('name', 150);
            $table->json('items');      // array of item data matching prescription_items columns
            $table->timestamps();
        });

        Schema::connection('tenant')->create('prescription_number_sequences', function (Blueprint $table) {
            $table->string('date_key', 10)->primary();          // YYYY-MM-DD
            $table->unsignedInteger('last_number')->default(0);
        });

        Schema::connection('tenant')->create('prescription_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prescription_id')->index();
            $table->enum('delivery_type', ['email', 'print'])->default('email');
            $table->string('recipient_email', 200)->nullable();
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed', 'bounced'])->default('pending');
            $table->string('smtp2go_message_id', 100)->nullable()->index();
            $table->text('error_message')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->foreign('prescription_id')->references('id')->on('prescriptions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('prescription_delivery_logs');
        Schema::connection('tenant')->dropIfExists('prescription_number_sequences');
        Schema::connection('tenant')->dropIfExists('prescription_templates');
        Schema::connection('tenant')->dropIfExists('prescription_items');
        Schema::connection('tenant')->dropIfExists('prescriptions');
    }
};
