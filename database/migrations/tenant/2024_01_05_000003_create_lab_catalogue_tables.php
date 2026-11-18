<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('lab_test_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::connection('tenant')->create('lab_tests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->string('name', 150);
            $table->string('code', 50)->unique();
            $table->string('loinc_code', 20)->nullable()->index();  // LOINC for LIS interop
            $table->string('specimen_type', 50)->nullable();         // blood, urine, swab, etc.
            $table->string('container', 50)->nullable();             // EDTA, plain, urine pot
            $table->string('unit', 30)->nullable();
            $table->string('reference_range_low', 50)->nullable();
            $table->string('reference_range_high', 50)->nullable();
            $table->text('reference_range_text')->nullable();        // free-text for complex ranges
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);           // cannot be deleted if true
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('lab_test_categories')->nullOnDelete();
        });

        Schema::connection('tenant')->create('lab_panels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::connection('tenant')->create('lab_panel_tests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('panel_id');
            $table->unsignedBigInteger('test_id');
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->unique(['panel_id', 'test_id']);
            $table->foreign('panel_id')->references('id')->on('lab_panels')->cascadeOnDelete();
            $table->foreign('test_id')->references('id')->on('lab_tests')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('lab_panel_tests');
        Schema::connection('tenant')->dropIfExists('lab_panels');
        Schema::connection('tenant')->dropIfExists('lab_tests');
        Schema::connection('tenant')->dropIfExists('lab_test_categories');
    }
};
