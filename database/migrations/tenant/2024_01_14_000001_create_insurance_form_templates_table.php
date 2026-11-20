<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('insurance_form_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insurance_provider_id');
            $table->string('name', 150);
            $table->string('description', 300)->nullable();
            $table->string('file_path', 500);
            $table->string('original_name', 200);
            $table->string('mime_type', 100)->default('application/pdf');
            $table->unsignedInteger('file_size')->default(0);
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('insurance_provider_id')
                  ->references('id')->on('insurance_providers')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('insurance_form_templates');
    }
};
