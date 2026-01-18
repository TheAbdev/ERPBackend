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
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('code');
            $table->string('name');
            $table->string('prefix')->nullable();
            $table->string('suffix')->nullable();
            $table->integer('next_number')->default(1);
            $table->integer('min_length')->default(1);
            $table->string('format')->nullable(); // e.g., "INV-{YYYY}-{NUMBER}"
            $table->boolean('reset_period')->default(false); // Reset yearly, monthly, etc.
            $table->string('reset_frequency')->nullable(); // yearly, monthly, daily
            $table->date('last_reset_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('code');
            $table->index(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active']);
            
            // Unique constraint: code must be unique per tenant
            $table->unique(['tenant_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
    }
};
