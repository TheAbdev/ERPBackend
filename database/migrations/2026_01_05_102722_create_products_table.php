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
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->onDelete('set null');
            $table->string('sku'); // Stock Keeping Unit
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('barcode')->nullable();
            $table->foreignId('unit_of_measure_id')->constrained('units_of_measure')->onDelete('restrict');
            $table->boolean('is_tracked')->default(true); // Track inventory
            $table->boolean('is_serialized')->default(false); // Serial number tracking
            $table->boolean('is_batch_tracked')->default(false); // Batch/lot tracking
            $table->string('type')->default('stock'); // stock, service, kit, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('category_id');
            $table->index('sku');
            $table->index('barcode');
            $table->index('is_tracked');
            $table->index('is_active');
            $table->index(['tenant_id', 'category_id']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'is_tracked']);

            // Unique constraint: SKU must be unique per tenant
            $table->unique(['tenant_id', 'sku']);
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
