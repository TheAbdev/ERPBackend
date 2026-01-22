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
        if (Schema::hasTable('ecommerce_order_items') && !Schema::hasColumn('ecommerce_order_items', 'deleted_at')) {
            Schema::table('ecommerce_order_items', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ecommerce_order_items') && Schema::hasColumn('ecommerce_order_items', 'deleted_at')) {
            Schema::table('ecommerce_order_items', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};


