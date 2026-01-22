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
        if (Schema::hasTable('ecommerce_stores')) {
            return;
        }

        Schema::create('ecommerce_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->string('domain')->nullable();
            $table->string('slug')->unique();
            if (Schema::hasTable('ecommerce_themes')) {
                $table->foreignId('theme_id')->nullable()->constrained('ecommerce_themes')->onDelete('set null');
            } else {
                $table->unsignedBigInteger('theme_id')->nullable();
            }
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // Store settings (currency, language, etc.)
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('slug');
            $table->index('is_active');
            $table->index(['tenant_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_stores');
    }
};

