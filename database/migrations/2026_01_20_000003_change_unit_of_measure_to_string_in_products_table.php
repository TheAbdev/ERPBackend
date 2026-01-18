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
        Schema::table('products', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['unit_of_measure_id']);
            
            // Drop the column
            $table->dropColumn('unit_of_measure_id');
            
            // Add new string column
            $table->string('unit_of_measure', 50)->after('barcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop the string column
            $table->dropColumn('unit_of_measure');
            
            // Add back the foreign key column
            $table->foreignId('unit_of_measure_id')->after('barcode')
                ->constrained('units_of_measure')->onDelete('restrict');
        });
    }
};



