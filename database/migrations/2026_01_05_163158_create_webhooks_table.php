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
        Schema::create('erp_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('url');
            $table->string('secret')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('module'); // ERP, CRM, etc.
            $table->json('event_types'); // Array of event types to subscribe to
            $table->string('last_delivery_status')->nullable(); // success, failure
            $table->timestamp('last_delivery_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('module');
            $table->index('is_active');
            $table->index(['tenant_id', 'module']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_webhooks');
    }

    /**
     * Reverse the migrations.
     */

};
