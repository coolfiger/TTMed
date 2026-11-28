<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('observation_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained('encounters')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('ordered_by')->constrained('users')->restrictOnDelete();

            $table->string('reason');                        // e.g. "Dehydration — IV rehydration"
            $table->text('treatment_notes')->nullable();     // what the doctor ordered
            $table->string('room_name', 50)->nullable();     // "Obs Room 1", free text for now

            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('observation_orders');
    }
};
