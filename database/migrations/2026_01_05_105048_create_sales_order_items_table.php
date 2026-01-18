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
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('sales_order_id')->constrained('sales_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->onDelete('restrict');
            $table->foreignId('unit_of_measure_id')->constrained('units_of_measure')->onDelete('restrict');
            $table->decimal('quantity', 15, 4);
            $table->decimal('base_quantity', 15, 4); // Quantity in base unit
            $table->decimal('unit_price', 15, 4);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('line_total', 15, 4); // (quantity * unit_price) - discount + tax
            $table->decimal('delivered_quantity', 15, 4)->default(0);
            $table->text('notes')->nullable();
            $table->integer('line_number')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('sales_order_id');
            $table->index('product_id');
            $table->index(['tenant_id', 'sales_order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
    }
};
