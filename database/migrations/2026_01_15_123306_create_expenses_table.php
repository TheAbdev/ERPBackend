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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('expense_number');
            $table->foreignId('expense_category_id')->nullable()->constrained('expense_categories')->onDelete('set null');
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('set null'); // Chart of accounts
            $table->foreignId('vendor_id')->nullable()->constrained('accounts')->onDelete('set null'); // Vendor account
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('restrict');
            $table->string('payee_name');
            $table->text('description');
            $table->decimal('amount', 15, 4);
            $table->date('expense_date');
            $table->string('payment_method')->nullable(); // cash, bank_transfer, credit_card, check
            $table->string('status')->default('pending'); // pending, approved, paid, rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('receipt_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('expense_category_id');
            $table->index('account_id');
            $table->index('vendor_id');
            $table->index('status');
            $table->index('expense_date');
            $table->unique(['tenant_id', 'expense_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
