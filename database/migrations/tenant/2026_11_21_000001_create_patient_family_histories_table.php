<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('patient_family_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->enum('relationship', [
                'mother', 'father',
                'maternal_grandmother', 'maternal_grandfather',
                'paternal_grandmother', 'paternal_grandfather',
                'sibling', 'other',
            ]);
            $table->string('condition', 200);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('patient_family_histories');
    }
};
