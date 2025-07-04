<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('estimates', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('ESTIMATE');
            $table->string('number')->unique();
            $table->date('date');
            $table->integer('validity')->default(5);
            $table->enum('status', ['DRAFT', 'SENT', 'ACCEPTED', 'REJECTED', 'EXPIRED', 'INVOICED'])->default('DRAFT');
            $table->boolean('active')->default(true);
            $table->string('branch_id');
            $table->string('channel');
            $table->string('salesperson');
            $table->string('refrence')->default('PO-2025-0010');

            // Flattened options fields
            $table->boolean('tax_inclusive')->default(false);
            $table->boolean('show_bank_details')->default(true);
            $table->string('bank_id')->nullable();
            $table->boolean('show_signature')->default(true);
            $table->boolean('show_upi_qr')->default(true);

            // Customer information as JSON
            $table->string('customer_id');
            $table->json('customer_billing');
            $table->json('customer_shipping');

            // Line items as JSON array
            $table->json('items');

            // Flattened totals fields
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('taxable_amount', 15, 2)->default(0);
            $table->decimal('total_tax', 15, 2)->default(0);
            $table->decimal('shipping_charges', 15, 2)->default(0);
            $table->decimal('other_charges', 15, 2)->default(0);
            $table->decimal('adjustment', 15, 2)->default(0);
            $table->decimal('round_off', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);

            // Terms and additional information
            $table->text('terms')->nullable();
            $table->text('notes')->nullable();

            // Audit fields
            $table->string('created_by');
            $table->string('updated_by');
            $table->timestamps();

            // Indexes for performance
            $table->index(['status', 'date']);
            $table->index('customer_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimates');
    }
};
