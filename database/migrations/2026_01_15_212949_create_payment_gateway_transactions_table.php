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
        if (Schema::hasTable('payment_gateway_transactions')) {
            return;
        }

        Schema::create('payment_gateway_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('payment_gateway_id')->constrained('payment_gateways')->onDelete('restrict');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');
            $table->string('gateway_transaction_id'); // Transaction ID from gateway
            $table->string('gateway_type'); // stripe, paypal
            $table->string('status'); // pending, processing, completed, failed, refunded, cancelled
            $table->decimal('amount', 15, 4);
            $table->string('currency', 3);
            $table->string('payment_method')->nullable(); // card, bank_transfer, paypal, etc.
            $table->json('gateway_response')->nullable(); // Full response from gateway
            $table->json('metadata')->nullable(); // Additional metadata
            $table->string('failure_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('payment_gateway_id');
            $table->index('payment_id');
            $table->index('gateway_transaction_id');
            $table->index('status');
            $table->index(['tenant_id', 'status']);
            $table->index(['gateway_type', 'gateway_transaction_id'], 'pg_trans_gateway_txn_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_transactions');
    }
};
