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
        Schema::create('ecommerce_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('store_id')->constrained('ecommerce_stores')->onDelete('cascade');
            $table->string('email')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->json('addresses')->nullable(); // Billing and shipping addresses
            $table->string('password')->nullable(); // For registered customers
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('store_id');
            $table->index('email');
            $table->index('is_active');
            $table->index(['tenant_id', 'store_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_customers');
    }
};







