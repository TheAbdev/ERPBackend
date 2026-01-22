<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('ecommerce_product_sync') || !Schema::hasColumn('ecommerce_product_sync', 'ecommerce_images')) {
            return;
        }

        $constraints = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_NAME = 'ecommerce_product_sync.ecommerce_images'"
        );
        if (!empty($constraints)) {
            DB::statement('ALTER TABLE ecommerce_product_sync DROP CHECK `ecommerce_product_sync.ecommerce_images`');
        }

        Schema::table('ecommerce_product_sync', function (Blueprint $table) {
            $table->string('ecommerce_images')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('ecommerce_product_sync') || !Schema::hasColumn('ecommerce_product_sync', 'ecommerce_images')) {
            return;
        }

        Schema::table('ecommerce_product_sync', function (Blueprint $table) {
            $table->json('ecommerce_images')->nullable()->change();
        });
    }
};





