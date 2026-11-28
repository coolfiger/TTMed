<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('observation_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('observation_order_id')->constrained('observation_orders')->cascadeOnDelete();
            $table->foreignId('encounter_id')->constrained('encounters')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();

            // Vitals snapshot at time of entry
            $table->unsignedSmallInteger('bp_systolic')->nullable();
            $table->unsignedSmallInteger('bp_diastolic')->nullable();
            $table->unsignedSmallInteger('pulse')->nullable();
            $table->decimal('temp_celsius', 4, 1)->nullable();
            $table->unsignedTinyInteger('spo2')->nullable();      // 0–100 %

            $table->text('notes')->nullable();
            $table->timestamp('occurred_at')->useCurrent();

            $table->index(['observation_order_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('observation_entries');
    }
};
