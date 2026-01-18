<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('email_account_id')->constrained('email_accounts')->onDelete('cascade');
            $table->string('message_id')->unique();
            $table->string('subject');
            $table->text('body');
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->json('to');
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->string('direction'); // incoming, outgoing
            $table->string('related_type')->nullable(); // Lead, Contact, Deal
            $table->unsignedBigInteger('related_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->boolean('is_read')->default(false);
            $table->json('attachments')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('tenant_id');
            $table->index('email_account_id');
            $table->index(['related_type', 'related_id']);
            $table->index('direction');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_messages');
    }
};
