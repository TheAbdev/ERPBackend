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
        Schema::create('workflow_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('workflow_id')->constrained('workflows')->onDelete('cascade');
            $table->string('trigger_type'); // lead, deal, activity
            $table->unsignedBigInteger('trigger_id'); // ID of the triggering entity
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->timestamp('executed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('execution_log')->nullable(); // Log of actions executed
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('workflow_id');
            $table->index(['trigger_type', 'trigger_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_runs');
    }
};
