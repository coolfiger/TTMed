<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('observation_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('notes', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('status', ['vacant', 'occupied'])->default('vacant');
            $table->timestamps();

            $table->unique(['branch_id', 'name']);
            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('observation_rooms');
    }
};
