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
        Schema::table('ecommerce_customers', function (Blueprint $table) {
            $table->foreignId('contact_id')->nullable()->after('store_id')->constrained('contacts')->onDelete('set null');
            $table->index('contact_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_customers', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropIndex(['contact_id']);
            $table->dropColumn('contact_id');
        });
    }
};







