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
        Schema::create('erp_workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('workflow_id')->constrained('erp_workflows')->onDelete('restrict');
            $table->string('entity_type'); // Fully qualified model class
            $table->unsignedBigInteger('entity_id');
            $table->integer('current_step')->default(1);
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('initiated_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('initiated_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('workflow_id');
            $table->index(['entity_type', 'entity_id']);
            $table->index('status');
            $table->index('current_step');
            $table->index(['tenant_id', 'status']);

            // Unique constraint: one workflow instance per entity
            $table->unique(['tenant_id', 'entity_type', 'entity_id'], 'unique_workflow_instance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_workflow_instances');
    }
};
