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
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('entity_type'); // Lead, Contact, Account, Deal, Product, etc.
            $table->string('field_name'); // Internal field name (snake_case)
            $table->string('label'); // Display label
            $table->string('type'); // text, number, email, date, select, checkbox, textarea
            $table->text('options')->nullable(); // JSON for select/checkbox options
            $table->boolean('is_required')->default(false);
            $table->boolean('is_unique')->default(false);
            $table->text('default_value')->nullable();
            $table->text('validation_rules')->nullable(); // JSON validation rules
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('entity_type');
            $table->index(['tenant_id', 'entity_type']);
            $table->unique(['tenant_id', 'entity_type', 'field_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
