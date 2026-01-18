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
        Schema::create('erp_webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('webhook_id')->constrained('erp_webhooks')->onDelete('cascade');
            $table->string('event_type');
            $table->json('payload');
            $table->string('status')->default('pending'); // pending, success, failure
            $table->integer('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('webhook_id');
            $table->index('status');
            $table->index('event_type');
            $table->index(['webhook_id', 'status']);
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_webhook_deliveries');
    }

    /**
     * Reverse the migrations.
     */

};
