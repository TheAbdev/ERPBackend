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
        Schema::create('ecommerce_product_sync', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('store_id')->constrained('ecommerce_stores')->onDelete('cascade');
            $table->boolean('is_synced')->default(false);
            $table->boolean('store_visibility')->default(true); // Show/hide in store
            $table->decimal('ecommerce_price', 15, 2)->nullable(); // Override price for store
            $table->json('ecommerce_images')->nullable(); // Additional images for store
            $table->text('ecommerce_description')->nullable(); // Store-specific description
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'product_id', 'store_id']);
            $table->index('tenant_id');
            $table->index('product_id');
            $table->index('store_id');
            $table->index('is_synced');
            $table->index('store_visibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_product_sync');
    }
};



















