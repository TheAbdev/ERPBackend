<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('ecommerce_product_sync') && Schema::hasColumn('ecommerce_product_sync', 'ecommerce_images')) {
            Schema::table('ecommerce_product_sync', function (Blueprint $table) {
                // First, convert existing JSON arrays to single string (take first image if exists)
                DB::statement('UPDATE ecommerce_product_sync SET ecommerce_images = JSON_EXTRACT(ecommerce_images, "$[0]") WHERE ecommerce_images IS NOT NULL AND JSON_VALID(ecommerce_images)');
                
                // Change column type from JSON to string
                $table->string('ecommerce_images')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ecommerce_product_sync') && Schema::hasColumn('ecommerce_product_sync', 'ecommerce_images')) {
            Schema::table('ecommerce_product_sync', function (Blueprint $table) {
                // Convert back to JSON array
                DB::statement('UPDATE ecommerce_product_sync SET ecommerce_images = JSON_ARRAY(ecommerce_images) WHERE ecommerce_images IS NOT NULL');
                
                $table->json('ecommerce_images')->nullable()->change();
            });
        }
    }
};



