<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        // Sequential number per day
        Schema::connection('tenant')->create('invoice_number_sequences', function (Blueprint $table) {
            $table->string('date_key', 10)->primary();
            $table->unsignedInteger('last_number')->default(0);
        });

        // Invoices
        Schema::connection('tenant')->create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 30)->unique();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('encounter_id')->nullable();
            $table->unsignedBigInteger('insurance_provider_id')->nullable();
            $table->string('status', 20)->default('draft');
            // draft | finalized | paid | partially_paid | cancelled | void
            $table->unsignedBigInteger('subtotal')->default(0);  // cents
            $table->unsignedBigInteger('tax_total')->default(0); // cents
            $table->unsignedBigInteger('discount')->default(0);  // cents
            $table->unsignedBigInteger('total')->default(0);     // cents
            $table->unsignedBigInteger('amount_paid')->default(0);
            $table->unsignedBigInteger('amount_due')->default(0);
            $table->unsignedBigInteger('insurance_portion')->default(0);
            $table->unsignedBigInteger('patient_portion')->default(0);
            $table->string('notes', 500)->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('finalized_by')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index('encounter_id');
        });

        // Invoice line items
        Schema::connection('tenant')->create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('description', 300);
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_price');  // cents — snapshot at billing time
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('line_total');  // cents
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
        });

        // Insurance claims against invoices
        Schema::connection('tenant')->create('insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('insurance_provider_id');
            $table->string('claim_reference', 100)->nullable();
            $table->string('status', 20)->default('pending');
            // pending | submitted | approved | partial | rejected | paid
            $table->unsignedBigInteger('claimed_amount');   // cents
            $table->unsignedBigInteger('approved_amount')->default(0);
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->date('submitted_date')->nullable();
            $table->date('approved_date')->nullable();
            $table->date('paid_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
        });

        // Cashier shifts
        Schema::connection('tenant')->create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('cashier_id');   // user_id
            $table->string('status', 20)->default('open');
            // open | closed | approved
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('opening_float')->default(0);  // cents
            $table->unsignedBigInteger('closing_float')->nullable();  // cents
            $table->unsignedBigInteger('expected_cash')->default(0);  // computed on close
            $table->unsignedBigInteger('actual_cash')->nullable();    // counted on close
            $table->bigInteger('variance')->nullable();               // signed — can be negative
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['cashier_id', 'status']);
        });

        // Payments against invoices
        Schema::connection('tenant')->create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_reference', 30)->unique();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('cashier_shift_id')->nullable();
            $table->string('payment_method', 30);
            // cash | card | linx | cheque | insurance | waiver | other
            $table->unsignedBigInteger('amount');         // cents
            $table->string('reference_number', 100)->nullable(); // card auth / cheque no.
            $table->string('notes', 300)->nullable();
            $table->unsignedBigInteger('collected_by');
            $table->boolean('voided')->default(false);
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason', 300)->nullable();
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices');
            $table->index(['invoice_id']);
            $table->index(['cashier_shift_id']);
        });

        // Payment reference sequences
        Schema::connection('tenant')->create('payment_reference_sequences', function (Blueprint $table) {
            $table->string('date_key', 10)->primary();
            $table->unsignedInteger('last_number')->default(0);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('payment_reference_sequences');
        Schema::connection('tenant')->dropIfExists('payments');
        Schema::connection('tenant')->dropIfExists('cashier_shifts');
        Schema::connection('tenant')->dropIfExists('insurance_claims');
        Schema::connection('tenant')->dropIfExists('invoice_items');
        Schema::connection('tenant')->dropIfExists('invoices');
        Schema::connection('tenant')->dropIfExists('invoice_number_sequences');
    }
};
