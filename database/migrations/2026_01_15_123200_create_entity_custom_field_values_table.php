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
        Schema::create('entity_custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('custom_field_id')->constrained('custom_fields')->onDelete('cascade');
            $table->string('entity_type'); // Lead, Contact, Account, etc.
            $table->unsignedBigInteger('entity_id');
            $table->text('value')->nullable(); // Store as text, parse based on field type
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('custom_field_id');
            $table->index(['entity_type', 'entity_id']);
            $table->index(['tenant_id', 'entity_type', 'entity_id']);
            $table->unique(['custom_field_id', 'entity_type', 'entity_id'], 'unique_custom_field_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_custom_field_values');
    }
};
