<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make slug globally unique so /website/{slug} always resolves to one site.
     * Prevents two tenants from having sites with the same slug (no conflict on public URL).
     */
    public function up(): void
    {
        // 1. Resolve existing duplicate slugs (different tenants, same slug)
        $duplicateSlugs = DB::table('website_sites')
            ->select('slug')
            ->groupBy('slug')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('slug');

        foreach ($duplicateSlugs as $slug) {
            $sites = DB::table('website_sites')
                ->where('slug', $slug)
                ->orderBy('id')
                ->get();

            $keepFirst = true;
            foreach ($sites as $site) {
                if ($keepFirst) {
                    $keepFirst = false;
                    continue;
                }
                // Make slug unique: append tenant_id so it stays deterministic
                $newSlug = $slug . '-' . $site->tenant_id;
                $suffix = 0;
                while (DB::table('website_sites')->where('slug', $newSlug)->exists()) {
                    $suffix++;
                    $newSlug = $slug . '-' . $site->tenant_id . '-' . $suffix;
                }
                DB::table('website_sites')->where('id', $site->id)->update(['slug' => $newSlug]);
            }
        }

        Schema::table('website_sites', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'slug']);
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('website_sites', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->unique(['tenant_id', 'slug']);
        });
    }
};
