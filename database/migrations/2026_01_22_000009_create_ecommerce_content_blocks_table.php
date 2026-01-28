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
        Schema::create('ecommerce_content_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('store_id')->nullable()->constrained('ecommerce_stores')->onDelete('cascade');
            $table->string('type'); // text, image, products_grid, hero, video, html, form
            $table->string('name')->nullable();
            $table->json('content')->nullable(); // Block content data
            $table->json('settings')->nullable(); // Block settings (styles, config)
            $table->boolean('is_reusable')->default(false); // Can be reused across pages
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('store_id');
            $table->index('type');
            $table->index('is_reusable');
            $table->index(['tenant_id', 'store_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_content_blocks');
    }
};



















