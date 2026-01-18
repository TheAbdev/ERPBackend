<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->string('subject');
            $table->text('body');
            $table->string('type')->nullable(); // lead, contact, deal, invoice, etc.
            $table->boolean('is_active')->default(true);
            $table->json('variables')->nullable(); // Available variables
            $table->timestamps();
            $table->softDeletes();
            $table->index('tenant_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
