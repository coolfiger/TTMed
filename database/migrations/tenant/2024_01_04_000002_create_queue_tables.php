<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('queue_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('encounter_id')->unique();
            $table->unsignedBigInteger('patient_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('doctor_id')->nullable()->index();
            $table->unsignedSmallInteger('queue_number');
            $table->date('queue_date');
            $table->string('visit_type', 50)->default('walk_in');
            $table->enum('status', [
                'waiting',
                'with_nurse',
                'awaiting_doctor',
                'with_doctor',
                'completed',
                'cancelled',
                'no_show',
            ])->default('waiting');
            $table->boolean('is_priority')->default(false);
            $table->string('priority_reason', 200)->nullable();
            $table->unsignedBigInteger('checked_in_by')->nullable();
            $table->timestamp('called_at')->nullable();
            $table->timestamps();

            $table->foreign('encounter_id')->references('id')->on('encounters')->cascadeOnDelete();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->foreign('doctor_id')->references('id')->on('doctors')->nullOnDelete();
            $table->foreign('checked_in_by')->references('id')->on('users')->nullOnDelete();

            $table->unique(['branch_id', 'queue_date', 'queue_number']);
            $table->index(['branch_id', 'queue_date', 'status']);
        });

        Schema::connection('tenant')->create('queue_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('queue_entry_id')->index();
            $table->string('event_type', 50);
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50)->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('occurred_at')->useCurrent();

            $table->foreign('queue_entry_id')->references('id')->on('queue_entries')->cascadeOnDelete();
            $table->foreign('performed_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::connection('tenant')->create('queue_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->index();
            $table->date('queue_date');
            $table->unsignedSmallInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'queue_date']);
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
        });

        Schema::connection('tenant')->create('doctor_availability', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->enum('status', [
                'available',
                'busy',
                'on_break',
                'off_duty',
                'not_accepting',
            ])->default('available');
            $table->string('status_note', 200)->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['doctor_id', 'branch_id']);
            $table->foreign('doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('doctor_availability');
        Schema::connection('tenant')->dropIfExists('queue_number_sequences');
        Schema::connection('tenant')->dropIfExists('queue_events');
        Schema::connection('tenant')->dropIfExists('queue_entries');
    }
};
