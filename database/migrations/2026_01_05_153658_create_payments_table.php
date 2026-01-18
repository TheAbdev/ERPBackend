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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('payment_number');
            $table->string('type'); // incoming, outgoing
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->onDelete('restrict');
            $table->foreignId('fiscal_period_id')->constrained('fiscal_periods')->onDelete('restrict');
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('restrict');
            $table->date('payment_date');
            $table->decimal('amount', 15, 4);
            $table->string('payment_method')->nullable(); // cash, bank_transfer, check, etc.
            $table->string('reference_number')->nullable();
            $table->string('reference_type')->nullable(); // polymorphic: SalesInvoice, PurchaseInvoice
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('fiscal_period_id');
            $table->index('currency_id');
            $table->index('type');
            $table->index('payment_date');
            $table->index(['reference_type', 'reference_id']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'payment_date']);

            // Unique constraint: payment_number must be unique per tenant
            $table->unique(['tenant_id', 'payment_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
