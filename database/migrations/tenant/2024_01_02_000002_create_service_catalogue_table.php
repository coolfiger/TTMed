<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::connection('tenant')->create('services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('code', 50)->unique();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price');       // stored in cents
            $table->unsignedBigInteger('cost')->default(0); // cost of goods
            $table->decimal('tax_rate', 5, 2)->default(0);  // e.g. 12.50 for 12.5%
            $table->boolean('taxable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // seeded defaults
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('service_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('services');
        Schema::connection('tenant')->dropIfExists('service_categories');
    }
};
