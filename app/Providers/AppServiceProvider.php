<?php

namespace App\Providers;

use App\Core\Services\TenantContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register TenantContext as singleton
        $this->app->singleton(TenantContext::class, function ($app) {
            return new TenantContext;
        });

        // Register CacheService as singleton
        $this->app->singleton(\App\Core\Services\CacheService::class);

        // Register PermissionCacheService as singleton
        $this->app->singleton(\App\Core\Services\PermissionCacheService::class);

        // Register observability services
        $this->app->singleton(\App\Core\Services\AuditService::class);
        $this->app->singleton(\App\Core\Services\LogMaskingService::class);
        $this->app->singleton(\App\Core\Services\ActivityTimelineService::class);
        $this->app->singleton(\App\Core\Services\HealthCheckService::class);
        $this->app->singleton(\App\Core\Services\QueueMonitoringService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register morph map for polymorphic relationships
        \Illuminate\Database\Eloquent\Relations\Relation::enforceMorphMap([
            'user' => \App\Models\User::class,
            'lead' => \App\Modules\CRM\Models\Lead::class,
            'contact' => \App\Modules\CRM\Models\Contact::class,
            'account' => \App\Modules\CRM\Models\Account::class,
            'deal' => \App\Modules\CRM\Models\Deal::class,
        ]);

        // Register policies
        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\CRM\Models\Lead::class,
            \App\Modules\CRM\Policies\LeadPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\CRM\Models\Contact::class,
            \App\Modules\CRM\Policies\ContactPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\CRM\Models\Account::class,
            \App\Modules\CRM\Policies\AccountPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\CRM\Models\Pipeline::class,
            \App\Modules\CRM\Policies\PipelinePolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\CRM\Models\Deal::class,
            \App\Modules\CRM\Policies\DealPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\CRM\Models\Activity::class,
            \App\Modules\CRM\Policies\ActivityPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\CRM\Models\Note::class,
            \App\Modules\CRM\Policies\NotePolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\CRM\Models\Workflow::class,
            \App\Modules\CRM\Policies\WorkflowPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\CRM\Models\ImportResult::class,
            \App\Modules\CRM\Policies\ImportPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\CRM\Models\ExportLog::class,
            \App\Modules\CRM\Policies\ExportPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Core\Models\AuditLog::class,
            \App\Core\Policies\AuditLogPolicy::class
        );

        // E-Commerce Policies
        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ECommerce\Models\Store::class,
            \App\Modules\ECommerce\Policies\StorePolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ECommerce\Models\Theme::class,
            \App\Modules\ECommerce\Policies\ThemePolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ECommerce\Models\Order::class,
            \App\Modules\ECommerce\Policies\OrderPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ECommerce\Models\Page::class,
            \App\Modules\ECommerce\Policies\PagePolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ECommerce\Models\ContentBlock::class,
            \App\Modules\ECommerce\Policies\ContentBlockPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ECommerce\Models\ProductSync::class,
            \App\Modules\ECommerce\Policies\ProductSyncPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Core\Models\Tenant::class,
            \App\Platform\Policies\TenantPolicy::class
        );

        // Register ERP policies
        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\Product::class,
            \App\Modules\ERP\Policies\ProductPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\ProductCategory::class,
            \App\Modules\ERP\Policies\ProductCategoryPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\Warehouse::class,
            \App\Modules\ERP\Policies\WarehousePolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\Currency::class,
            \App\Modules\ERP\Policies\CurrencyPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\Supplier::class,
            \App\Modules\ERP\Policies\SupplierPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\StockItem::class,
            \App\Modules\ERP\Policies\InventoryPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\InventoryTransaction::class,
            \App\Modules\ERP\Policies\InventoryPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\SalesOrder::class,
            \App\Modules\ERP\Policies\SalesOrderPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\PurchaseOrder::class,
            \App\Modules\ERP\Policies\PurchaseOrderPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\Account::class,
            \App\Modules\ERP\Policies\AccountPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\JournalEntry::class,
            \App\Modules\ERP\Policies\JournalEntryPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\Report::class,
            \App\Modules\ERP\Policies\ReportPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\SystemSetting::class,
            \App\Modules\ERP\Policies\SystemSettingPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\PaymentGateway::class,
            \App\Modules\ERP\Policies\PaymentGatewayPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\SystemHealth::class,
            \App\Modules\ERP\Policies\SystemHealthPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\SalesInvoice::class,
            \App\Modules\ERP\Policies\InvoicePolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\RecurringInvoice::class,
            \App\Modules\ERP\Policies\RecurringInvoicePolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\CreditNote::class,
            \App\Modules\ERP\Policies\CreditNotePolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\Expense::class,
            \App\Modules\ERP\Policies\ExpensePolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\ExpenseCategory::class,
            \App\Modules\ERP\Policies\ExpenseCategoryPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\InventorySerial::class,
            \App\Modules\ERP\Policies\InventorySerialPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\ReorderRule::class,
            \App\Modules\ERP\Policies\ReorderRulePolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\Project::class,
            \App\Modules\ERP\Policies\ProjectPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\ProjectTask::class,
            \App\Modules\ERP\Policies\ProjectTaskPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\Timesheet::class,
            \App\Modules\ERP\Policies\TimesheetPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\CRM\Models\LeadScore::class,
            \App\Modules\CRM\Policies\LeadScorePolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\CRM\Models\LeadAssignmentRule::class,
            \App\Modules\CRM\Policies\LeadAssignmentRulePolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\CRM\Models\NoteAttachment::class,
            \App\Modules\CRM\Policies\NoteAttachmentPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\CRM\Models\EmailAccount::class,
            \App\Modules\CRM\Policies\EmailAccountPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\CRM\Models\EmailTemplate::class,
            \App\Modules\CRM\Policies\EmailTemplatePolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\CRM\Models\EmailCampaign::class,
            \App\Modules\CRM\Policies\EmailCampaignPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\CRM\Models\CalendarConnection::class,
            \App\Policies\CalendarConnectionPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Core\Models\Tag::class,
            \App\Core\Policies\TagPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Core\Models\Team::class,
            \App\Core\Policies\TeamPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Core\Models\CustomField::class,
            \App\Core\Policies\CustomFieldPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Core\Models\UserLoginHistory::class,
            \App\Core\Policies\UserLoginHistoryPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Models\User::class,
            \App\Core\Policies\UserPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Core\Models\Role::class,
            \App\Core\Policies\RolePolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\FiscalYear::class,
            \App\Modules\ERP\Policies\FiscalYearPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\ERP\Models\FiscalPeriod::class,
            \App\Modules\ERP\Policies\FiscalPeriodPolicy::class
        );

        // Register HR policies
        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\HR\Models\Department::class,
            \App\Modules\HR\Policies\DepartmentPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\HR\Models\Position::class,
            \App\Modules\HR\Policies\PositionPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\HR\Models\Employee::class,
            \App\Modules\HR\Policies\EmployeePolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\HR\Models\Contract::class,
            \App\Modules\HR\Policies\ContractPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\HR\Models\Attendance::class,
            \App\Modules\HR\Policies\AttendancePolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\HR\Models\AttendanceRecord::class,
            \App\Modules\HR\Policies\AttendanceRecordPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\HR\Models\LeaveRequest::class,
            \App\Modules\HR\Policies\LeaveRequestPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\HR\Models\Payroll::class,
            \App\Modules\HR\Policies\PayrollPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\HR\Models\PayrollRun::class,
            \App\Modules\HR\Policies\PayrollRunPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\HR\Models\PayrollItem::class,
            \App\Modules\HR\Policies\PayrollItemPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\HR\Models\Recruitment::class,
            \App\Modules\HR\Policies\RecruitmentPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\HR\Models\RecruitmentOpening::class,
            \App\Modules\HR\Policies\RecruitmentOpeningPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\HR\Models\PerformanceReview::class,
            \App\Modules\HR\Policies\PerformanceReviewPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\HR\Models\Training::class,
            \App\Modules\HR\Policies\TrainingPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\HR\Models\TrainingCourse::class,
            \App\Modules\HR\Policies\TrainingCoursePolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\HR\Models\TrainingAssignment::class,
            \App\Modules\HR\Policies\TrainingAssignmentPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\HR\Models\TrainingEnrollment::class,
            \App\Modules\HR\Policies\TrainingEnrollmentPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\HR\Models\EmployeeDocument::class,
            \App\Modules\HR\Policies\EmployeeDocumentPolicy::class
        );

        // Register observers
        \App\Modules\CRM\Models\Lead::observe(\App\Observers\LeadObserver::class);
        \App\Modules\CRM\Models\Deal::observe(\App\Modules\CRM\Observers\DealObserver::class);

        // Register E-Commerce observers
        \App\Modules\ERP\Models\Product::observe(\App\Observers\ProductObserver::class);
        \App\Modules\ERP\Models\SalesOrder::observe(\App\Observers\SalesOrderObserver::class);

        // Register cache invalidation observers for CRM models
        \App\Modules\CRM\Models\Lead::observe(\App\Observers\CacheInvalidationObserver::class);
        \App\Modules\CRM\Models\Contact::observe(\App\Observers\CacheInvalidationObserver::class);
        \App\Modules\CRM\Models\Account::observe(\App\Observers\CacheInvalidationObserver::class);
        \App\Modules\CRM\Models\Deal::observe(\App\Observers\CacheInvalidationObserver::class);
        \App\Modules\CRM\Models\Activity::observe(\App\Observers\CacheInvalidationObserver::class);
        \App\Modules\CRM\Models\Note::observe(\App\Observers\CacheInvalidationObserver::class);
        \App\Modules\CRM\Models\LeadScore::observe(\App\Observers\CacheInvalidationObserver::class);
        \App\Modules\CRM\Models\LeadAssignmentRule::observe(\App\Observers\CacheInvalidationObserver::class);
        \App\Modules\CRM\Models\NoteAttachment::observe(\App\Observers\CacheInvalidationObserver::class);

        // Register cache invalidation observers for ERP models
        \App\Modules\ERP\Models\Product::observe(\App\Observers\CacheInvalidationObserver::class);
        \App\Modules\ERP\Models\ProductCategory::observe(\App\Observers\CacheInvalidationObserver::class);
        \App\Modules\ERP\Models\StockItem::observe(\App\Observers\CacheInvalidationObserver::class);
        \App\Modules\ERP\Models\InventoryTransaction::observe(\App\Observers\CacheInvalidationObserver::class);
        \App\Modules\ERP\Models\SalesOrder::observe(\App\Observers\CacheInvalidationObserver::class);
        \App\Modules\ERP\Models\PurchaseOrder::observe(\App\Observers\CacheInvalidationObserver::class);
        \App\Modules\ERP\Models\Account::observe(\App\Observers\CacheInvalidationObserver::class);
        \App\Modules\ERP\Models\JournalEntry::observe(\App\Observers\CacheInvalidationObserver::class);
        \App\Modules\ERP\Models\RecurringInvoice::observe(\App\Observers\CacheInvalidationObserver::class);
        \App\Modules\ERP\Models\CreditNote::observe(\App\Observers\CacheInvalidationObserver::class);
        \App\Modules\ERP\Models\Expense::observe(\App\Observers\CacheInvalidationObserver::class);
        \App\Modules\ERP\Models\ExpenseCategory::observe(\App\Observers\CacheInvalidationObserver::class);
        \App\Modules\ERP\Models\InventorySerial::observe(\App\Observers\CacheInvalidationObserver::class);
        \App\Modules\ERP\Models\ReorderRule::observe(\App\Observers\CacheInvalidationObserver::class);

        // Register ERP audit observers
        \App\Modules\ERP\Models\Account::observe(\App\Modules\ERP\Observers\AccountObserver::class);
        \App\Modules\ERP\Models\JournalEntry::observe(\App\Modules\ERP\Observers\JournalEntryObserver::class);
        \App\Modules\ERP\Models\SalesInvoice::observe(\App\Modules\ERP\Observers\InvoiceObserver::class);
        \App\Modules\ERP\Models\PurchaseInvoice::observe(\App\Modules\ERP\Observers\PurchaseInvoiceObserver::class);
        \App\Modules\ERP\Models\Payment::observe(\App\Modules\ERP\Observers\PaymentObserver::class);
        \App\Modules\ERP\Models\FixedAsset::observe(\App\Modules\ERP\Observers\FixedAssetObserver::class);
        \App\Modules\ERP\Models\AssetDepreciation::observe(\App\Modules\ERP\Observers\AssetDepreciationObserver::class);
        \App\Modules\ERP\Models\RecurringInvoice::observe(\App\Modules\ERP\Observers\RecurringInvoiceObserver::class);
        \App\Modules\ERP\Models\CreditNote::observe(\App\Modules\ERP\Observers\CreditNoteObserver::class);
        \App\Modules\ERP\Models\Expense::observe(\App\Modules\ERP\Observers\ExpenseObserver::class);

        // Register event listeners
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\ActivityDue::class,
            \App\Listeners\SendActivityReminderListener::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\NoteMentioned::class,
            \App\Listeners\SendMentionNotificationListener::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\DealStatusChanged::class,
            \App\Listeners\SendDealNotificationListener::class
        );

        // Workflow triggers
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\DealStatusChanged::class,
            \App\Listeners\TriggerWorkflowOnDealStatusChanged::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\ActivityDue::class,
            \App\Listeners\TriggerWorkflowOnActivityOverdue::class
        );

        // Permission cache invalidation
        $permissionCacheListener = app(\App\Listeners\ClearPermissionCache::class);

        \App\Core\Models\Role::observe(function ($role) use ($permissionCacheListener) {
            $permissionCacheListener->handleRoleUpdated($role);
        });

        // Register cache invalidation observers for Core models
        \App\Core\Models\Tag::observe(\App\Observers\CacheInvalidationObserver::class);
        \App\Core\Models\Team::observe(\App\Observers\CacheInvalidationObserver::class);
        \App\Core\Models\CustomField::observe(\App\Observers\CacheInvalidationObserver::class);

        \App\Core\Models\Permission::observe(function ($permission) use ($permissionCacheListener) {
            $permissionCacheListener->handlePermissionUpdated($permission);
        });

        // Clear user permission cache when user roles are synced
        \Illuminate\Support\Facades\Event::listen(
            'eloquent.saved: App\Models\User',
            function ($user) {
                if ($user->isDirty('tenant_id')) {
                    app(\App\Core\Services\PermissionCacheService::class)->clearUserCache($user);
                }
            }
        );
    }
}
