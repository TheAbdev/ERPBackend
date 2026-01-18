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
        Schema::table('inventory_transactions', function (Blueprint $table) {
            // Drop foreign key constraint
            $table->dropForeign(['unit_of_measure_id']);

            // Make unit_of_measure_id nullable
            $table->foreignId('unit_of_measure_id')->nullable()->change();

            // Add unit_of_measure as string (optional)
            $table->string('unit_of_measure', 50)->nullable()->after('unit_of_measure_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            // Remove unit_of_measure string field
            $table->dropColumn('unit_of_measure');

            // Make unit_of_measure_id required again
            $table->foreignId('unit_of_measure_id')->nullable(false)->change();

            // Re-add foreign key constraint
            $table->foreign('unit_of_measure_id')->references('id')->on('units_of_measure')->onDelete('restrict');
        });
    }
};

