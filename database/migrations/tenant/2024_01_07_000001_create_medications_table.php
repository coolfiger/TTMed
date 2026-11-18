<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('medications', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);                        // display name
            $table->string('generic_name', 200)->nullable();    // generic / INN
            $table->json('brand_names')->nullable();            // known brand names
            $table->string('strength', 100)->nullable();        // e.g. "500mg", "5mg/5ml"
            $table->string('strength_unit', 50)->nullable();
            $table->enum('form', [
                'tablet', 'capsule', 'liquid', 'syrup', 'suspension',
                'injection', 'cream', 'ointment', 'gel', 'inhaler',
                'drops', 'suppository', 'patch', 'powder', 'other',
            ])->default('tablet');
            $table->enum('route', [
                'oral', 'topical', 'injection', 'inhalation',
                'ophthalmic', 'otic', 'nasal', 'rectal', 'sublingual', 'other',
            ])->default('oral');
            $table->string('drug_class', 150)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_custom')->default(false);       // tenant-added vs seeded
            $table->timestamps();

            $table->index(['name', 'generic_name']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('medications');
    }
};
