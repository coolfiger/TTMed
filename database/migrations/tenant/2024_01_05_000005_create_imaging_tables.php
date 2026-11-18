<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('imaging_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();
            $table->unsignedBigInteger('patient_id')->index();
            $table->unsignedBigInteger('encounter_id')->nullable()->index();
            $table->unsignedBigInteger('ordered_by')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();

            $table->string('modality', 20);                         // CT, MR, CR, US, XA, NM, etc.
            $table->string('body_part', 100)->nullable();
            $table->string('laterality', 10)->nullable();           // left, right, bilateral
            $table->text('clinical_indication')->nullable();
            $table->text('instructions')->nullable();
            $table->enum('priority', ['routine', 'urgent', 'stat'])->default('routine');

            $table->enum('status', [
                'pending',
                'sent_to_ris',
                'scheduled',
                'in_progress',
                'completed',
                'report_available',
                'report_reviewed',
                'cancelled',
            ])->default('pending');

            // RIS/PACS integration fields
            $table->string('accession_number', 50)->nullable()->unique();
            $table->string('ris_order_id', 50)->nullable()->index();    // RIS internal ID
            $table->string('integration_type', 30)->nullable();         // native, hl7_v2, vepro
            $table->string('hl7_placer_order_number', 50)->nullable();

            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('performed_at')->nullable();
            $table->timestamp('report_received_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('encounter_id')->references('id')->on('encounters')->nullOnDelete();
            $table->foreign('ordered_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::connection('tenant')->create('imaging_studies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('imaging_order_id')->index();
            $table->unsignedBigInteger('patient_id')->index();

            $table->string('study_instance_uid', 100)->nullable()->unique(); // DICOM UID
            $table->string('modality', 20);
            $table->string('study_description', 200)->nullable();
            $table->integer('number_of_series')->nullable();
            $table->integer('number_of_instances')->nullable();

            // PACS viewer link (never a public URL — requires auth redirect)
            $table->string('pacs_study_url', 500)->nullable();      // internal PACS viewer URL
            $table->string('pacs_system', 50)->nullable();          // vepro, dcm4chee, etc.

            $table->timestamp('study_datetime')->nullable();
            $table->timestamps();

            $table->foreign('imaging_order_id')->references('id')->on('imaging_orders')->cascadeOnDelete();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
        });

        Schema::connection('tenant')->create('imaging_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('imaging_order_id')->unique(); // one report per order
            $table->unsignedBigInteger('patient_id')->index();

            $table->string('radiologist_name', 150)->nullable();
            $table->string('report_status', 30)->default('preliminary'); // preliminary, final, amended
            $table->text('findings')->nullable();
            $table->text('impression')->nullable();
            $table->text('recommendations')->nullable();

            // Stored report PDF (private, never public)
            $table->string('report_pdf_path', 500)->nullable();
            $table->unsignedBigInteger('report_pdf_size')->nullable();

            // Integration source
            $table->string('source', 20)->default('manual');        // manual, hl7_v2, vepro, fhir
            $table->text('raw_message')->nullable();                // original HL7/FHIR for audit

            $table->boolean('is_critical')->default(false);
            $table->text('critical_finding')->nullable();

            $table->timestamp('report_datetime')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamps();

            $table->foreign('imaging_order_id')->references('id')->on('imaging_orders')->cascadeOnDelete();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::connection('tenant')->create('imaging_order_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->date('order_date')->unique();
            $table->unsignedMediumInteger('last_number')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('imaging_order_number_sequences');
        Schema::connection('tenant')->dropIfExists('imaging_reports');
        Schema::connection('tenant')->dropIfExists('imaging_studies');
        Schema::connection('tenant')->dropIfExists('imaging_orders');
    }
};
