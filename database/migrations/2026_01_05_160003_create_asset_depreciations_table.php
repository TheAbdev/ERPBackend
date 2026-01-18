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
        Schema::create('asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->onDelete('cascade');
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->onDelete('restrict');
            $table->foreignId('fiscal_period_id')->constrained('fiscal_periods')->onDelete('restrict');
            $table->date('depreciation_date');
            $table->decimal('amount', 15, 4);
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->boolean('is_posted')->default(false);
            $table->foreignId('posted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('fixed_asset_id');
            $table->index('fiscal_period_id');
            $table->index('depreciation_date');
            $table->index('is_posted');
            $table->index(['tenant_id', 'fixed_asset_id']);
            $table->index(['tenant_id', 'fiscal_period_id']);
            $table->index(['tenant_id', 'is_posted']);

            // Unique constraint: one depreciation per asset per fiscal period
            $table->unique(['tenant_id', 'fixed_asset_id', 'fiscal_period_id'], 'unique_asset_depreciation_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_depreciations');
    }
};
