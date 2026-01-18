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
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->decimal('quantity_delivered', 15, 4)->default(0)->after('quantity');
            $table->decimal('quantity_invoiced', 15, 4)->default(0)->after('quantity_delivered');
            $table->index('quantity_delivered');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->dropIndex(['quantity_delivered']);
            $table->dropColumn(['quantity_delivered', 'quantity_invoiced']);
        });
    }
};
