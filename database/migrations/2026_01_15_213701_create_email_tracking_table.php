<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('email_campaign_id')->nullable()->constrained('email_campaigns')->onDelete('cascade');
            $table->string('email_message_id')->nullable(); // For individual emails
            $table->string('recipient_email');
            $table->string('tracking_token')->unique();
            $table->boolean('opened')->default(false);
            $table->timestamp('opened_at')->nullable();
            $table->integer('open_count')->default(0);
            $table->json('clicked_links')->nullable(); // Array of clicked URLs
            $table->timestamp('first_clicked_at')->nullable();
            $table->boolean('bounced')->default(false);
            $table->string('bounce_reason')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
            $table->index('email_campaign_id');
            $table->index('tracking_token');
            $table->index('recipient_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_tracking');
    }
};
