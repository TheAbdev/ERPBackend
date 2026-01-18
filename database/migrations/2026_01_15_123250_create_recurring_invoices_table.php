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
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name'); // Template name
            $table->foreignId('customer_id')->nullable()->constrained('accounts')->onDelete('set null'); // For CRM accounts
            $table->string('customer_name'); // Fallback if no account
            $table->string('customer_email')->nullable();
            $table->text('customer_address')->nullable();
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('restrict');
            $table->string('frequency'); // daily, weekly, monthly, quarterly, yearly
            $table->integer('interval')->default(1); // Every X days/weeks/months
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('occurrences')->nullable(); // Number of invoices to generate
            $table->integer('day_of_month')->nullable(); // For monthly: which day
            $table->string('day_of_week')->nullable(); // For weekly: Monday, Tuesday, etc.
            $table->date('next_run_date');
            $table->date('last_run_date')->nullable();
            $table->integer('generated_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->json('invoice_data'); // Store invoice template data (items, taxes, etc.)
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('customer_id');
            $table->index('next_run_date');
            $table->index('is_active');
            $table->index(['tenant_id', 'is_active', 'next_run_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_invoices');
    }
};
