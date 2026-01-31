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
        if (Schema::hasTable('ecommerce_theme_pages')) {
            return;
        }

        Schema::create('ecommerce_theme_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->constrained('ecommerce_themes')->onDelete('cascade');
            $table->string('page_type'); // home, products, product, cart, checkout, account
            $table->string('title')->nullable();
            $table->json('content')->nullable(); // Page content blocks
            $table->json('draft_content')->nullable(); // Draft content for preview before publish
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('theme_id');
            $table->index('page_type');
            $table->index('is_published');
            $table->unique(['theme_id', 'page_type']); // Each theme can have only one page per type
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_theme_pages');
    }
};

