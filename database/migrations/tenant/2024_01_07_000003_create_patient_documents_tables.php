<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('patient_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id')->index();
            $table->unsignedBigInteger('encounter_id')->nullable()->index();
            $table->unsignedBigInteger('uploaded_by');
            $table->unsignedBigInteger('document_category_id')->nullable();

            $table->string('title', 250);
            $table->text('description')->nullable();
            $table->string('original_filename', 500);
            $table->string('storage_path', 1000);       // absolute private path; never served via public URL
            $table->string('mime_type', 100);
            $table->unsignedInteger('file_size_bytes')->default(0);
            $table->unsignedSmallInteger('version')->default(1);

            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('encounter_id')->references('id')->on('encounters')->nullOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::connection('tenant')->create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_document_id')->index();
            $table->unsignedSmallInteger('version_number');
            $table->unsignedBigInteger('uploaded_by');
            $table->string('storage_path', 1000);
            $table->string('original_filename', 500);
            $table->unsignedInteger('file_size_bytes')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('patient_document_id')->references('id')->on('patient_documents')->cascadeOnDelete();
        });

        Schema::connection('tenant')->create('document_access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_document_id')->index();
            $table->unsignedBigInteger('accessed_by')->nullable();
            $table->enum('access_type', ['view', 'download', 'print'])->default('view');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('patient_document_id')->references('id')->on('patient_documents')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('document_access_logs');
        Schema::connection('tenant')->dropIfExists('document_versions');
        Schema::connection('tenant')->dropIfExists('patient_documents');
    }
};
