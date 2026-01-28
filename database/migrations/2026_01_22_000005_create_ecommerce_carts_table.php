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
        Schema::create('ecommerce_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('store_id')->constrained('ecommerce_stores')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('ecommerce_customers')->onDelete('cascade');
            $table->string('session_id')->nullable(); // For guest carts
            $table->json('items')->nullable(); // Cart items
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('shipping', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('store_id');
            $table->index('customer_id');
            $table->index('session_id');
            $table->index('expires_at');
            $table->index(['tenant_id', 'store_id', 'session_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_carts');
    }
};



















