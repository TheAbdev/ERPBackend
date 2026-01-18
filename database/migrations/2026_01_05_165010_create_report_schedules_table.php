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
        Schema::create('erp_report_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('report_id')->constrained('erp_reports')->onDelete('cascade');
            $table->string('cron_expression');
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('recipients')->nullable(); // Array of user IDs or emails
            $table->string('format')->default('pdf'); // pdf, excel, csv
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('report_id');
            $table->index('is_active');
            $table->index('next_run_at');
            $table->index(['tenant_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_report_schedules');
    }
};
