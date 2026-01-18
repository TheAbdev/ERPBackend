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
        Schema::create('erp_workflow_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('workflow_instance_id')->constrained('erp_workflow_instances')->onDelete('cascade');
            $table->integer('step_order');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('action'); // approve, reject
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('workflow_instance_id');
            $table->index('user_id');
            $table->index('action');
            $table->index(['workflow_instance_id', 'step_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_workflow_actions');
    }
};
