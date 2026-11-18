<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('encounters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('doctor_id')->nullable()->index();
            $table->unsignedBigInteger('queue_entry_id')->nullable();
            $table->string('visit_type', 50)->default('walk_in');
            $table->enum('status', [
                'checked_in',
                'awaiting_nurse',
                'with_nurse',
                'awaiting_doctor',
                'with_doctor',
                'completed',
                'cancelled',
                'no_show',
            ])->default('checked_in');
            $table->date('encounter_date');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('seen_by_nurse_at')->nullable();
            $table->timestamp('seen_by_doctor_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('checked_in_by')->nullable();
            $table->text('chief_complaint')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('doctor_id')->references('id')->on('doctors')->nullOnDelete();
            $table->foreign('checked_in_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('encounters');
    }
};
