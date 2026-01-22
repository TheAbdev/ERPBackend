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
        if (Schema::hasTable('ecommerce_carts') && !Schema::hasColumn('ecommerce_carts', 'deleted_at')) {
            Schema::table('ecommerce_carts', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ecommerce_carts') && Schema::hasColumn('ecommerce_carts', 'deleted_at')) {
            Schema::table('ecommerce_carts', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
