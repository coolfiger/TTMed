<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('patient_number', 30)->unique();
            $table->string('title', 10)->nullable();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('middle_name', 80)->nullable();
            $table->string('preferred_name', 80)->nullable();
            $table->enum('gender', ['M', 'F', 'O'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('national_id', 50)->nullable()->index();
            $table->string('passport_number', 50)->nullable();
            $table->string('phone_primary', 30)->nullable()->index();
            $table->string('phone_secondary', 30)->nullable();
            $table->string('email', 150)->nullable()->index();
            $table->text('address')->nullable();
            $table->string('city', 80)->nullable();
            $table->string('country', 80)->nullable()->default('Trinidad and Tobago');
            $table->string('occupation', 100)->nullable();
            $table->string('employer', 150)->nullable();
            $table->string('marital_status', 20)->nullable();
            $table->string('blood_type', 5)->nullable();
            $table->unsignedBigInteger('insurance_provider_id')->nullable()->index();
            $table->string('insurance_policy_number', 100)->nullable();
            $table->string('insurance_group_number', 100)->nullable();
            $table->unsignedBigInteger('registered_by')->nullable();
            $table->unsignedBigInteger('primary_doctor_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_deceased')->default(false);
            $table->date('deceased_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('insurance_provider_id')->references('id')->on('insurance_providers')->nullOnDelete();
            $table->foreign('registered_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('primary_doctor_id')->references('id')->on('doctors')->nullOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();

            $table->index(['last_name', 'first_name']);
            $table->index(['date_of_birth']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('patients');
    }
};
