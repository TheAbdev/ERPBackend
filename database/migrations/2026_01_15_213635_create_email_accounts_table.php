<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('type'); // smtp, imap
            $table->json('credentials'); // Encrypted
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_sync')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('tenant_id');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_accounts');
    }
};
