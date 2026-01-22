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
        Schema::create('storefront_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('store_id')->constrained('ecommerce_stores')->onDelete('cascade');
            $table->string('name')->default('Home Layout');
            $table->string('slug')->default('home');
            $table->json('layout_json');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('store_id');
            $table->index('slug');
            $table->index('is_published');
            $table->unique(['store_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storefront_layouts');
    }
};

