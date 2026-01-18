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
        // Add tax fields to sales_invoices
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->decimal('net_amount', 15, 4)->default(0)->after('subtotal');
            $table->json('tax_breakdown')->nullable()->after('tax_amount'); // Array of tax lines
        });

        // Add tax fields to purchase_invoices
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->decimal('net_amount', 15, 4)->default(0)->after('subtotal');
            $table->json('tax_breakdown')->nullable()->after('tax_amount'); // Array of tax lines
        });

        // Add tax fields to sales_invoice_items
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->onDelete('set null')->after('product_variant_id');
            $table->decimal('net_amount', 15, 4)->default(0)->after('unit_price');
            $table->json('tax_breakdown')->nullable()->after('tax_amount'); // Array of tax lines per item
        });

        // Add tax fields to purchase_invoice_items
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->onDelete('set null')->after('product_variant_id');
            $table->decimal('net_amount', 15, 4)->default(0)->after('unit_price');
            $table->json('tax_breakdown')->nullable()->after('tax_amount'); // Array of tax lines per item
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->dropForeign(['tax_rate_id']);
            $table->dropColumn(['tax_rate_id', 'net_amount', 'tax_breakdown']);
        });

        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->dropForeign(['tax_rate_id']);
            $table->dropColumn(['tax_rate_id', 'net_amount', 'tax_breakdown']);
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn(['net_amount', 'tax_breakdown']);
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn(['net_amount', 'tax_breakdown']);
        });
    }
};
