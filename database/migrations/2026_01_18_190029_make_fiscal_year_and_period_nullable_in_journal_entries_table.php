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
        Schema::table('journal_entries', function (Blueprint $table) {
            // Make fiscal_year_id and fiscal_period_id nullable
            $table->foreignId('fiscal_year_id')->nullable()->change();
            $table->foreignId('fiscal_period_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            // Revert fiscal_year_id and fiscal_period_id to not nullable
            // Note: This may fail if there are existing null values
            $table->foreignId('fiscal_year_id')->nullable(false)->change();
            $table->foreignId('fiscal_period_id')->nullable(false)->change();
        });
    }
};
