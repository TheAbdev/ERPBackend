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
        Schema::table('activities', function (Blueprint $table) {
            $table->boolean('is_recurring')->default(false)->after('status');
            $table->string('recurrence_pattern')->nullable()->after('is_recurring'); // daily, weekly, monthly, yearly
            $table->integer('recurrence_interval')->default(1)->after('recurrence_pattern'); // Every X days/weeks/months
            $table->date('recurrence_end_date')->nullable()->after('recurrence_interval');
            $table->integer('recurrence_count')->nullable()->after('recurrence_end_date'); // Number of occurrences
            $table->foreignId('parent_activity_id')->nullable()->constrained('activities')->onDelete('cascade')->after('recurrence_count');
            $table->index('is_recurring');
            $table->index('parent_activity_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['parent_activity_id']);
            $table->dropIndex(['is_recurring']);
            $table->dropIndex(['parent_activity_id']);
            $table->dropColumn([
                'is_recurring',
                'recurrence_pattern',
                'recurrence_interval',
                'recurrence_end_date',
                'recurrence_count',
                'parent_activity_id',
            ]);
        });
    }
};
