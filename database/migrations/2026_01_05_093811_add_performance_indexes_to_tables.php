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
        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            if (! $this->hasIndex('users', 'users_tenant_id_email_index')) {
                $table->index(['tenant_id', 'email'], 'users_tenant_id_email_index');
            }
        });

        // Leads table indexes
        Schema::table('leads', function (Blueprint $table) {
            if (! $this->hasIndex('leads', 'leads_tenant_id_status_index')) {
                $table->index(['tenant_id', 'status'], 'leads_tenant_id_status_index');
            }
            if (! $this->hasIndex('leads', 'leads_tenant_id_assigned_to_index')) {
                $table->index(['tenant_id', 'assigned_to'], 'leads_tenant_id_assigned_to_index');
            }
            if (! $this->hasIndex('leads', 'leads_tenant_id_created_at_index')) {
                $table->index(['tenant_id', 'created_at'], 'leads_tenant_id_created_at_index');
            }
        });

        // Contacts table indexes
        Schema::table('contacts', function (Blueprint $table) {
            if (! $this->hasIndex('contacts', 'contacts_tenant_id_email_index')) {
                $table->index(['tenant_id', 'email'], 'contacts_tenant_id_email_index');
            }
            if (! $this->hasIndex('contacts', 'contacts_tenant_id_lead_id_index')) {
                $table->index(['tenant_id', 'lead_id'], 'contacts_tenant_id_lead_id_index');
            }
        });

        // Accounts table indexes
        Schema::table('accounts', function (Blueprint $table) {
            if (! $this->hasIndex('accounts', 'accounts_tenant_id_parent_id_index')) {
                $table->index(['tenant_id', 'parent_id'], 'accounts_tenant_id_parent_id_index');
            }
        });

        // Deals table indexes
        Schema::table('deals', function (Blueprint $table) {
            if (! $this->hasIndex('deals', 'deals_tenant_id_status_index')) {
                $table->index(['tenant_id', 'status'], 'deals_tenant_id_status_index');
            }
            if (! $this->hasIndex('deals', 'deals_tenant_id_pipeline_id_index')) {
                $table->index(['tenant_id', 'pipeline_id'], 'deals_tenant_id_pipeline_id_index');
            }
            if (! $this->hasIndex('deals', 'deals_tenant_id_stage_id_index')) {
                $table->index(['tenant_id', 'stage_id'], 'deals_tenant_id_stage_id_index');
            }
            if (! $this->hasIndex('deals', 'deals_tenant_id_assigned_to_index')) {
                $table->index(['tenant_id', 'assigned_to'], 'deals_tenant_id_assigned_to_index');
            }
            if (! $this->hasIndex('deals', 'deals_tenant_id_expected_close_date_index')) {
                $table->index(['tenant_id', 'expected_close_date'], 'deals_tenant_id_expected_close_date_index');
            }
        });

        // Activities table indexes
        Schema::table('activities', function (Blueprint $table) {
            if (! $this->hasIndex('activities', 'activities_tenant_id_status_index')) {
                $table->index(['tenant_id', 'status'], 'activities_tenant_id_status_index');
            }
            if (! $this->hasIndex('activities', 'activities_tenant_id_assigned_to_index')) {
                $table->index(['tenant_id', 'assigned_to'], 'activities_tenant_id_assigned_to_index');
            }
            if (! $this->hasIndex('activities', 'activities_tenant_id_due_date_index')) {
                $table->index(['tenant_id', 'due_date'], 'activities_tenant_id_due_date_index');
            }
            if (! $this->hasIndex('activities', 'activities_tenant_id_related_index')) {
                $table->index(['tenant_id', 'related_type', 'related_id'], 'activities_tenant_id_related_index');
            }
        });

        // Notes table indexes
        Schema::table('notes', function (Blueprint $table) {
            if (! $this->hasIndex('notes', 'notes_tenant_id_noteable_index')) {
                $table->index(['tenant_id', 'noteable_type', 'noteable_id'], 'notes_tenant_id_noteable_index');
            }
        });

        // Notifications table indexes
        Schema::table('notifications', function (Blueprint $table) {
            if (! $this->hasIndex('notifications', 'notifications_tenant_id_read_at_index')) {
                $table->index(['tenant_id', 'read_at'], 'notifications_tenant_id_read_at_index');
            }
            if (! $this->hasIndex('notifications', 'notifications_notifiable_read_at_index')) {
                $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_notifiable_read_at_index');
            }
        });

        // Import/Export indexes
        Schema::table('import_results', function (Blueprint $table) {
            if (! $this->hasIndex('import_results', 'import_results_tenant_id_status_index')) {
                $table->index(['tenant_id', 'status'], 'import_results_tenant_id_status_index');
            }
        });

        Schema::table('export_logs', function (Blueprint $table) {
            if (! $this->hasIndex('export_logs', 'export_logs_tenant_id_export_type_index')) {
                $table->index(['tenant_id', 'export_type'], 'export_logs_tenant_id_export_type_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_tenant_id_email_index');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('leads_tenant_id_status_index');
            $table->dropIndex('leads_tenant_id_assigned_to_index');
            $table->dropIndex('leads_tenant_id_created_at_index');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex('contacts_tenant_id_email_index');
            $table->dropIndex('contacts_tenant_id_lead_id_index');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex('accounts_tenant_id_parent_id_index');
        });

        Schema::table('deals', function (Blueprint $table) {
            $table->dropIndex('deals_tenant_id_status_index');
            $table->dropIndex('deals_tenant_id_pipeline_id_index');
            $table->dropIndex('deals_tenant_id_stage_id_index');
            $table->dropIndex('deals_tenant_id_assigned_to_index');
            $table->dropIndex('deals_tenant_id_expected_close_date_index');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('activities_tenant_id_status_index');
            $table->dropIndex('activities_tenant_id_assigned_to_index');
            $table->dropIndex('activities_tenant_id_due_date_index');
            $table->dropIndex('activities_tenant_id_related_index');
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->dropIndex('notes_tenant_id_noteable_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_tenant_id_read_at_index');
            $table->dropIndex('notifications_notifiable_read_at_index');
        });

        Schema::table('import_results', function (Blueprint $table) {
            $table->dropIndex('import_results_tenant_id_status_index');
        });

        Schema::table('export_logs', function (Blueprint $table) {
            $table->dropIndex('export_logs_tenant_id_export_type_index');
        });
    }

    /**
     * Check if index exists.
     */
    protected function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        $result = $connection->select(
            "SELECT COUNT(*) as count FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, $table, $indexName]
        );

        return $result[0]->count > 0;
    }
};
