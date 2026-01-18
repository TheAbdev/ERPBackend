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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->onDelete('cascade');
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->onDelete('set null');
            $table->string('transaction_type'); // opening_balance, adjustment, transfer, receipt, issue
            $table->string('reference_type')->nullable(); // e.g., 'purchase_order', 'sales_order', 'adjustment'
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('quantity', 15, 4); // Positive for receipts, negative for issues
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('total_cost', 15, 4)->default(0); // quantity * unit_cost
            $table->foreignId('unit_of_measure_id')->constrained('units_of_measure')->onDelete('restrict');
            $table->decimal('base_quantity', 15, 4); // Quantity in base unit of measure
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('transaction_date');
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('warehouse_id');
            $table->index('product_id');
            $table->index('product_variant_id');
            $table->index('batch_id');
            $table->index('transaction_type');
            $table->index('transaction_date');
            $table->index(['reference_type', 'reference_id']);
            $table->index(['tenant_id', 'warehouse_id', 'product_id'], 'idx_inv_trans_tenant_wh_product');
            $table->index(['tenant_id', 'transaction_type', 'transaction_date'], 'idx_inv_trans_tenant_type_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
