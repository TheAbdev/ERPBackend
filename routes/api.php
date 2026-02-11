<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;


Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});


Route::prefix('storefront')->group(function () {
    Route::get('/{slug}', [\App\Modules\ECommerce\Http\Controllers\StorefrontController::class, 'getStore']);
    Route::get('/{slug}/products', [\App\Modules\ECommerce\Http\Controllers\StorefrontController::class, 'getProducts']);
    Route::get('/{slug}/products/{productId}', [\App\Modules\ECommerce\Http\Controllers\StorefrontController::class, 'getProduct']);
    Route::get('/{slug}/layout', [\App\Modules\ECommerce\Http\Controllers\StorefrontController::class, 'getLayout']);
    Route::get('/{slug}/theme-config', [\App\Modules\ECommerce\Http\Controllers\StorefrontController::class, 'getThemeConfig']);
    Route::get('/{slug}/nav-pages', [\App\Modules\ECommerce\Http\Controllers\StorefrontController::class, 'getNavPages']);
    Route::get('/{slug}/pages/type/{pageType}', [\App\Modules\ECommerce\Http\Controllers\StorefrontController::class, 'getPageByType']);
    Route::get('/{slug}/pages/{pageSlug}', [\App\Modules\ECommerce\Http\Controllers\PageController::class, 'getBySlug']);


    Route::prefix('{slug}/cart')->group(function () {
        Route::get('/', [\App\Modules\ECommerce\Http\Controllers\CartController::class, 'getCart']);
        Route::post('/items', [\App\Modules\ECommerce\Http\Controllers\CartController::class, 'addItem']);
        Route::put('/items/{itemIndex}', [\App\Modules\ECommerce\Http\Controllers\CartController::class, 'updateItem']);
        Route::delete('/items/{itemIndex}', [\App\Modules\ECommerce\Http\Controllers\CartController::class, 'removeItem']);
    });


    Route::get('/{slug}/orders', [\App\Modules\ECommerce\Http\Controllers\OrderController::class, 'listPublicOrders']);
    Route::post('/{slug}/orders', [\App\Modules\ECommerce\Http\Controllers\OrderController::class, 'createFromCart']);
    Route::get('/{slug}/orders/{orderId}', [\App\Modules\ECommerce\Http\Controllers\OrderController::class, 'getPublicOrder']);
    Route::post('/{slug}/orders/{orderId}/payment', [\App\Modules\ECommerce\Http\Controllers\OrderController::class, 'processPayment']);

    Route::get('/{slug}/{pageSlug}', [\App\Modules\ECommerce\Http\Controllers\PageController::class, 'getBySlug']);
});


