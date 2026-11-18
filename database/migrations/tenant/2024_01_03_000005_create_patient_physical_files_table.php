<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('patient_physical_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id')->unique();
            $table->string('file_number', 50)->nullable()->unique();
            $table->string('location_cabinet', 50)->nullable();
            $table->string('location_shelf', 50)->nullable();
            $table->string('location_drawer', 50)->nullable();
            $table->string('current_location_note', 200)->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
        });

        Schema::connection('tenant')->create('patient_file_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_physical_file_id')->index();
            $table->string('from_location', 200)->nullable();
            $table->string('to_location', 200);
            $table->string('reason', 200)->nullable();
            $table->unsignedBigInteger('moved_by')->nullable();
            $table->timestamp('moved_at')->useCurrent();

            $table->foreign('patient_physical_file_id')->references('id')->on('patient_physical_files')->cascadeOnDelete();
            $table->foreign('moved_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::connection('tenant')->create('patient_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 20)->default('PT');
            $table->unsignedBigInteger('last_number')->default(0);
            $table->unsignedTinyInteger('pad_length')->default(6);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('patient_file_movements');
        Schema::connection('tenant')->dropIfExists('patient_physical_files');
        Schema::connection('tenant')->dropIfExists('patient_number_sequences');
    }
};
