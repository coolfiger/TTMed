<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('document_inbound_log', function (Blueprint $table) {
            $table->id();

            // What arrived
            $table->string('type', 30);                        // imaging_report, lab_result, patient_document, manual_upload
            $table->string('source', 30);                      // hl7_v2, vepro, fhir_r4, manual, patient_provided
            $table->string('reference_type', 60)->nullable();  // App\Models\Tenant\ImagingReport etc.
            $table->unsignedBigInteger('reference_id')->nullable();

            // Who it belongs to
            $table->unsignedBigInteger('patient_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable();

            // What it is
            $table->string('description', 300)->nullable();    // e.g. "X-Ray (DX) Chest — preliminary"
            $table->string('status', 30)->default('received'); // received, reviewed, critical, error

            // Critical flag
            $table->boolean('is_critical')->default(false);
            $table->text('critical_note')->nullable();

            // Tracking
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();                              // created_at = arrival time

            $table->index(['type', 'status', 'created_at']);
            $table->index(['patient_id', 'created_at']);
            $table->index(['is_critical', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('document_inbound_log');
    }
};