Route::middleware(['auth:sanctum', 'tenant.resolve', 'tenant.access'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });


    Route::prefix('auth/2fa')->group(function () {
        Route::get('/status', [\App\Core\Http\Controllers\TwoFactorAuthController::class, 'status']);
        Route::post('/enable', [\App\Core\Http\Controllers\TwoFactorAuthController::class, 'enable']);
        Route::post('/verify', [\App\Core\Http\Controllers\TwoFactorAuthController::class, 'verify']);
        Route::post('/disable', [\App\Core\Http\Controllers\TwoFactorAuthController::class, 'disable']);
        Route::post('/regenerate-recovery-codes', [\App\Core\Http\Controllers\TwoFactorAuthController::class, 'regenerateRecoveryCodes']);
    });


    Route::prefix('login-history')->group(function () {
        Route::get('/', [\App\Core\Http\Controllers\LoginHistoryController::class, 'index']);
        Route::get('/all', [\App\Core\Http\Controllers\LoginHistoryController::class, 'all']);
    });

    Route::prefix('custom-fields')->group(function () {
        Route::get('/', [\App\Core\Http\Controllers\CustomFieldController::class, 'index']);
        Route::post('/', [\App\Core\Http\Controllers\CustomFieldController::class, 'store']);
        Route::get('/{customField}', [\App\Core\Http\Controllers\CustomFieldController::class, 'show']);
        Route::put('/{customField}', [\App\Core\Http\Controllers\CustomFieldController::class, 'update']);
        Route::delete('/{customField}', [\App\Core\Http\Controllers\CustomFieldController::class, 'destroy']);
    });

    Route::prefix('tags')->group(function () {
        Route::get('/', [\App\Core\Http\Controllers\TagController::class, 'index']);
        Route::post('/', [\App\Core\Http\Controllers\TagController::class, 'store']);
        Route::get('/{tag}', [\App\Core\Http\Controllers\TagController::class, 'show']);
        Route::put('/{tag}', [\App\Core\Http\Controllers\TagController::class, 'update']);
        Route::delete('/{tag}', [\App\Core\Http\Controllers\TagController::class, 'destroy']);
    });

    Route::prefix('users')->group(function () {
        Route::get('/', [\App\Core\Http\Controllers\UserController::class, 'index']);
        Route::post('/', [\App\Core\Http\Controllers\UserController::class, 'store']);
        Route::get('/{user}', [\App\Core\Http\Controllers\UserController::class, 'show']);
        Route::put('/{user}', [\App\Core\Http\Controllers\UserController::class, 'update']);
        Route::patch('/{user}', [\App\Core\Http\Controllers\UserController::class, 'update']);
        Route::delete('/{user}', [\App\Core\Http\Controllers\UserController::class, 'destroy']);
        Route::post('/{user}/roles/assign', [\App\Core\Http\Controllers\UserController::class, 'assignRoles']);
        Route::post('/{user}/activate', [\App\Core\Http\Controllers\UserController::class, 'activate']);
        Route::post('/{user}/deactivate', [\App\Core\Http\Controllers\UserController::class, 'deactivate']);
    });

    Route::prefix('roles')->group(function () {
        Route::get('/', [\App\Core\Http\Controllers\RoleController::class, 'index']);
        Route::post('/', [\App\Core\Http\Controllers\RoleController::class, 'store']);
        Route::get('/{role}', [\App\Core\Http\Controllers\RoleController::class, 'show']);
        Route::put('/{role}', [\App\Core\Http\Controllers\RoleController::class, 'update']);
        Route::patch('/{role}', [\App\Core\Http\Controllers\RoleController::class, 'update']);
        Route::delete('/{role}', [\App\Core\Http\Controllers\RoleController::class, 'destroy']);
        Route::post('/{role}/permissions/assign', [\App\Core\Http\Controllers\RoleController::class, 'assignPermissions']);
        Route::post('/{role}/permissions/remove', [\App\Core\Http\Controllers\RoleController::class, 'removePermissions']);
        Route::post('/{role}/permissions/sync', [\App\Core\Http\Controllers\RoleController::class, 'syncPermissions']);
    });

    Route::prefix('permissions')->group(function () {
        Route::get('/', [\App\Core\Http\Controllers\PermissionController::class, 'index']);
    });

    Route::prefix('teams')->group(function () {
        Route::get('/', [\App\Core\Http\Controllers\TeamController::class, 'index']);
        Route::post('/', [\App\Core\Http\Controllers\TeamController::class, 'store']);
        Route::get('/{team}', [\App\Core\Http\Controllers\TeamController::class, 'show']);
        Route::put('/{team}', [\App\Core\Http\Controllers\TeamController::class, 'update']);
        Route::delete('/{team}', [\App\Core\Http\Controllers\TeamController::class, 'destroy']);
        Route::post('/{team}/users/attach', [\App\Core\Http\Controllers\TeamController::class, 'attachUsers']);
        Route::post('/{team}/users/detach', [\App\Core\Http\Controllers\TeamController::class, 'detachUsers']);
    });

    Route::prefix('crm/leads')->group(function () {
        Route::get('/', [\App\Modules\CRM\Http\Controllers\LeadController::class, 'index']);
        Route::post('/', [\App\Modules\CRM\Http\Controllers\LeadController::class, 'store']);
        Route::get('/{lead}', [\App\Modules\CRM\Http\Controllers\LeadController::class, 'show']);
        Route::put('/{lead}', [\App\Modules\CRM\Http\Controllers\LeadController::class, 'update']);
        Route::patch('/{lead}', [\App\Modules\CRM\Http\Controllers\LeadController::class, 'update']);
        Route::delete('/{lead}', [\App\Modules\CRM\Http\Controllers\LeadController::class, 'destroy']);
        Route::post('/{lead}/convert-to-contact', [\App\Modules\CRM\Http\Controllers\LeadConversionController::class, 'toContact']);
        Route::post('/{lead}/convert-to-deal', [\App\Modules\CRM\Http\Controllers\LeadConversionController::class, 'toDeal']);
        Route::post('/{lead}/convert-to-contact-and-deal', [\App\Modules\CRM\Http\Controllers\LeadConversionController::class, 'toContactAndDeal']);
        Route::post('/{lead}/calculate-score', [\App\Modules\CRM\Http\Controllers\LeadScoreController::class, 'calculate']);
    });

    Route::prefix('crm/lead-scores')->group(function () {
        Route::post('/recalculate-all', [\App\Modules\CRM\Http\Controllers\LeadScoreController::class, 'recalculateAll']);
    });

    Route::prefix('crm/lead-assignment-rules')->group(function () {
        Route::get('/', [\App\Modules\CRM\Http\Controllers\LeadAssignmentRuleController::class, 'index']);
        Route::post('/', [\App\Modules\CRM\Http\Controllers\LeadAssignmentRuleController::class, 'store']);
        Route::get('/{leadAssignmentRule}', [\App\Modules\CRM\Http\Controllers\LeadAssignmentRuleController::class, 'show']);
        Route::put('/{leadAssignmentRule}', [\App\Modules\CRM\Http\Controllers\LeadAssignmentRuleController::class, 'update']);
        Route::patch('/{leadAssignmentRule}', [\App\Modules\CRM\Http\Controllers\LeadAssignmentRuleController::class, 'update']);
        Route::delete('/{leadAssignmentRule}', [\App\Modules\CRM\Http\Controllers\LeadAssignmentRuleController::class, 'destroy']);
    });

    Route::prefix('crm/contacts')->group(function () {
        Route::get('/', [\App\Modules\CRM\Http\Controllers\ContactController::class, 'index']);
        Route::post('/', [\App\Modules\CRM\Http\Controllers\ContactController::class, 'store']);
        Route::get('/{contact}', [\App\Modules\CRM\Http\Controllers\ContactController::class, 'show']);
        Route::put('/{contact}', [\App\Modules\CRM\Http\Controllers\ContactController::class, 'update']);
        Route::patch('/{contact}', [\App\Modules\CRM\Http\Controllers\ContactController::class, 'update']);
        Route::delete('/{contact}', [\App\Modules\CRM\Http\Controllers\ContactController::class, 'destroy']);
    });

    Route::prefix('crm/accounts')->group(function () {
        Route::get('/', [\App\Modules\CRM\Http\Controllers\AccountController::class, 'index']);
        Route::post('/', [\App\Modules\CRM\Http\Controllers\AccountController::class, 'store']);
        Route::get('/{account}', [\App\Modules\CRM\Http\Controllers\AccountController::class, 'show']);
        Route::put('/{account}', [\App\Modules\CRM\Http\Controllers\AccountController::class, 'update']);
        Route::patch('/{account}', [\App\Modules\CRM\Http\Controllers\AccountController::class, 'update']);
        Route::delete('/{account}', [\App\Modules\CRM\Http\Controllers\AccountController::class, 'destroy']);
        Route::post('/{account}/contacts/attach', [\App\Modules\CRM\Http\Controllers\AccountController::class, 'attachContacts']);
        Route::post('/{account}/contacts/detach', [\App\Modules\CRM\Http\Controllers\AccountController::class, 'detachContacts']);
        Route::post('/merge', [\App\Modules\CRM\Http\Controllers\AccountMergeController::class, 'merge']);
    });

    Route::prefix('crm/pipelines')->group(function () {
        Route::get('/', [\App\Modules\CRM\Http\Controllers\PipelineController::class, 'index']);
        Route::post('/', [\App\Modules\CRM\Http\Controllers\PipelineController::class, 'store']);
        Route::get('/{pipeline}', [\App\Modules\CRM\Http\Controllers\PipelineController::class, 'show']);
        Route::put('/{pipeline}', [\App\Modules\CRM\Http\Controllers\PipelineController::class, 'update']);
        Route::patch('/{pipeline}', [\App\Modules\CRM\Http\Controllers\PipelineController::class, 'update']);
        Route::delete('/{pipeline}', [\App\Modules\CRM\Http\Controllers\PipelineController::class, 'destroy']);
        Route::post('/{pipeline}/stages', [\App\Modules\CRM\Http\Controllers\PipelineController::class, 'createStage']);
        Route::put('/{pipeline}/stages/{stage}', [\App\Modules\CRM\Http\Controllers\PipelineController::class, 'updateStage']);
        Route::delete('/{pipeline}/stages/{stage}', [\App\Modules\CRM\Http\Controllers\PipelineController::class, 'destroyStage']);
        Route::post('/{pipeline}/stages/reorder', [\App\Modules\CRM\Http\Controllers\PipelineController::class, 'reorderStages']);
    });

    Route::prefix('crm/deals')->group(function () {
        Route::get('/', [\App\Modules\CRM\Http\Controllers\DealController::class, 'index']);
        Route::post('/', [\App\Modules\CRM\Http\Controllers\DealController::class, 'store']);
        Route::get('/{deal}', [\App\Modules\CRM\Http\Controllers\DealController::class, 'show']);
        Route::put('/{deal}', [\App\Modules\CRM\Http\Controllers\DealController::class, 'update']);
        Route::patch('/{deal}', [\App\Modules\CRM\Http\Controllers\DealController::class, 'update']);
        Route::delete('/{deal}', [\App\Modules\CRM\Http\Controllers\DealController::class, 'destroy']);
        Route::post('/{deal}/move-stage', [\App\Modules\CRM\Http\Controllers\DealController::class, 'moveStage']);
        Route::post('/{deal}/won', [\App\Modules\CRM\Http\Controllers\DealController::class, 'markWon']);
        Route::post('/{deal}/lost', [\App\Modules\CRM\Http\Controllers\DealController::class, 'markLost']);
    });

    Route::prefix('crm/activities')->group(function () {
        Route::get('/', [\App\Modules\CRM\Http\Controllers\ActivityController::class, 'index']);
        Route::post('/', [\App\Modules\CRM\Http\Controllers\ActivityController::class, 'store']);
        Route::get('/{activity}', [\App\Modules\CRM\Http\Controllers\ActivityController::class, 'show']);
        Route::put('/{activity}', [\App\Modules\CRM\Http\Controllers\ActivityController::class, 'update']);
        Route::patch('/{activity}', [\App\Modules\CRM\Http\Controllers\ActivityController::class, 'update']);
        Route::delete('/{activity}', [\App\Modules\CRM\Http\Controllers\ActivityController::class, 'destroy']);
        Route::post('/{activity}/complete', [\App\Modules\CRM\Http\Controllers\ActivityController::class, 'markCompleted']);
    });

    Route::prefix('crm/notes')->group(function () {
        Route::get('/', [\App\Modules\CRM\Http\Controllers\NoteController::class, 'index']);
        Route::post('/', [\App\Modules\CRM\Http\Controllers\NoteController::class, 'store']);
        Route::get('/{note}', [\App\Modules\CRM\Http\Controllers\NoteController::class, 'show']);
        Route::put('/{note}', [\App\Modules\CRM\Http\Controllers\NoteController::class, 'update']);
        Route::patch('/{note}', [\App\Modules\CRM\Http\Controllers\NoteController::class, 'update']);
        Route::delete('/{note}', [\App\Modules\CRM\Http\Controllers\NoteController::class, 'destroy']);
        Route::post('/{note}/replies', [\App\Modules\CRM\Http\Controllers\NoteController::class, 'reply']);
        Route::get('/{note}/replies', [\App\Modules\CRM\Http\Controllers\NoteController::class, 'replies']);
        Route::get('/{note}/attachments', [\App\Modules\CRM\Http\Controllers\NoteAttachmentController::class, 'index']);
    });

    Route::prefix('crm/note-attachments')->group(function () {
        Route::post('/', [\App\Modules\CRM\Http\Controllers\NoteAttachmentController::class, 'store']);
        Route::get('/{noteAttachment}', [\App\Modules\CRM\Http\Controllers\NoteAttachmentController::class, 'show']);
        Route::delete('/{noteAttachment}', [\App\Modules\CRM\Http\Controllers\NoteAttachmentController::class, 'destroy']);
    });

    Route::prefix('crm/reports')->group(function () {
        Route::get('/leads', [\App\Modules\CRM\Http\Controllers\ReportsController::class, 'leads']);
        Route::get('/deals', [\App\Modules\CRM\Http\Controllers\ReportsController::class, 'deals']);
        Route::get('/activities', [\App\Modules\CRM\Http\Controllers\ReportsController::class, 'activities']);
        Route::get('/sales-performance', [\App\Modules\CRM\Http\Controllers\ReportsController::class, 'salesPerformance']);
    });

    Route::prefix('crm/workflows')->group(function () {
        Route::get('/', [\App\Modules\CRM\Http\Controllers\WorkflowController::class, 'index']);
        Route::post('/', [\App\Modules\CRM\Http\Controllers\WorkflowController::class, 'store']);
        Route::get('/{workflow}', [\App\Modules\CRM\Http\Controllers\WorkflowController::class, 'show']);
        Route::put('/{workflow}', [\App\Modules\CRM\Http\Controllers\WorkflowController::class, 'update']);
        Route::patch('/{workflow}', [\App\Modules\CRM\Http\Controllers\WorkflowController::class, 'update']);
        Route::delete('/{workflow}', [\App\Modules\CRM\Http\Controllers\WorkflowController::class, 'destroy']);
    });

    Route::prefix('crm/imports')->group(function () {
        Route::get('/', [\App\Modules\CRM\Http\Controllers\ImportController::class, 'index']);
        Route::post('/', [\App\Modules\CRM\Http\Controllers\ImportController::class, 'store']);
        Route::get('/{import}', [\App\Modules\CRM\Http\Controllers\ImportController::class, 'show']);
    });

    Route::prefix('crm/exports')->group(function () {
        Route::get('/', [\App\Modules\CRM\Http\Controllers\ExportController::class, 'index']);
        Route::post('/', [\App\Modules\CRM\Http\Controllers\ExportController::class, 'store']);
        Route::get('/{exportLog}/download', [\App\Modules\CRM\Http\Controllers\ExportController::class, 'download'])
            ->name('api.crm.exports.download');
    });

    Route::prefix('crm/calendar/google')->group(function () {
        Route::get('/connect', [\App\Modules\CRM\Http\Controllers\GoogleCalendarController::class, 'connect']);
        Route::get('/callback', [\App\Modules\CRM\Http\Controllers\GoogleCalendarController::class, 'callback']);
        Route::post('/sync', [\App\Modules\CRM\Http\Controllers\GoogleCalendarController::class, 'sync']);
        Route::get('/events', [\App\Modules\CRM\Http\Controllers\GoogleCalendarController::class, 'events']);
        Route::delete('/disconnect/{calendarConnection}', [\App\Modules\CRM\Http\Controllers\GoogleCalendarController::class, 'disconnect']);
    });

    Route::prefix('crm/calendar/outlook')->group(function () {
        Route::get('/connect', [\App\Modules\CRM\Http\Controllers\OutlookCalendarController::class, 'connect']);
        Route::get('/callback', [\App\Modules\CRM\Http\Controllers\OutlookCalendarController::class, 'callback']);
        Route::post('/sync', [\App\Modules\CRM\Http\Controllers\OutlookCalendarController::class, 'sync']);
        Route::delete('/disconnect/{calendarConnection}', [\App\Modules\CRM\Http\Controllers\OutlookCalendarController::class, 'disconnect']);
    });

    Route::prefix('crm/email-accounts')->group(function () {
        Route::get('/', [\App\Modules\CRM\Http\Controllers\EmailAccountController::class, 'index']);
        Route::post('/', [\App\Modules\CRM\Http\Controllers\EmailAccountController::class, 'store']);
        Route::get('/{emailAccount}', [\App\Modules\CRM\Http\Controllers\EmailAccountController::class, 'show']);
        Route::put('/{emailAccount}', [\App\Modules\CRM\Http\Controllers\EmailAccountController::class, 'update']);
        Route::delete('/{emailAccount}', [\App\Modules\CRM\Http\Controllers\EmailAccountController::class, 'destroy']);
    });

    Route::prefix('crm/email-templates')->group(function () {
        Route::get('/', [\App\Modules\CRM\Http\Controllers\EmailTemplateController::class, 'index']);
        Route::post('/', [\App\Modules\CRM\Http\Controllers\EmailTemplateController::class, 'store']);
        Route::get('/{emailTemplate}', [\App\Modules\CRM\Http\Controllers\EmailTemplateController::class, 'show']);
        Route::put('/{emailTemplate}', [\App\Modules\CRM\Http\Controllers\EmailTemplateController::class, 'update']);
        Route::delete('/{emailTemplate}', [\App\Modules\CRM\Http\Controllers\EmailTemplateController::class, 'destroy']);
    });

    Route::prefix('crm/email-campaigns')->group(function () {
        Route::get('/', [\App\Modules\CRM\Http\Controllers\EmailCampaignController::class, 'index']);
        Route::post('/', [\App\Modules\CRM\Http\Controllers\EmailCampaignController::class, 'store']);
        Route::get('/{emailCampaign}', [\App\Modules\CRM\Http\Controllers\EmailCampaignController::class, 'show']);
        Route::put('/{emailCampaign}', [\App\Modules\CRM\Http\Controllers\EmailCampaignController::class, 'update']);
        Route::delete('/{emailCampaign}', [\App\Modules\CRM\Http\Controllers\EmailCampaignController::class, 'destroy']);
        Route::post('/{emailCampaign}/send', [\App\Modules\CRM\Http\Controllers\EmailCampaignController::class, 'send']);
    });

    Route::prefix('crm/email-tracking')->group(function () {
        Route::get('/{token}/open', [\App\Modules\CRM\Http\Controllers\EmailTrackingController::class, 'trackOpen'])
            ->name('api.crm.email-tracking.open');
        Route::get('/{token}/click', [\App\Modules\CRM\Http\Controllers\EmailTrackingController::class, 'trackClick'])
            ->name('api.crm.email-tracking.click');
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotificationController::class, 'index']);
        Route::post('/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markRead']);
        Route::post('/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllRead']);
    });

    Route::prefix('health')->group(function () {
        Route::get('/', [\App\Http\Controllers\HealthCheckController::class, 'index']);
        Route::get('/database', [\App\Http\Controllers\HealthCheckController::class, 'database']);
        Route::get('/cache', [\App\Http\Controllers\HealthCheckController::class, 'cache']);
        Route::get('/queue', [\App\Http\Controllers\HealthCheckController::class, 'queue']);
        Route::get('/storage', [\App\Http\Controllers\HealthCheckController::class, 'storage']);
    });

    Route::prefix('audit-logs')->middleware(['tenant.rate_limit:100,1'])->group(function () {
        Route::get('/', [\App\Http\Controllers\AuditLogController::class, 'index']);
        Route::get('/model-timeline', [\App\Http\Controllers\AuditLogController::class, 'modelTimeline']);
        Route::get('/user/{userId}/timeline', [\App\Http\Controllers\AuditLogController::class, 'userTimeline']);
        Route::get('/recent', [\App\Http\Controllers\AuditLogController::class, 'recentActivity']);
        Route::delete('/', [\App\Http\Controllers\AuditLogController::class, 'destroy']);

    });

    Route::prefix('queue')->middleware(['tenant.rate_limit:60,1'])->group(function () {
        Route::get('/statistics', [\App\Http\Controllers\QueueMonitoringController::class, 'statistics']);
        Route::get('/failed-jobs', [\App\Http\Controllers\QueueMonitoringController::class, 'failedJobs']);
        Route::get('/metrics', [\App\Http\Controllers\QueueMonitoringController::class, 'metrics']);
    });

    Route::prefix('erp/products')->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\ProductController::class, 'index']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\ProductController::class, 'store']);
        Route::get('/{product}', [\App\Modules\ERP\Http\Controllers\ProductController::class, 'show']);
        Route::put('/{product}', [\App\Modules\ERP\Http\Controllers\ProductController::class, 'update']);
        Route::patch('/{product}', [\App\Modules\ERP\Http\Controllers\ProductController::class, 'update']);
        Route::delete('/{product}', [\App\Modules\ERP\Http\Controllers\ProductController::class, 'destroy']);
    });

    Route::prefix('erp/product-bundles')->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\ProductBundleController::class, 'index']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\ProductBundleController::class, 'store']);
        Route::get('/{productBundle}', [\App\Modules\ERP\Http\Controllers\ProductBundleController::class, 'show']);
        Route::put('/{productBundle}', [\App\Modules\ERP\Http\Controllers\ProductBundleController::class, 'update']);
        Route::patch('/{productBundle}', [\App\Modules\ERP\Http\Controllers\ProductBundleController::class, 'update']);
        Route::delete('/{productBundle}', [\App\Modules\ERP\Http\Controllers\ProductBundleController::class, 'destroy']);
    });

    Route::prefix('erp/product-categories')->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\ProductCategoryController::class, 'index']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\ProductCategoryController::class, 'store']);
        Route::get('/{category}', [\App\Modules\ERP\Http\Controllers\ProductCategoryController::class, 'show']);
        Route::put('/{category}', [\App\Modules\ERP\Http\Controllers\ProductCategoryController::class, 'update']);
        Route::patch('/{category}', [\App\Modules\ERP\Http\Controllers\ProductCategoryController::class, 'update']);
        Route::delete('/{category}', [\App\Modules\ERP\Http\Controllers\ProductCategoryController::class, 'destroy']);
    });

    Route::prefix('erp/units-of-measure')->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\UnitOfMeasureController::class, 'index']);
        Route::get('/{unitOfMeasure}', [\App\Modules\ERP\Http\Controllers\UnitOfMeasureController::class, 'show']);
    });

    Route::prefix('erp/warehouses')->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\WarehouseController::class, 'index']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\WarehouseController::class, 'store']);
        Route::get('/{warehouse}', [\App\Modules\ERP\Http\Controllers\WarehouseController::class, 'show']);
        Route::put('/{warehouse}', [\App\Modules\ERP\Http\Controllers\WarehouseController::class, 'update']);
        Route::patch('/{warehouse}', [\App\Modules\ERP\Http\Controllers\WarehouseController::class, 'update']);
        Route::delete('/{warehouse}', [\App\Modules\ERP\Http\Controllers\WarehouseController::class, 'destroy']);
    });

    Route::prefix('erp/inventory')->group(function () {
        Route::get('/stock-items', [\App\Modules\ERP\Http\Controllers\InventoryController::class, 'stockItems']);
        Route::get('/stock-items/{stockItem}', [\App\Modules\ERP\Http\Controllers\InventoryController::class, 'stockItem']);
        Route::post('/transactions', [\App\Modules\ERP\Http\Controllers\InventoryController::class, 'recordTransaction']);
        Route::get('/transactions', [\App\Modules\ERP\Http\Controllers\InventoryController::class, 'transactions']);
        Route::get('/check-availability', [\App\Modules\ERP\Http\Controllers\InventoryController::class, 'checkAvailability']);
        Route::get('/low-stock', [\App\Modules\ERP\Http\Controllers\InventoryController::class, 'lowStock']);
    });

    Route::prefix('erp/currencies')->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\CurrencyController::class, 'index']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\CurrencyController::class, 'store']);
        Route::get('/{currency}', [\App\Modules\ERP\Http\Controllers\CurrencyController::class, 'show']);
        Route::put('/{currency}', [\App\Modules\ERP\Http\Controllers\CurrencyController::class, 'update']);
        Route::patch('/{currency}', [\App\Modules\ERP\Http\Controllers\CurrencyController::class, 'update']);
        Route::delete('/{currency}', [\App\Modules\ERP\Http\Controllers\CurrencyController::class, 'destroy']);
    });

    Route::prefix('erp/sales-orders')->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\SalesOrderController::class, 'index']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\SalesOrderController::class, 'store']);
        Route::get('/{salesOrder}', [\App\Modules\ERP\Http\Controllers\SalesOrderController::class, 'show']);
        Route::put('/{salesOrder}', [\App\Modules\ERP\Http\Controllers\SalesOrderController::class, 'update']);
        Route::patch('/{salesOrder}', [\App\Modules\ERP\Http\Controllers\SalesOrderController::class, 'update']);
        Route::delete('/{salesOrder}', [\App\Modules\ERP\Http\Controllers\SalesOrderController::class, 'destroy']);
        Route::post('/{salesOrder}/confirm', [\App\Modules\ERP\Http\Controllers\SalesOrderController::class, 'confirm']);
        Route::post('/{salesOrder}/cancel', [\App\Modules\ERP\Http\Controllers\SalesOrderController::class, 'cancel']);
        Route::post('/{salesOrder}/deliver', [\App\Modules\ERP\Http\Controllers\SalesOrderController::class, 'deliver']);
        Route::post('/{salesOrder}/partial-deliver', [\App\Modules\ERP\Http\Controllers\SalesOrderController::class, 'partialDeliver']);
        Route::post('/{salesOrder}/partial-deliver', [\App\Modules\ERP\Http\Controllers\SalesOrderController::class, 'partialDeliver']);
    });

    Route::prefix('erp/purchase-orders')->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\PurchaseOrderController::class, 'index']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\PurchaseOrderController::class, 'store']);
        Route::get('/{purchaseOrder}', [\App\Modules\ERP\Http\Controllers\PurchaseOrderController::class, 'show']);
        Route::put('/{purchaseOrder}', [\App\Modules\ERP\Http\Controllers\PurchaseOrderController::class, 'update']);
        Route::patch('/{purchaseOrder}', [\App\Modules\ERP\Http\Controllers\PurchaseOrderController::class, 'update']);
        Route::delete('/{purchaseOrder}', [\App\Modules\ERP\Http\Controllers\PurchaseOrderController::class, 'destroy']);
        Route::post('/{purchaseOrder}/confirm', [\App\Modules\ERP\Http\Controllers\PurchaseOrderController::class, 'confirm']);
        Route::post('/{purchaseOrder}/cancel', [\App\Modules\ERP\Http\Controllers\PurchaseOrderController::class, 'cancel']);
        Route::post('/{purchaseOrder}/receive', [\App\Modules\ERP\Http\Controllers\PurchaseOrderController::class, 'receive']);
    });

    Route::prefix('erp/sales-invoices')->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\SalesInvoiceController::class, 'index']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\SalesInvoiceController::class, 'store']);
        Route::get('/{salesInvoice}', [\App\Modules\ERP\Http\Controllers\SalesInvoiceController::class, 'show']);
        Route::put('/{salesInvoice}', [\App\Modules\ERP\Http\Controllers\SalesInvoiceController::class, 'update']);
        Route::patch('/{salesInvoice}', [\App\Modules\ERP\Http\Controllers\SalesInvoiceController::class, 'update']);
        Route::delete('/{salesInvoice}', [\App\Modules\ERP\Http\Controllers\SalesInvoiceController::class, 'destroy']);
        Route::post('/{salesInvoice}/issue', [\App\Modules\ERP\Http\Controllers\SalesInvoiceController::class, 'issue']);
    });

    Route::prefix('erp/recurring-invoices')->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\RecurringInvoiceController::class, 'index']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\RecurringInvoiceController::class, 'store']);
        Route::get('/{recurringInvoice}', [\App\Modules\ERP\Http\Controllers\RecurringInvoiceController::class, 'show']);
        Route::put('/{recurringInvoice}', [\App\Modules\ERP\Http\Controllers\RecurringInvoiceController::class, 'update']);
        Route::patch('/{recurringInvoice}', [\App\Modules\ERP\Http\Controllers\RecurringInvoiceController::class, 'update']);
        Route::delete('/{recurringInvoice}', [\App\Modules\ERP\Http\Controllers\RecurringInvoiceController::class, 'destroy']);
        Route::post('/generate', [\App\Modules\ERP\Http\Controllers\RecurringInvoiceController::class, 'generateDueInvoices']);
    });

    Route::prefix('erp/credit-notes')->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\CreditNoteController::class, 'index']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\CreditNoteController::class, 'store']);
        Route::get('/{creditNote}', [\App\Modules\ERP\Http\Controllers\CreditNoteController::class, 'show']);
        Route::put('/{creditNote}', [\App\Modules\ERP\Http\Controllers\CreditNoteController::class, 'update']);
        Route::patch('/{creditNote}', [\App\Modules\ERP\Http\Controllers\CreditNoteController::class, 'update']);
        Route::delete('/{creditNote}', [\App\Modules\ERP\Http\Controllers\CreditNoteController::class, 'destroy']);
        Route::post('/{creditNote}/issue', [\App\Modules\ERP\Http\Controllers\CreditNoteController::class, 'issue']);
    });

    Route::prefix('erp/expenses')->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\ExpenseController::class, 'index']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\ExpenseController::class, 'store']);
        Route::get('/{expense}', [\App\Modules\ERP\Http\Controllers\ExpenseController::class, 'show']);
        Route::put('/{expense}', [\App\Modules\ERP\Http\Controllers\ExpenseController::class, 'update']);
        Route::patch('/{expense}', [\App\Modules\ERP\Http\Controllers\ExpenseController::class, 'update']);
        Route::delete('/{expense}', [\App\Modules\ERP\Http\Controllers\ExpenseController::class, 'destroy']);
        Route::post('/{expense}/approve', [\App\Modules\ERP\Http\Controllers\ExpenseController::class, 'approve']);
        Route::post('/{expense}/reject', [\App\Modules\ERP\Http\Controllers\ExpenseController::class, 'reject']);
    });

    Route::prefix('erp/expense-categories')->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\ExpenseCategoryController::class, 'index']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\ExpenseCategoryController::class, 'store']);
        Route::get('/{expenseCategory}', [\App\Modules\ERP\Http\Controllers\ExpenseCategoryController::class, 'show']);
        Route::put('/{expenseCategory}', [\App\Modules\ERP\Http\Controllers\ExpenseCategoryController::class, 'update']);
        Route::patch('/{expenseCategory}', [\App\Modules\ERP\Http\Controllers\ExpenseCategoryController::class, 'update']);
        Route::delete('/{expenseCategory}', [\App\Modules\ERP\Http\Controllers\ExpenseCategoryController::class, 'destroy']);
    });

    Route::prefix('erp/inventory-serials')->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\InventorySerialController::class, 'index']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\InventorySerialController::class, 'store']);
        Route::get('/{inventorySerial}', [\App\Modules\ERP\Http\Controllers\InventorySerialController::class, 'show']);
        Route::put('/{inventorySerial}', [\App\Modules\ERP\Http\Controllers\InventorySerialController::class, 'update']);
        Route::delete('/{inventorySerial}', [\App\Modules\ERP\Http\Controllers\InventorySerialController::class, 'destroy']);
    });

    Route::prefix('erp/reorder-rules')->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\ReorderRuleController::class, 'index']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\ReorderRuleController::class, 'store']);
        Route::get('/{reorderRule}', [\App\Modules\ERP\Http\Controllers\ReorderRuleController::class, 'show']);
        Route::put('/{reorderRule}', [\App\Modules\ERP\Http\Controllers\ReorderRuleController::class, 'update']);
        Route::delete('/{reorderRule}', [\App\Modules\ERP\Http\Controllers\ReorderRuleController::class, 'destroy']);
        Route::post('/check-and-reorder', [\App\Modules\ERP\Http\Controllers\ReorderRuleController::class, 'checkAndReorder']);
    });

    Route::prefix('erp/suppliers')->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\SupplierController::class, 'index']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\SupplierController::class, 'store']);
        Route::get('/{supplier}', [\App\Modules\ERP\Http\Controllers\SupplierController::class, 'show']);
        Route::put('/{supplier}', [\App\Modules\ERP\Http\Controllers\SupplierController::class, 'update']);
        Route::patch('/{supplier}', [\App\Modules\ERP\Http\Controllers\SupplierController::class, 'update']);
        Route::delete('/{supplier}', [\App\Modules\ERP\Http\Controllers\SupplierController::class, 'destroy']);
    });

    Route::prefix('erp/supplier-reports')->group(function () {
        Route::get('/performance/{supplierId}', [\App\Modules\ERP\Http\Controllers\SupplierReportController::class, 'performance']);
        Route::get('/summary', [\App\Modules\ERP\Http\Controllers\SupplierReportController::class, 'summary']);
    });

    Route::prefix('erp/accounts')->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\AccountController::class, 'index']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\AccountController::class, 'store']);
        Route::get('/{account}', [\App\Modules\ERP\Http\Controllers\AccountController::class, 'show']);
        Route::put('/{account}', [\App\Modules\ERP\Http\Controllers\AccountController::class, 'update']);
        Route::patch('/{account}', [\App\Modules\ERP\Http\Controllers\AccountController::class, 'update']);
        Route::delete('/{account}', [\App\Modules\ERP\Http\Controllers\AccountController::class, 'destroy']);
    });

    Route::prefix('erp/journal-entries')->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\JournalEntryController::class, 'index']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\JournalEntryController::class, 'store']);
        Route::get('/{journalEntry}', [\App\Modules\ERP\Http\Controllers\JournalEntryController::class, 'show']);
        Route::put('/{journalEntry}', [\App\Modules\ERP\Http\Controllers\JournalEntryController::class, 'update']);
        Route::patch('/{journalEntry}', [\App\Modules\ERP\Http\Controllers\JournalEntryController::class, 'update']);
        Route::delete('/{journalEntry}', [\App\Modules\ERP\Http\Controllers\JournalEntryController::class, 'destroy']);
        Route::post('/{journalEntry}/post', [\App\Modules\ERP\Http\Controllers\JournalEntryController::class, 'post']);
    });

    Route::prefix('erp/reports')->middleware(['tenant.rate_limit:60,1'])->group(function () {
        // ERP analytics reports (CRM-like)
        Route::get('/products', [\App\Modules\ERP\Http\Controllers\ReportsController::class, 'products']);
        Route::get('/product-categories', [\App\Modules\ERP\Http\Controllers\ReportsController::class, 'productCategories']);
        Route::get('/suppliers', [\App\Modules\ERP\Http\Controllers\ReportsController::class, 'suppliers']);
        Route::get('/purchase-orders', [\App\Modules\ERP\Http\Controllers\ReportsController::class, 'purchaseOrders']);
        Route::get('/sales-orders', [\App\Modules\ERP\Http\Controllers\ReportsController::class, 'salesOrders']);
        Route::get('/invoices', [\App\Modules\ERP\Http\Controllers\ReportsController::class, 'invoices']);

        // ERP report templates (existing)
        Route::get('/', [\App\Modules\ERP\Http\Controllers\ReportController::class, 'index']);
        Route::get('/{report}', [\App\Modules\ERP\Http\Controllers\ReportController::class, 'show']);
        Route::get('/{report}/export', [\App\Modules\ERP\Http\Controllers\ReportController::class, 'export']);
    });


    Route::prefix('erp/financial-reports')->middleware(['tenant.rate_limit:60,1'])->group(function () {
        Route::get('/trial-balance', [\App\Modules\ERP\Http\Controllers\FinancialReportController::class, 'trialBalance']);
        Route::get('/general-ledger', [\App\Modules\ERP\Http\Controllers\FinancialReportController::class, 'generalLedger']);
        Route::get('/profit-loss', [\App\Modules\ERP\Http\Controllers\FinancialReportController::class, 'profitLoss']);
        Route::get('/balance-sheet', [\App\Modules\ERP\Http\Controllers\FinancialReportController::class, 'balanceSheet']);
        Route::get('/vat-return', [\App\Modules\ERP\Http\Controllers\FinancialReportController::class, 'vatReturn']);
    });

    Route::prefix('erp/fiscal-years')->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\FiscalYearController::class, 'index']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\FiscalYearController::class, 'store']);
        Route::get('/{fiscalYear}', [\App\Modules\ERP\Http\Controllers\FiscalYearController::class, 'show']);
        Route::put('/{fiscalYear}', [\App\Modules\ERP\Http\Controllers\FiscalYearController::class, 'update']);
        Route::delete('/{fiscalYear}', [\App\Modules\ERP\Http\Controllers\FiscalYearController::class, 'destroy']);
    });

    Route::prefix('erp/fiscal-periods')->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\FiscalPeriodController::class, 'index']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\FiscalPeriodController::class, 'store']);
        Route::get('/{fiscalPeriod}', [\App\Modules\ERP\Http\Controllers\FiscalPeriodController::class, 'show']);
        Route::put('/{fiscalPeriod}', [\App\Modules\ERP\Http\Controllers\FiscalPeriodController::class, 'update']);
        Route::delete('/{fiscalPeriod}', [\App\Modules\ERP\Http\Controllers\FiscalPeriodController::class, 'destroy']);
    });

    Route::prefix('erp/dashboard')->middleware(['tenant.rate_limit:60,1'])->group(function () {
        Route::get('/metrics', [\App\Modules\ERP\Http\Controllers\DashboardController::class, 'metrics']);
        Route::get('/recent-activities', [\App\Modules\ERP\Http\Controllers\DashboardController::class, 'recentActivities']);
        Route::get('/module-summary', [\App\Modules\ERP\Http\Controllers\DashboardController::class, 'moduleSummary']);
    });

    // ERP System Settings
    Route::prefix('erp/settings')->middleware(['tenant.rate_limit:30,1'])->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\SystemSettingsController::class, 'index']);
        Route::get('/{key}', [\App\Modules\ERP\Http\Controllers\SystemSettingsController::class, 'show']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\SystemSettingsController::class, 'store']);
        Route::put('/{key}', [\App\Modules\ERP\Http\Controllers\SystemSettingsController::class, 'update']);
        Route::delete('/{key}', [\App\Modules\ERP\Http\Controllers\SystemSettingsController::class, 'destroy']);
    });


    Route::prefix('erp/system-health')->middleware(['tenant.rate_limit:10,1'])->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\SystemHealthController::class, 'index']);
        Route::post('/check', [\App\Modules\ERP\Http\Controllers\SystemHealthController::class, 'check']);
    });


    Route::prefix('tenant/settings')->middleware(['tenant.rate_limit:30,1'])->group(function () {
        Route::get('/', [\App\Tenant\Http\Controllers\TenantSettingsController::class, 'index']);
        Route::put('/', [\App\Tenant\Http\Controllers\TenantSettingsController::class, 'update']);
        Route::get('/email', [\App\Tenant\Http\Controllers\TenantSettingsController::class, 'getEmail']);
        Route::put('/email', [\App\Tenant\Http\Controllers\TenantSettingsController::class, 'updateEmail']);
        Route::post('/email/test', [\App\Tenant\Http\Controllers\TenantSettingsController::class, 'testEmail']);
        Route::get('/storage', [\App\Tenant\Http\Controllers\TenantSettingsController::class, 'getStorage']);
        Route::put('/storage', [\App\Tenant\Http\Controllers\TenantSettingsController::class, 'updateStorage']);
        Route::post('/storage/test-s3', [\App\Tenant\Http\Controllers\TenantSettingsController::class, 'testS3Connection']);
        Route::get('/security', [\App\Tenant\Http\Controllers\TenantSettingsController::class, 'getSecurity']);
        Route::put('/security', [\App\Tenant\Http\Controllers\TenantSettingsController::class, 'updateSecurity']);
    });

    Route::prefix('erp/notifications')->middleware(['tenant.rate_limit:60,1'])->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\NotificationController::class, 'index']);
        Route::post('/{notification}/mark-read', [\App\Modules\ERP\Http\Controllers\NotificationController::class, 'markRead']);
        Route::post('/mark-all-read', [\App\Modules\ERP\Http\Controllers\NotificationController::class, 'markAllRead']);
        Route::get('/unread-count', [\App\Modules\ERP\Http\Controllers\NotificationController::class, 'unreadCount']);
    });

    Route::prefix('erp/webhooks')->middleware(['tenant.rate_limit:30,1'])->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\WebhookController::class, 'index']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\WebhookController::class, 'store']);
        Route::get('/{webhook}', [\App\Modules\ERP\Http\Controllers\WebhookController::class, 'show']);
        Route::put('/{webhook}', [\App\Modules\ERP\Http\Controllers\WebhookController::class, 'update']);
        Route::delete('/{webhook}', [\App\Modules\ERP\Http\Controllers\WebhookController::class, 'destroy']);
    });

    Route::prefix('erp/activity-feed')->middleware(['tenant.rate_limit:60,1'])->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\ActivityFeedController::class, 'index']);
        Route::get('/entity/{entityType}/{entityId}', [\App\Modules\ERP\Http\Controllers\ActivityFeedController::class, 'entity']);
    });


    Route::prefix('erp/payment-gateways')->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\PaymentGatewayController::class, 'index']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\PaymentGatewayController::class, 'store']);
        Route::get('/{paymentGateway}', [\App\Modules\ERP\Http\Controllers\PaymentGatewayController::class, 'show']);
        Route::put('/{paymentGateway}', [\App\Modules\ERP\Http\Controllers\PaymentGatewayController::class, 'update']);
        Route::patch('/{paymentGateway}', [\App\Modules\ERP\Http\Controllers\PaymentGatewayController::class, 'update']);
        Route::delete('/{paymentGateway}', [\App\Modules\ERP\Http\Controllers\PaymentGatewayController::class, 'destroy']);
        Route::post('/{paymentGateway}/process-payment', [\App\Modules\ERP\Http\Controllers\PaymentGatewayController::class, 'processPayment']);
    });

    Route::prefix('erp/payment-webhooks')->group(function () {
        Route::post('/stripe', [\App\Modules\ERP\Http\Controllers\PaymentWebhookController::class, 'stripe']);
        Route::post('/paypal', [\App\Modules\ERP\Http\Controllers\PaymentWebhookController::class, 'paypal']);
    });

    Route::prefix('erp/projects')->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\ProjectController::class, 'index']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\ProjectController::class, 'store']);
        Route::get('/{project}', [\App\Modules\ERP\Http\Controllers\ProjectController::class, 'show']);
        Route::put('/{project}', [\App\Modules\ERP\Http\Controllers\ProjectController::class, 'update']);
        Route::patch('/{project}', [\App\Modules\ERP\Http\Controllers\ProjectController::class, 'update']);
        Route::delete('/{project}', [\App\Modules\ERP\Http\Controllers\ProjectController::class, 'destroy']);

        Route::get('/{project}/tasks', [\App\Modules\ERP\Http\Controllers\ProjectTaskController::class, 'index']);
        Route::post('/{project}/tasks', [\App\Modules\ERP\Http\Controllers\ProjectTaskController::class, 'store']);
        Route::get('/{project}/tasks/{projectTask}', [\App\Modules\ERP\Http\Controllers\ProjectTaskController::class, 'show']);
        Route::put('/{project}/tasks/{projectTask}', [\App\Modules\ERP\Http\Controllers\ProjectTaskController::class, 'update']);
        Route::patch('/{project}/tasks/{projectTask}', [\App\Modules\ERP\Http\Controllers\ProjectTaskController::class, 'update']);
        Route::delete('/{project}/tasks/{projectTask}', [\App\Modules\ERP\Http\Controllers\ProjectTaskController::class, 'destroy']);
    });

    Route::prefix('ecommerce')->middleware(['tenant.rate_limit:60,1'])->group(function () {
        Route::prefix('upload')->group(function () {
            Route::post('/image', [\App\Modules\ECommerce\Http\Controllers\FileUploadController::class, 'uploadImage']);
        });


        Route::prefix('stores')->group(function () {
            Route::get('/', [\App\Modules\ECommerce\Http\Controllers\StoreController::class, 'index']);
            Route::get('/my-store', [\App\Modules\ECommerce\Http\Controllers\StoreController::class, 'myStore']);
            Route::post('/', [\App\Modules\ECommerce\Http\Controllers\StoreController::class, 'store']);
            Route::get('/{store}', [\App\Modules\ECommerce\Http\Controllers\StoreController::class, 'show']);
            Route::put('/{store}', [\App\Modules\ECommerce\Http\Controllers\StoreController::class, 'update']);
            Route::delete('/{store}', [\App\Modules\ECommerce\Http\Controllers\StoreController::class, 'destroy']);
        });


        Route::prefix('themes')->group(function () {
            Route::get('/', [\App\Modules\ECommerce\Http\Controllers\ThemeController::class, 'index']);
            Route::get('/templates', [\App\Modules\ECommerce\Http\Controllers\ThemeController::class, 'templates']);
            Route::post('/', [\App\Modules\ECommerce\Http\Controllers\ThemeController::class, 'store']);
            Route::post('/from-template', [\App\Modules\ECommerce\Http\Controllers\ThemeController::class, 'createFromTemplate']);
            Route::get('/{theme}', [\App\Modules\ECommerce\Http\Controllers\ThemeController::class, 'show']);
            Route::put('/{theme}', [\App\Modules\ECommerce\Http\Controllers\ThemeController::class, 'update']);
            Route::delete('/{theme}', [\App\Modules\ECommerce\Http\Controllers\ThemeController::class, 'destroy']);

            // Theme Pages
            Route::get('/{theme}/pages', [\App\Modules\ECommerce\Http\Controllers\ThemeController::class, 'getPages']);
            Route::get('/{theme}/pages/{pageType}', [\App\Modules\ECommerce\Http\Controllers\ThemeController::class, 'getPage']);
            Route::put('/{theme}/pages/{pageType}', [\App\Modules\ECommerce\Http\Controllers\ThemeController::class, 'updatePage']);
            Route::post('/{theme}/pages/{pageType}/publish', [\App\Modules\ECommerce\Http\Controllers\ThemeController::class, 'publishPage']);
        });


        Route::prefix('orders')->group(function () {
            Route::get('/', [\App\Modules\ECommerce\Http\Controllers\OrderController::class, 'index']);
            Route::get('/{order}', [\App\Modules\ECommerce\Http\Controllers\OrderController::class, 'show']);
            Route::put('/{order}', [\App\Modules\ECommerce\Http\Controllers\OrderController::class, 'update']);
        });


        Route::prefix('pages')->group(function () {
            Route::get('/', [\App\Modules\ECommerce\Http\Controllers\PageController::class, 'index']);
            Route::get('/templates', [\App\Modules\ECommerce\Http\Controllers\PageController::class, 'templates']);
            Route::get('/by-type', [\App\Modules\ECommerce\Http\Controllers\PageController::class, 'getByTypeAdmin']);
            Route::post('/', [\App\Modules\ECommerce\Http\Controllers\PageController::class, 'store']);
            Route::get('/{page}', [\App\Modules\ECommerce\Http\Controllers\PageController::class, 'show']);
            Route::put('/{page}', [\App\Modules\ECommerce\Http\Controllers\PageController::class, 'update']);
            Route::delete('/{page}', [\App\Modules\ECommerce\Http\Controllers\PageController::class, 'destroy']);
        });

        Route::prefix('page-builder')->group(function () {
            Route::get('/block-types', [\App\Modules\ECommerce\Http\Controllers\PageBuilderController::class, 'getBlockTypes']);
            Route::get('/reusable-blocks', [\App\Modules\ECommerce\Http\Controllers\PageBuilderController::class, 'getReusableBlocks']);
            Route::post('/reusable-blocks', [\App\Modules\ECommerce\Http\Controllers\PageBuilderController::class, 'createReusableBlock']);
            Route::post('/pages/{page}/content', [\App\Modules\ECommerce\Http\Controllers\PageBuilderController::class, 'savePageContent']);
            Route::post('/{page}/save', [\App\Modules\ECommerce\Http\Controllers\PageBuilderController::class, 'savePageContent']);
            Route::post('/{page}/publish', [\App\Modules\ECommerce\Http\Controllers\PageBuilderController::class, 'publishPageContent']);
        });

        Route::prefix('storefront-layouts')->group(function () {
            Route::get('/', [\App\Modules\ECommerce\Http\Controllers\StorefrontLayoutController::class, 'index']);
            Route::get('/by-store/{store}', [\App\Modules\ECommerce\Http\Controllers\StorefrontLayoutController::class, 'getByStore']);
            Route::post('/by-store/{store}', [\App\Modules\ECommerce\Http\Controllers\StorefrontLayoutController::class, 'saveByStore']);
            Route::post('/by-store/{store}/publish', [\App\Modules\ECommerce\Http\Controllers\StorefrontLayoutController::class, 'publishByStore']);
        });

        Route::prefix('product-sync')->group(function () {
            Route::get('/status', [\App\Modules\ECommerce\Http\Controllers\ProductSyncController::class, 'getStatus']);
            Route::post('/sync', [\App\Modules\ECommerce\Http\Controllers\ProductSyncController::class, 'sync']);
            Route::post('/unsync', [\App\Modules\ECommerce\Http\Controllers\ProductSyncController::class, 'unsync']);
            Route::post('/sync-all', [\App\Modules\ECommerce\Http\Controllers\ProductSyncController::class, 'syncAll']);
        });
    });


    Route::prefix('erp/timesheets')->group(function () {
        Route::get('/', [\App\Modules\ERP\Http\Controllers\TimesheetController::class, 'index']);
        Route::post('/', [\App\Modules\ERP\Http\Controllers\TimesheetController::class, 'store']);
        Route::get('/{timesheet}', [\App\Modules\ERP\Http\Controllers\TimesheetController::class, 'show']);
        Route::put('/{timesheet}', [\App\Modules\ERP\Http\Controllers\TimesheetController::class, 'update']);
        Route::patch('/{timesheet}', [\App\Modules\ERP\Http\Controllers\TimesheetController::class, 'update']);
        Route::delete('/{timesheet}', [\App\Modules\ERP\Http\Controllers\TimesheetController::class, 'destroy']);
        Route::post('/{timesheet}/submit', [\App\Modules\ERP\Http\Controllers\TimesheetController::class, 'submit']);
        Route::post('/{timesheet}/approve', [\App\Modules\ERP\Http\Controllers\TimesheetController::class, 'approve']);
        Route::post('/{timesheet}/reject', [\App\Modules\ERP\Http\Controllers\TimesheetController::class, 'reject']);
    });

    Route::prefix('hr/departments')->group(function () {
        Route::get('/', [\App\Modules\HR\Http\Controllers\DepartmentController::class, 'index']);
        Route::post('/', [\App\Modules\HR\Http\Controllers\DepartmentController::class, 'store']);
        Route::get('/{department}', [\App\Modules\HR\Http\Controllers\DepartmentController::class, 'show']);
        Route::put('/{department}', [\App\Modules\HR\Http\Controllers\DepartmentController::class, 'update']);
        Route::patch('/{department}', [\App\Modules\HR\Http\Controllers\DepartmentController::class, 'update']);
        Route::delete('/{department}', [\App\Modules\HR\Http\Controllers\DepartmentController::class, 'destroy']);
    });

    Route::prefix('hr/positions')->group(function () {
        Route::get('/', [\App\Modules\HR\Http\Controllers\PositionController::class, 'index']);
        Route::post('/', [\App\Modules\HR\Http\Controllers\PositionController::class, 'store']);
        Route::get('/{position}', [\App\Modules\HR\Http\Controllers\PositionController::class, 'show']);
        Route::put('/{position}', [\App\Modules\HR\Http\Controllers\PositionController::class, 'update']);
        Route::patch('/{position}', [\App\Modules\HR\Http\Controllers\PositionController::class, 'update']);
        Route::delete('/{position}', [\App\Modules\HR\Http\Controllers\PositionController::class, 'destroy']);
    });

    Route::prefix('hr/employees')->group(function () {
        Route::get('/', [\App\Modules\HR\Http\Controllers\EmployeeController::class, 'index']);
        Route::post('/', [\App\Modules\HR\Http\Controllers\EmployeeController::class, 'store']);
        Route::get('/{employee}', [\App\Modules\HR\Http\Controllers\EmployeeController::class, 'show']);
        Route::put('/{employee}', [\App\Modules\HR\Http\Controllers\EmployeeController::class, 'update']);
        Route::patch('/{employee}', [\App\Modules\HR\Http\Controllers\EmployeeController::class, 'update']);
        Route::delete('/{employee}', [\App\Modules\HR\Http\Controllers\EmployeeController::class, 'destroy']);
    });

    Route::prefix('hr/contracts')->group(function () {
        Route::get('/', [\App\Modules\HR\Http\Controllers\ContractController::class, 'index']);
        Route::post('/', [\App\Modules\HR\Http\Controllers\ContractController::class, 'store']);
        Route::get('/{contract}', [\App\Modules\HR\Http\Controllers\ContractController::class, 'show']);
        Route::put('/{contract}', [\App\Modules\HR\Http\Controllers\ContractController::class, 'update']);
        Route::patch('/{contract}', [\App\Modules\HR\Http\Controllers\ContractController::class, 'update']);
        Route::delete('/{contract}', [\App\Modules\HR\Http\Controllers\ContractController::class, 'destroy']);
    });

    Route::prefix('hr/employment-contracts')->group(function () {
        Route::get('/', [\App\Modules\HR\Http\Controllers\EmploymentContractController::class, 'index']);
        Route::post('/', [\App\Modules\HR\Http\Controllers\EmploymentContractController::class, 'store']);
        Route::get('/{employmentContract}', [\App\Modules\HR\Http\Controllers\EmploymentContractController::class, 'show']);
        Route::put('/{employmentContract}', [\App\Modules\HR\Http\Controllers\EmploymentContractController::class, 'update']);
        Route::patch('/{employmentContract}', [\App\Modules\HR\Http\Controllers\EmploymentContractController::class, 'update']);
        Route::delete('/{employmentContract}', [\App\Modules\HR\Http\Controllers\EmploymentContractController::class, 'destroy']);
    });

    Route::prefix('hr/attendances')->group(function () {
        Route::get('/', [\App\Modules\HR\Http\Controllers\AttendanceController::class, 'index']);
        Route::post('/', [\App\Modules\HR\Http\Controllers\AttendanceController::class, 'store']);
        Route::get('/{attendance}', [\App\Modules\HR\Http\Controllers\AttendanceController::class, 'show']);
        Route::put('/{attendance}', [\App\Modules\HR\Http\Controllers\AttendanceController::class, 'update']);
        Route::patch('/{attendance}', [\App\Modules\HR\Http\Controllers\AttendanceController::class, 'update']);
        Route::delete('/{attendance}', [\App\Modules\HR\Http\Controllers\AttendanceController::class, 'destroy']);
    });

    Route::prefix('hr/attendance-records')->group(function () {
        Route::get('/', [\App\Modules\HR\Http\Controllers\AttendanceRecordController::class, 'index']);
        Route::post('/', [\App\Modules\HR\Http\Controllers\AttendanceRecordController::class, 'store']);
        Route::get('/{attendanceRecord}', [\App\Modules\HR\Http\Controllers\AttendanceRecordController::class, 'show']);
        Route::put('/{attendanceRecord}', [\App\Modules\HR\Http\Controllers\AttendanceRecordController::class, 'update']);
        Route::patch('/{attendanceRecord}', [\App\Modules\HR\Http\Controllers\AttendanceRecordController::class, 'update']);
        Route::delete('/{attendanceRecord}', [\App\Modules\HR\Http\Controllers\AttendanceRecordController::class, 'destroy']);
    });

    Route::prefix('hr/leave-requests')->group(function () {
        Route::get('/', [\App\Modules\HR\Http\Controllers\LeaveRequestController::class, 'index']);
        Route::post('/', [\App\Modules\HR\Http\Controllers\LeaveRequestController::class, 'store']);
        Route::get('/{leaveRequest}', [\App\Modules\HR\Http\Controllers\LeaveRequestController::class, 'show']);
        Route::put('/{leaveRequest}', [\App\Modules\HR\Http\Controllers\LeaveRequestController::class, 'update']);
        Route::patch('/{leaveRequest}', [\App\Modules\HR\Http\Controllers\LeaveRequestController::class, 'update']);
        Route::delete('/{leaveRequest}', [\App\Modules\HR\Http\Controllers\LeaveRequestController::class, 'destroy']);
    });

    Route::prefix('hr/payrolls')->group(function () {
        Route::get('/', [\App\Modules\HR\Http\Controllers\PayrollController::class, 'index']);
        Route::post('/', [\App\Modules\HR\Http\Controllers\PayrollController::class, 'store']);
        Route::get('/{payroll}', [\App\Modules\HR\Http\Controllers\PayrollController::class, 'show']);
        Route::put('/{payroll}', [\App\Modules\HR\Http\Controllers\PayrollController::class, 'update']);
        Route::patch('/{payroll}', [\App\Modules\HR\Http\Controllers\PayrollController::class, 'update']);
        Route::delete('/{payroll}', [\App\Modules\HR\Http\Controllers\PayrollController::class, 'destroy']);
        Route::post('/{payroll}/approve', [\App\Modules\HR\Http\Controllers\PayrollController::class, 'approve']);
        Route::post('/{payroll}/mark-paid', [\App\Modules\HR\Http\Controllers\PayrollController::class, 'markPaid']);
    });

    Route::prefix('hr/recruitments')->group(function () {
        Route::get('/', [\App\Modules\HR\Http\Controllers\RecruitmentController::class, 'index']);
        Route::post('/', [\App\Modules\HR\Http\Controllers\RecruitmentController::class, 'store']);
        Route::get('/{recruitment}', [\App\Modules\HR\Http\Controllers\RecruitmentController::class, 'show']);
        Route::put('/{recruitment}', [\App\Modules\HR\Http\Controllers\RecruitmentController::class, 'update']);
        Route::patch('/{recruitment}', [\App\Modules\HR\Http\Controllers\RecruitmentController::class, 'update']);
        Route::delete('/{recruitment}', [\App\Modules\HR\Http\Controllers\RecruitmentController::class, 'destroy']);
    });

    Route::prefix('hr/performance-reviews')->group(function () {
        Route::get('/', [\App\Modules\HR\Http\Controllers\PerformanceReviewController::class, 'index']);
        Route::post('/', [\App\Modules\HR\Http\Controllers\PerformanceReviewController::class, 'store']);
        Route::get('/{performanceReview}', [\App\Modules\HR\Http\Controllers\PerformanceReviewController::class, 'show']);
        Route::put('/{performanceReview}', [\App\Modules\HR\Http\Controllers\PerformanceReviewController::class, 'update']);
        Route::patch('/{performanceReview}', [\App\Modules\HR\Http\Controllers\PerformanceReviewController::class, 'update']);
        Route::delete('/{performanceReview}', [\App\Modules\HR\Http\Controllers\PerformanceReviewController::class, 'destroy']);
    });

    Route::prefix('hr/trainings')->group(function () {
        Route::get('/', [\App\Modules\HR\Http\Controllers\TrainingController::class, 'index']);
        Route::post('/', [\App\Modules\HR\Http\Controllers\TrainingController::class, 'store']);
        Route::get('/{training}', [\App\Modules\HR\Http\Controllers\TrainingController::class, 'show']);
        Route::put('/{training}', [\App\Modules\HR\Http\Controllers\TrainingController::class, 'update']);
        Route::patch('/{training}', [\App\Modules\HR\Http\Controllers\TrainingController::class, 'update']);
        Route::delete('/{training}', [\App\Modules\HR\Http\Controllers\TrainingController::class, 'destroy']);
    });

    Route::prefix('hr/training-assignments')->group(function () {
        Route::get('/', [\App\Modules\HR\Http\Controllers\TrainingAssignmentController::class, 'index']);
        Route::post('/', [\App\Modules\HR\Http\Controllers\TrainingAssignmentController::class, 'store']);
        Route::get('/{trainingAssignment}', [\App\Modules\HR\Http\Controllers\TrainingAssignmentController::class, 'show']);
        Route::put('/{trainingAssignment}', [\App\Modules\HR\Http\Controllers\TrainingAssignmentController::class, 'update']);
        Route::patch('/{trainingAssignment}', [\App\Modules\HR\Http\Controllers\TrainingAssignmentController::class, 'update']);
        Route::delete('/{trainingAssignment}', [\App\Modules\HR\Http\Controllers\TrainingAssignmentController::class, 'destroy']);
    });

    Route::prefix('hr/employee-documents')->group(function () {
        Route::get('/', [\App\Modules\HR\Http\Controllers\EmployeeDocumentController::class, 'index']);
        Route::post('/', [\App\Modules\HR\Http\Controllers\EmployeeDocumentController::class, 'store']);
        Route::get('/{employeeDocument}', [\App\Modules\HR\Http\Controllers\EmployeeDocumentController::class, 'show']);
        Route::put('/{employeeDocument}', [\App\Modules\HR\Http\Controllers\EmployeeDocumentController::class, 'update']);
        Route::patch('/{employeeDocument}', [\App\Modules\HR\Http\Controllers\EmployeeDocumentController::class, 'update']);
        Route::delete('/{employeeDocument}', [\App\Modules\HR\Http\Controllers\EmployeeDocumentController::class, 'destroy']);
    });
});

