<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('title', 20)->nullable();             // Dr., Mr., Ms., etc.
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('gender', 10)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('emergency_contact_name', 150)->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->string('profile_photo_path', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });

        Schema::connection('tenant')->create('doctors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_profile_id')->unique();
            $table->string('registration_number', 100)->nullable();
            $table->string('specialty', 150)->nullable();
            $table->text('qualifications')->nullable();
            $table->string('signature_path', 500)->nullable();
            $table->string('stamp_path', 500)->nullable();
            $table->string('prescription_logo_path', 500)->nullable();
            $table->text('prescription_header')->nullable();
            $table->text('prescription_footer')->nullable();
            $table->string('consultation_fee_code', 50)->nullable(); // links to service catalogue
            $table->boolean('available_for_appointments')->default(true);
            $table->timestamps();

            $table->foreign('staff_profile_id')->references('id')->on('staff_profiles')->cascadeOnDelete();
        });

        Schema::connection('tenant')->create('doctor_operating_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedTinyInteger('day_of_week'); // 0=Sun … 6=Sat
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);

            $table->unique(['doctor_id', 'day_of_week']);
            $table->foreign('doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('doctor_operating_hours');
        Schema::connection('tenant')->dropIfExists('doctors');
        Schema::connection('tenant')->dropIfExists('staff_profiles');
    }
};
