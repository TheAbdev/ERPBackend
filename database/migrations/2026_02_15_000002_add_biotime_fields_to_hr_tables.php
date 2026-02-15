<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->string('biotime_emp_code')->nullable()->after('phone');
            $table->index(['tenant_id', 'biotime_emp_code'], 'hr_employees_biotime_emp_code_idx');
        });

        Schema::table('hr_attendance_records', function (Blueprint $table) {
            $table->string('source')->nullable()->after('notes');
            $table->string('external_id')->nullable()->after('source');
            $table->json('raw_payload')->nullable()->after('external_id');
            $table->unique(['tenant_id', 'source', 'external_id'], 'hr_attendance_records_source_external_uidx');
        });
    }

    public function down(): void
    {
        Schema::table('hr_attendance_records', function (Blueprint $table) {
            $table->dropUnique('hr_attendance_records_source_external_uidx');
            $table->dropColumn(['raw_payload', 'external_id', 'source']);
        });

        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropIndex('hr_employees_biotime_emp_code_idx');
            $table->dropColumn('biotime_emp_code');
        });
    }
};
