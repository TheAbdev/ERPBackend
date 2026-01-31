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
        Schema::table('ecommerce_themes', function (Blueprint $table) {
            if (!Schema::hasColumn('ecommerce_themes', 'source_template')) {
                $table->string('source_template')->nullable()->after('preview_image');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_themes', function (Blueprint $table) {
            if (Schema::hasColumn('ecommerce_themes', 'source_template')) {
                $table->dropColumn('source_template');
            }
        });
    }
};

