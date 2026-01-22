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
        if (Schema::hasTable('ecommerce_product_sync') && !Schema::hasColumn('ecommerce_product_sync', 'deleted_at')) {
            Schema::table('ecommerce_product_sync', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ecommerce_product_sync') && Schema::hasColumn('ecommerce_product_sync', 'deleted_at')) {
            Schema::table('ecommerce_product_sync', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
