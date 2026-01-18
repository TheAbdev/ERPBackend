<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop table if exists (in case of previous failed migration)
        Schema::dropIfExists('calendar_connections');
        
        Schema::create('calendar_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('provider'); // google, outlook
            $table->string('calendar_id')->nullable(); // Specific calendar ID
            $table->text('access_token'); // Encrypted
            $table->text('refresh_token')->nullable(); // Encrypted
            $table->timestamp('token_expires_at')->nullable();
            $table->json('settings')->nullable(); // Additional provider-specific settings
            $table->boolean('is_active')->default(true);
            $table->boolean('sync_enabled')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('tenant_id');
            $table->index('user_id');
            $table->index('provider');
            $table->unique(['tenant_id', 'user_id', 'provider', 'calendar_id'], 'cal_conn_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_connections');
    }
};

