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
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->string('invoice_type'); // SalesInvoice, PurchaseInvoice
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('amount', 15, 4);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('payment_id');
            $table->index(['invoice_type', 'invoice_id']);
            $table->index(['tenant_id', 'invoice_type', 'invoice_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
