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
        // First, remove any duplicate sites per tenant (keep the latest one)
        $duplicates = \DB::table('website_sites')
            ->select('tenant_id', \DB::raw('COUNT(*) as count'))
            ->groupBy('tenant_id')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            $sites = \DB::table('website_sites')
                ->where('tenant_id', $duplicate->tenant_id)
                ->orderBy('created_at', 'desc')
                ->get();

            // Keep the first (latest) site, delete the rest
            if ($sites->count() > 1) {
                $keepSite = $sites->first();
                \DB::table('website_sites')
                    ->where('tenant_id', $duplicate->tenant_id)
                    ->where('id', '!=', $keepSite->id)
                    ->delete();
            }
        }

        Schema::table('website_sites', function (Blueprint $table) {
            // Drop existing unique constraint on tenant_id + slug if exists
            $table->dropUnique(['tenant_id', 'slug']);
            
            // Add unique constraint on tenant_id only (one site per tenant)
            $table->unique('tenant_id');
            
            // Re-add unique constraint on slug per tenant (optional, for safety)
            $table->unique(['tenant_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('website_sites', function (Blueprint $table) {
            $table->dropUnique(['website_sites_tenant_id_unique']);
            $table->dropUnique(['website_sites_tenant_id_slug_unique']);
            $table->unique(['tenant_id', 'slug']);
        });
    }
};