Route::prefix('platform')->group(function () {
    Route::prefix('site-owners')->group(function () {
        Route::post('/', [\App\Platform\Http\Controllers\SiteOwnerController::class, 'create']);
        Route::get('/', [\App\Platform\Http\Controllers\SiteOwnerController::class, 'index'])
            ->middleware(['auth:sanctum', 'platform.owner']);
        Route::post('/assign-permission', [\App\Platform\Http\Controllers\SiteOwnerController::class, 'assignPermission'])
            ->middleware(['auth:sanctum', 'platform.owner']);
    });

    Route::middleware(['auth:sanctum', 'platform.owner'])->group(function () {
        Route::prefix('tenants')->group(function () {
            Route::get('/', [\App\Platform\Http\Controllers\TenantController::class, 'index']);
            Route::post('/', [\App\Platform\Http\Controllers\TenantController::class, 'store']);
            Route::get('/{tenant}', [\App\Platform\Http\Controllers\TenantController::class, 'show']);
            Route::put('/{tenant}', [\App\Platform\Http\Controllers\TenantController::class, 'update']);
            Route::patch('/{tenant}', [\App\Platform\Http\Controllers\TenantController::class, 'update']);
            Route::delete('/{tenant}', [\App\Platform\Http\Controllers\TenantController::class, 'destroy']);
            Route::post('/{tenant}/assign-owner', [\App\Platform\Http\Controllers\TenantController::class, 'assignOwner']);
            Route::post('/{tenant}/activate', [\App\Platform\Http\Controllers\TenantController::class, 'activate']);
            Route::post('/{tenant}/suspend', [\App\Platform\Http\Controllers\TenantController::class, 'suspend']);
        });

        Route::prefix('system-health')->group(function () {
            Route::get('/', [\App\Platform\Http\Controllers\PlatformSystemHealthController::class, 'index']);
            Route::get('/tenants', [\App\Platform\Http\Controllers\PlatformSystemHealthController::class, 'tenants']);
            Route::get('/alerts', [\App\Platform\Http\Controllers\PlatformSystemHealthController::class, 'alerts']);
        });

        Route::prefix('settings')->group(function () {
            Route::get('/', [\App\Platform\Http\Controllers\PlatformSettingsController::class, 'index']);
            Route::put('/', [\App\Platform\Http\Controllers\PlatformSettingsController::class, 'update']);
            Route::get('/email', [\App\Platform\Http\Controllers\PlatformSettingsController::class, 'getEmail']);
            Route::put('/email', [\App\Platform\Http\Controllers\PlatformSettingsController::class, 'updateEmail']);
            Route::post('/email/test', [\App\Platform\Http\Controllers\PlatformSettingsController::class, 'testEmail']);
            Route::get('/storage', [\App\Platform\Http\Controllers\PlatformSettingsController::class, 'getStorage']);
            Route::put('/storage', [\App\Platform\Http\Controllers\PlatformSettingsController::class, 'updateStorage']);
            Route::post('/storage/test-s3', [\App\Platform\Http\Controllers\PlatformSettingsController::class, 'testS3']);
            Route::get('/security', [\App\Platform\Http\Controllers\PlatformSettingsController::class, 'getSecurity']);
            Route::put('/security', [\App\Platform\Http\Controllers\PlatformSettingsController::class, 'updateSecurity']);
        });

        Route::prefix('analytics')->group(function () {
            Route::get('/overview', [\App\Platform\Http\Controllers\PlatformAnalyticsController::class, 'overview']);
            Route::get('/tenants-growth', [\App\Platform\Http\Controllers\PlatformAnalyticsController::class, 'tenantsGrowth']);
            Route::get('/users-growth', [\App\Platform\Http\Controllers\PlatformAnalyticsController::class, 'usersGrowth']);
            Route::get('/usage-by-tenant', [\App\Platform\Http\Controllers\PlatformAnalyticsController::class, 'usageByTenant']);
            Route::get('/tenant-usage', [\App\Platform\Http\Controllers\PlatformAnalyticsController::class, 'tenantUsage']);
        });


        Route::prefix('reports')->group(function () {
            Route::get('/tenants-summary', [\App\Platform\Http\Controllers\PlatformReportsController::class, 'tenantsSummary']);
            Route::get('/users-summary', [\App\Platform\Http\Controllers\PlatformReportsController::class, 'usersSummary']);
            Route::get('/usage-report', [\App\Platform\Http\Controllers\PlatformReportsController::class, 'usageReport']);
            Route::get('/activity-report', [\App\Platform\Http\Controllers\PlatformReportsController::class, 'activityReport']);
            Route::post('/export', [\App\Platform\Http\Controllers\PlatformReportsController::class, 'export']);
        });

        Route::get('/reports/download/{filename}', [\App\Platform\Http\Controllers\PlatformReportsController::class, 'download'])
            ->middleware(['auth:sanctum', 'platform.owner']);

        Route::prefix('audit-logs')->group(function () {
            Route::get('/', [\App\Platform\Http\Controllers\PlatformAuditLogsController::class, 'index']);
            Route::get('/statistics', [\App\Platform\Http\Controllers\PlatformAuditLogsController::class, 'statistics']);
            Route::get('/export', [\App\Platform\Http\Controllers\PlatformAuditLogsController::class, 'export']);
        });
    });
});

Route::get('/health', [\App\Http\Controllers\HealthCheckController::class, 'index']);

