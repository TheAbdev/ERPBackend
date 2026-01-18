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
        Schema::create('erp_system_health', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->decimal('cpu_usage', 5, 2)->nullable();
            $table->decimal('memory_usage', 5, 2)->nullable();
            $table->decimal('disk_usage', 5, 2)->nullable();
            $table->integer('active_connections')->nullable();
            $table->integer('queue_size')->nullable();
            $table->json('metrics')->nullable(); // Additional metrics
            $table->string('status')->default('healthy'); // healthy, warning, critical
            $table->timestamp('last_checked_at')->useCurrent();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('status');
            $table->index('last_checked_at');
            $table->index(['tenant_id', 'last_checked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_system_health');
    }
};
