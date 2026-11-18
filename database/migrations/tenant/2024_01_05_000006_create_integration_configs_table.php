<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('integration_configs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30);                     // lab_lis, imaging_ris, imaging_pacs
            $table->string('provider', 50)->nullable();     // vepro, meditech, sunquest, etc.
            $table->string('protocol', 20)->default('hl7_v2'); // hl7_v2, fhir_r4, http, mllp

            // Connection
            $table->string('endpoint_url', 500)->nullable();      // HTTP endpoint
            $table->string('mllp_host', 150)->nullable();         // MLLP host
            $table->unsignedSmallInteger('mllp_port')->nullable(); // MLLP port (default 2575)

            // HL7 sending application/facility identifiers
            $table->string('sending_application', 50)->nullable();
            $table->string('sending_facility', 50)->nullable();
            $table->string('receiving_application', 50)->nullable();
            $table->string('receiving_facility', 50)->nullable();

            // Auth — values stored encrypted via centre_settings pattern
            $table->string('api_key_encrypted', 500)->nullable();
            $table->string('inbound_secret_encrypted', 500)->nullable(); // HMAC secret for inbound

            // Status
            $table->boolean('is_active')->default(false);
            $table->boolean('send_orders')->default(false);       // push orders to external system
            $table->boolean('receive_results')->default(true);    // accept inbound from external

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['type', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('integration_configs');
    }
};
