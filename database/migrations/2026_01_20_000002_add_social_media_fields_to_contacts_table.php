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
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('linkedin_url')->nullable()->after('phone');
            $table->string('twitter_handle')->nullable()->after('linkedin_url');
            $table->string('facebook_url')->nullable()->after('twitter_handle');
            $table->string('instagram_handle')->nullable()->after('facebook_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['linkedin_url', 'twitter_handle', 'facebook_url', 'instagram_handle']);
        });
    }
};














