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
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('asset_code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('acquisition_date');
            $table->decimal('acquisition_cost', 15, 4);
            $table->decimal('salvage_value', 15, 4)->default(0);
            $table->integer('useful_life_months');
            $table->string('depreciation_method')->default('straight_line'); // straight_line
            $table->string('status')->default('draft'); // draft, active, disposed
            $table->foreignId('asset_account_id')->constrained('chart_of_accounts')->onDelete('restrict');
            $table->foreignId('depreciation_expense_account_id')->constrained('chart_of_accounts')->onDelete('restrict');
            $table->foreignId('accumulated_depreciation_account_id')->constrained('chart_of_accounts')->onDelete('restrict');
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('restrict');
            $table->foreignId('fiscal_year_id')->nullable()->constrained('fiscal_years')->onDelete('set null');
            $table->foreignId('fiscal_period_id')->nullable()->constrained('fiscal_periods')->onDelete('set null');
            $table->date('activation_date')->nullable();
            $table->date('disposal_date')->nullable();
            $table->decimal('disposal_amount', 15, 4)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('activated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('disposed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('asset_account_id');
            $table->index('status');
            $table->index('acquisition_date');
            $table->index('activation_date');
            $table->index('fiscal_period_id');
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'asset_code']);

            // Unique constraint: asset_code must be unique per tenant
            $table->unique(['tenant_id', 'asset_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
