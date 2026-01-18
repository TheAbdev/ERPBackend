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
        Schema::create('erp_workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('workflow_id')->constrained('erp_workflows')->onDelete('cascade');
            $table->integer('step_order');
            $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('restrict');
            $table->string('permission')->nullable(); // Alternative to role_id
            $table->string('action')->default('approve'); // approve, reject
            $table->boolean('auto_approve')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('workflow_id');
            $table->index('step_order');
            $table->index('role_id');
            $table->index(['workflow_id', 'step_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_workflow_steps');
    }
};
