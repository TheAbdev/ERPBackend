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
        Schema::create('reorder_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->onDelete('cascade');
            $table->decimal('reorder_point', 15, 4); // When stock reaches this level
            $table->decimal('reorder_quantity', 15, 4); // Quantity to order
            $table->decimal('maximum_stock', 15, 4)->nullable(); // Maximum stock level
            $table->boolean('is_active')->default(true);
            $table->foreignId('supplier_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('product_id');
            $table->index('warehouse_id');
            $table->index('is_active');
            $table->index(['tenant_id', 'product_id', 'warehouse_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reorder_rules');
    }
};
