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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('entry_number');
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->onDelete('restrict');
            $table->foreignId('fiscal_period_id')->constrained('fiscal_periods')->onDelete('restrict');
            $table->date('entry_date');
            $table->string('reference_type')->nullable(); // e.g., 'App\Modules\ERP\Models\SalesOrder'
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description');
            $table->string('status')->default('draft'); // draft, posted
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('posted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('entry_number');
            $table->index('entry_date');
            $table->index('status');
            $table->index('fiscal_year_id');
            $table->index('fiscal_period_id');
            $table->index(['reference_type', 'reference_id']);
            $table->index(['tenant_id', 'fiscal_year_id', 'fiscal_period_id'], 'idx_journal_tenant_fy_fp');
            $table->index(['tenant_id', 'status', 'entry_date']);

            // Unique constraint: entry_number must be unique per tenant
            $table->unique(['tenant_id', 'entry_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
