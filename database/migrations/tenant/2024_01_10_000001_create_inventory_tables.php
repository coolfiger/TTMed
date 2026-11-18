<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('inventory_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 30)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::connection('tenant')->create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('name', 200);
            $table->string('code', 50)->unique();
            $table->string('description', 500)->nullable();
            $table->string('unit', 30)->default('unit'); // unit, box, vial, ml, etc.
            $table->unsignedInteger('quantity_on_hand')->default(0);
            $table->unsignedInteger('reorder_level')->default(10);  // alert below this
            $table->unsignedInteger('reorder_quantity')->default(50); // suggested reorder qty
            $table->unsignedBigInteger('unit_cost')->default(0);    // cents
            $table->boolean('is_active')->default(true);
            $table->boolean('track_expiry')->default(false);
            $table->date('expiry_date')->nullable();
            $table->string('supplier', 200)->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('inventory_categories')->nullOnDelete();
        });

        // Every stock movement — receive, dispense, adjust, return, write-off
        Schema::connection('tenant')->create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_item_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('transaction_type', 30);
            // receive | dispense | adjust | return | write_off
            $table->integer('quantity');      // positive = in, negative = out
            $table->unsignedInteger('quantity_before');
            $table->unsignedInteger('quantity_after');
            $table->string('reference', 100)->nullable(); // order number, encounter ID, etc.
            $table->string('notes', 500)->nullable();
            $table->unsignedBigInteger('performed_by');
            $table->timestamps();

            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->cascadeOnDelete();
            $table->index(['inventory_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('inventory_transactions');
        Schema::connection('tenant')->dropIfExists('inventory_items');
        Schema::connection('tenant')->dropIfExists('inventory_categories');
    }
};
