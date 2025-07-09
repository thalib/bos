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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['simple', 'variable', 'grouped', 'external'])->default('simple');
            $table->enum('publication_status', ['draft', 'published', 'discontinued', 'private'])->default('draft');
            $table->boolean('active')->default(true);
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('sku')->nullable()->unique();
            $table->string('barcode')->nullable();
            $table->string('brand')->default('ASENSAR');
            $table->decimal('cost', 10, 2)->default(0);
            $table->decimal('mrp', 10, 2)->default(0);
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('sale_price', 10, 2)->default(0);
            $table->boolean('taxable')->default(true);

            // GST Information
            $table->string('tax_hsn_code')->nullable();
            $table->decimal('tax_rate', 5, 2)->default(18);
            $table->boolean('tax_inclusive')->default(true);

            // Stock Information
            $table->boolean('stock_track')->default(false);
            $table->integer('stock_quantity')->default(0);
            $table->integer('stock_low_threshold')->default(0);

            // Dimensions
            $table->decimal('length', 8, 2)->nullable();
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('unit')->default('nos');

            // Shipping
            $table->decimal('shipping_weight', 8, 2)->nullable();
            $table->boolean('shipping_required')->default(true);
            $table->boolean('shipping_taxable')->default(true);
            $table->integer('shipping_class_id')->default(0);

            // Images and URLs
            $table->string('image')->nullable();
            $table->json('images')->nullable();
            $table->string('external_url')->nullable();

            // Relations (stored as JSON arrays for flexibility)
            $table->json('categories')->nullable();
            $table->json('tags')->nullable();
            $table->json('attributes')->nullable();
            $table->json('variations')->nullable();
            $table->json('meta_data')->nullable();
            $table->json('related_ids')->nullable();
            $table->json('upsell_ids')->nullable();
            $table->json('cross_sell_ids')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['publication_status', 'active']);
            $table->index('type');
            $table->index('brand');
            $table->index('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
