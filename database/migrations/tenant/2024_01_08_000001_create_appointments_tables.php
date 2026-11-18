<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Doctor working schedule (overrides doctor_operating_hours for slot-based booking)
        Schema::connection('tenant')->create('doctor_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('day_of_week'); // 0=Sun … 6=Sat
            $table->boolean('is_working_day')->default(true);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->smallInteger('slot_duration_minutes')->default(15);
            $table->smallInteger('max_patients_per_slot')->default(1);
            $table->smallInteger('max_patients_per_day')->nullable();
            $table->timestamps();
            $table->unique(['doctor_id', 'branch_id', 'day_of_week']);
        });

        // Scheduled breaks / recurring time-off blocks
        Schema::connection('tenant')->create('doctor_schedule_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('day_of_week'); // null = applies to a specific date only
            $table->time('break_start');
            $table->time('break_end');
            $table->string('label', 100)->nullable(); // "Lunch", "Admin time"
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Doctor-specific non-availability dates (leave, public holidays, etc.)
        Schema::connection('tenant')->create('doctor_unavailability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->date('unavailable_date');
            $table->time('from_time')->nullable(); // null = whole day
            $table->time('to_time')->nullable();
            $table->string('reason', 250)->nullable();
            $table->timestamps();
            $table->index(['doctor_id', 'unavailable_date']);
        });

        // Appointments
        Schema::connection('tenant')->create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_number', 30)->unique();
            $table->foreignId('patient_id')->constrained()->onDelete('restrict');
            $table->foreignId('doctor_id')->constrained()->onDelete('restrict');
            $table->foreignId('branch_id')->constrained()->onDelete('restrict');
            $table->foreignId('encounter_id')->nullable()->constrained()->onDelete('set null');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->smallInteger('duration_minutes')->default(15);
            $table->enum('appointment_type', ['scheduled', 'walk_in', 'follow_up', 'urgent'])->default('scheduled');
            $table->enum('status', [
                'scheduled',
                'confirmed',
                'arrived',
                'in_progress',
                'completed',
                'cancelled',
                'no_show',
                'rescheduled',
            ])->default('scheduled');
            $table->string('reason', 500)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('booked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('cancellation_reason', 500)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->boolean('reminder_sent')->default(false);
            $table->timestamp('reminder_sent_at')->nullable();
            // Follow-up linkage
            $table->foreignId('rescheduled_to')->nullable()->constrained('appointments')->onDelete('set null');
            $table->foreignId('rescheduled_from')->nullable()->constrained('appointments')->onDelete('set null');
            $table->timestamps();
            $table->index(['appointment_date', 'doctor_id', 'branch_id']);
            $table->index(['patient_id', 'appointment_date']);
            $table->index('status');
        });

        // Appointment number sequences
        Schema::connection('tenant')->create('appointment_number_sequences', function (Blueprint $table) {
            $table->string('date_key', 10)->primary();
            $table->unsignedInteger('last_number')->default(0);
        });

        // Follow-ups (doctor schedules next visit from within encounter)
        Schema::connection('tenant')->create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained()->onDelete('cascade');
            $table->foreignId('patient_id')->constrained()->onDelete('restrict');
            $table->foreignId('doctor_id')->constrained()->onDelete('restrict');
            $table->foreignId('branch_id')->constrained()->onDelete('restrict');
            $table->foreignId('appointment_id')->nullable()->constrained()->onDelete('set null');
            $table->date('target_date')->nullable();
            $table->smallInteger('follow_up_days')->nullable(); // "in 2 weeks"
            $table->string('reason', 500)->nullable();
            $table->text('instructions')->nullable();
            $table->enum('status', ['pending', 'booked', 'completed', 'cancelled'])->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->index(['doctor_id', 'status']);
            $table->index(['patient_id', 'status']);
        });

        // Appointment reminder email log
        Schema::connection('tenant')->create('appointment_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->string('recipient_email', 250);
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->string('smtp2go_message_id', 100)->nullable();
            $table->string('error_message', 500)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('appointment_reminders');
        Schema::connection('tenant')->dropIfExists('follow_ups');
        Schema::connection('tenant')->dropIfExists('appointment_number_sequences');
        Schema::connection('tenant')->dropIfExists('appointments');
        Schema::connection('tenant')->dropIfExists('doctor_unavailability');
        Schema::connection('tenant')->dropIfExists('doctor_schedule_breaks');
        Schema::connection('tenant')->dropIfExists('doctor_schedules');
    }
};
