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
        // Activity Feed indexes
        if (Schema::hasTable('erp_activity_feed')) {
            Schema::table('erp_activity_feed', function (Blueprint $table) {
                if (!$this->hasIndex('erp_activity_feed', 'idx_activity_tenant_created')) {
                    $table->index(['tenant_id', 'created_at'], 'idx_activity_tenant_created');
                }
                if (!$this->hasIndex('erp_activity_feed', 'idx_activity_entity')) {
                    $table->index(['entity_type', 'entity_id'], 'idx_activity_entity');
                }
            });
        }

        // Notifications indexes
        if (Schema::hasTable('erp_notifications')) {
            Schema::table('erp_notifications', function (Blueprint $table) {
                if (!$this->hasIndex('erp_notifications', 'idx_notif_tenant_user_read')) {
                    $table->index(['tenant_id', 'user_id', 'read_at'], 'idx_notif_tenant_user_read');
                }
                if (!$this->hasIndex('erp_notifications', 'idx_notif_entity')) {
                    $table->index(['entity_type', 'entity_id'], 'idx_notif_entity');
                }
            });
        }

        // Webhook deliveries indexes
        if (Schema::hasTable('erp_webhook_deliveries')) {
            Schema::table('erp_webhook_deliveries', function (Blueprint $table) {
                if (!$this->hasIndex('erp_webhook_deliveries', 'idx_delivery_status_attempts')) {
                    $table->index(['status', 'attempts'], 'idx_delivery_status_attempts');
                }
                if (!$this->hasIndex('erp_webhook_deliveries', 'idx_delivery_tenant_webhook')) {
                    $table->index(['tenant_id', 'webhook_id'], 'idx_delivery_tenant_webhook');
                }
            });
        }

        // Sales Invoices indexes
        if (Schema::hasTable('erp_sales_invoices')) {
            Schema::table('erp_sales_invoices', function (Blueprint $table) {
                if (!$this->hasIndex('erp_sales_invoices', 'idx_sales_inv_tenant_status_date')) {
                    $table->index(['tenant_id', 'status', 'issue_date'], 'idx_sales_inv_tenant_status_date');
                }
            });
        }

        // Purchase Invoices indexes
        if (Schema::hasTable('erp_purchase_invoices')) {
            Schema::table('erp_purchase_invoices', function (Blueprint $table) {
                if (!$this->hasIndex('erp_purchase_invoices', 'idx_purch_inv_tenant_status_date')) {
                    $table->index(['tenant_id', 'status', 'issue_date'], 'idx_purch_inv_tenant_status_date');
                }
            });
        }

        // Payments indexes
        if (Schema::hasTable('erp_payments')) {
            Schema::table('erp_payments', function (Blueprint $table) {
                if (!$this->hasIndex('erp_payments', 'idx_payment_tenant_date')) {
                    $table->index(['tenant_id', 'payment_date'], 'idx_payment_tenant_date');
                }
                if (!$this->hasIndex('erp_payments', 'idx_payment_reference')) {
                    $table->index(['reference_type', 'reference_id'], 'idx_payment_reference');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('erp_activity_feed')) {
            Schema::table('erp_activity_feed', function (Blueprint $table) {
                $table->dropIndex('idx_activity_tenant_created');
                $table->dropIndex('idx_activity_entity');
            });
        }

        if (Schema::hasTable('erp_notifications')) {
            Schema::table('erp_notifications', function (Blueprint $table) {
                $table->dropIndex('idx_notif_tenant_user_read');
                $table->dropIndex('idx_notif_entity');
            });
        }

        if (Schema::hasTable('erp_webhook_deliveries')) {
            Schema::table('erp_webhook_deliveries', function (Blueprint $table) {
                $table->dropIndex('idx_delivery_status_attempts');
                $table->dropIndex('idx_delivery_tenant_webhook');
            });
        }

        if (Schema::hasTable('erp_sales_invoices')) {
            Schema::table('erp_sales_invoices', function (Blueprint $table) {
                $table->dropIndex('idx_sales_inv_tenant_status_date');
            });
        }

        if (Schema::hasTable('erp_purchase_invoices')) {
            Schema::table('erp_purchase_invoices', function (Blueprint $table) {
                $table->dropIndex('idx_purch_inv_tenant_status_date');
            });
        }

        if (Schema::hasTable('erp_payments')) {
            Schema::table('erp_payments', function (Blueprint $table) {
                $table->dropIndex('idx_payment_tenant_date');
                $table->dropIndex('idx_payment_reference');
            });
        }
    }

    /**
     * Check if index exists.
     *
     * @param  string  $table
     * @param  string  $index
     * @return bool
     */
    protected function hasIndex(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        $indexes = $connection->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);
        return count($indexes) > 0;
    }
};
