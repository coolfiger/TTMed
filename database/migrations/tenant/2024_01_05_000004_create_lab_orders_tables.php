<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('lab_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();           // e.g. LAB-20250115-001
            $table->unsignedBigInteger('patient_id')->index();
            $table->unsignedBigInteger('encounter_id')->nullable()->index();
            $table->unsignedBigInteger('ordered_by')->nullable();   // doctor user_id
            $table->unsignedBigInteger('branch_id')->nullable();

            $table->enum('status', [
                'pending',
                'specimen_collected',
                'sent_to_lab',
                'processing',
                'partial_results',
                'results_received',
                'results_reviewed',
                'cancelled',
            ])->default('pending');

            $table->enum('order_type', ['stat', 'routine', 'fasting'])->default('routine');
            $table->text('clinical_notes')->nullable();

            // Integration fields
            $table->string('lis_placer_order_number', 50)->nullable()->index(); // our ID sent to LIS
            $table->string('lis_filler_order_number', 50)->nullable()->index(); // LIS's own ID
            $table->string('integration_type', 30)->nullable();    // native, hl7_v2, fhir_r4

            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->unsignedBigInteger('collected_by')->nullable();
            $table->timestamp('sent_to_lab_at')->nullable();
            $table->timestamp('results_received_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('encounter_id')->references('id')->on('encounters')->nullOnDelete();
            $table->foreign('ordered_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('collected_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::connection('tenant')->create('lab_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lab_order_id')->index();
            $table->unsignedBigInteger('lab_test_id')->nullable();
            $table->unsignedBigInteger('lab_panel_id')->nullable();
            $table->string('test_name', 150);                       // denormalized for history
            $table->string('test_code', 50)->nullable();
            $table->string('loinc_code', 20)->nullable();
            $table->timestamps();

            $table->foreign('lab_order_id')->references('id')->on('lab_orders')->cascadeOnDelete();
            $table->foreign('lab_test_id')->references('id')->on('lab_tests')->nullOnDelete();
            $table->foreign('lab_panel_id')->references('id')->on('lab_panels')->nullOnDelete();
        });

        Schema::connection('tenant')->create('lab_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lab_order_id')->index();
            $table->unsignedBigInteger('lab_order_item_id')->nullable()->index();
            $table->unsignedBigInteger('patient_id')->index();      // direct link for queries
            $table->unsignedBigInteger('lab_test_id')->nullable();

            $table->string('test_name', 150);                       // denormalized
            $table->string('test_code', 50)->nullable();
            $table->string('loinc_code', 20)->nullable();

            $table->text('value')->nullable();                      // string to handle all types
            $table->string('value_unit', 30)->nullable();
            $table->string('reference_range', 100)->nullable();
            $table->enum('flag', ['normal', 'low', 'high', 'critical_low', 'critical_high', 'abnormal'])->default('normal');
            $table->boolean('is_critical')->default(false);

            $table->text('interpretation_notes')->nullable();
            $table->string('performing_lab', 150)->nullable();      // lab name that ran the test
            $table->timestamp('result_datetime')->nullable();

            // Integration source tracking
            $table->string('source', 20)->default('manual');        // manual, hl7_v2, fhir_r4
            $table->text('raw_message')->nullable();                // original HL7/FHIR payload (for audit)

            $table->timestamps();

            $table->foreign('lab_order_id')->references('id')->on('lab_orders')->cascadeOnDelete();
            $table->foreign('lab_order_item_id')->references('id')->on('lab_order_items')->nullOnDelete();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('lab_test_id')->references('id')->on('lab_tests')->nullOnDelete();
        });

        Schema::connection('tenant')->create('lab_result_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lab_order_id')->index();
            $table->unsignedBigInteger('patient_id')->index();
            $table->string('file_name', 255);
            $table->string('storage_path', 500);                    // relative to tenant storage root
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('lab_order_id')->references('id')->on('lab_orders')->cascadeOnDelete();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::connection('tenant')->create('lab_order_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->date('order_date')->unique();
            $table->unsignedMediumInteger('last_number')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('lab_order_number_sequences');
        Schema::connection('tenant')->dropIfExists('lab_result_attachments');
        Schema::connection('tenant')->dropIfExists('lab_results');
        Schema::connection('tenant')->dropIfExists('lab_order_items');
        Schema::connection('tenant')->dropIfExists('lab_orders');
    }
};
