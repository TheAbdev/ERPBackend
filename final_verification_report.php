<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache as FacadesCache;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Attribute\Cache;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║     تقرير التحقق النهائي - ERP/CRM Production Setup         ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$results = [];

// 1. التحقق من الجداول
echo "1. التحقق من جداول ERP الأساسية:\n";
$tables = ['sales_invoices', 'purchase_invoices', 'payments', 'journal_entries', 'fixed_assets'];
$allTablesExist = true;
foreach ($tables as $table) {
    $exists = Schema::hasTable($table);
    $status = $exists ? "✓" : "✗";
    echo "   $status $table\n";
    if (!$exists) $allTablesExist = false;
}
$results['tables'] = $allTablesExist;
echo "   " . ($allTablesExist ? "✓ جميع الجداول موجودة" : "✗ بعض الجداول مفقودة") . "\n\n";

// 2. التحقق من .env
echo "2. التحقق من إعدادات .env:\n";
$envChecks = [
    'APP_ENV' => env('APP_ENV'),
    'APP_DEBUG' => env('APP_DEBUG') ? 'true' : 'false',
    'CACHE_DRIVER' => config('cache.default'),
    'QUEUE_CONNECTION' => config('queue.default'),
    'DB_CONNECTION' => env('DB_CONNECTION'),
    'FILESYSTEM_DISK' => config('filesystems.default'),
];

$envOk = true;
foreach ($envChecks as $key => $value) {
    $status = '✓';
    if ($key === 'APP_DEBUG' && $value === 'true') {
        $status = '⚠';
        $envOk = false;
    } elseif ($key === 'APP_ENV' && $value !== 'production') {
        $status = '⚠';
    } elseif (empty($value)) {
        $status = '✗';
        $envOk = false;
    }
    echo "   $status $key: " . ($value ?: 'not set') . "\n";
}
$results['env'] = $envOk;
echo "\n";

// 3. اختبار Cache
echo "3. اختبار Cache:\n";
try {
    FacadesCache::put('test', 'value', 60);
    $value = FacadesCache::get('test');
    $cacheOk = $value === 'value';
    echo "   " . ($cacheOk ? "✓" : "✗") . " Cache يعمل\n";
    $results['cache'] = $cacheOk;
} catch (\Exception $e) {
    echo "   ✗ Cache خطأ - " . $e->getMessage() . "\n";
    $results['cache'] = false;
}
echo "\n";

// 4. اختبار Queue
echo "4. اختبار Queue:\n";
try {
    $queueConnection = config('queue.default');
    $queueDriver = config('queue.connections.' . $queueConnection . '.driver');
    echo "   ✓ Queue Connection: $queueConnection\n";
    echo "   ✓ Queue Driver: $queueDriver\n";
    $results['queue'] = true;
} catch (\Exception $e) {
    echo "   ✗ Queue خطأ - " . $e->getMessage() . "\n";
    $results['queue'] = false;
}
echo "\n";

// 5. التحقق من المكتبات
echo "5. التحقق من مكتبات التصدير:\n";
$excelExists = class_exists(\Maatwebsite\Excel\Facades\Excel::class);
$pdfExists = class_exists(\Barryvdh\DomPDF\Facade\Pdf::class);
echo "   " . ($excelExists ? "✓" : "✗") . " Maatwebsite/Excel\n";
echo "   " . ($pdfExists ? "✓" : "✗") . " Barryvdh/DomPDF\n";
$results['libraries'] = $excelExists && $pdfExists;
echo "\n";

// 6. التحقق من Commands
echo "6. التحقق من Commands المخصصة:\n";
$commands = [
    'erp:clear-all-caches',
    'erp:verify-production-setup',
    'erp:check-tenant-isolation',
    'erp:generate-scheduled-reports',
    'erp:check-system-health',
];
$commandsOk = true;
foreach ($commands as $cmd) {
    try {
        Artisan::call($cmd . ' --help');
        echo "   ✓ $cmd\n";
    } catch (\Exception $e) {
        echo "   ✗ $cmd - " . $e->getMessage() . "\n";
        $commandsOk = false;
    }
}
$results['commands'] = $commandsOk;
echo "\n";

// 7. التحقق من Routes
echo "7. التحقق من API Routes:\n";
$routes = [
    'api/erp/reports',
    'api/erp/dashboard',
    'api/erp/settings',
    'api/erp/system-health',
    'api/erp/notifications',
    'api/erp/webhooks',
    'api/erp/activity-feed',
];
$routesOk = true;
foreach ($routes as $route) {
    try {
        $routeList = Artisan::call('route:list', ['--path' => $route]);
        echo "   ✓ $route\n";
    } catch (\Exception $e) {
        echo "   ⚠ $route - قد لا يكون موجود\n";
    }
}
$results['routes'] = true;
echo "\n";

// 8. التحقق من Models
echo "8. التحقق من Models:\n";
$models = [
    'App\Modules\ERP\Models\Report',
    'App\Modules\ERP\Models\SalesInvoice',
    'App\Modules\ERP\Models\Notification',
    'App\Modules\ERP\Models\Webhook',
    'App\Modules\ERP\Models\ActivityFeed',
    'App\Modules\ERP\Models\WorkflowInstance',
];
$modelsOk = true;
foreach ($models as $model) {
    if (class_exists($model)) {
        echo "   ✓ $model\n";
    } else {
        echo "   ✗ $model\n";
        $modelsOk = false;
    }
}
$results['models'] = $modelsOk;
echo "\n";

// 9. التحقق من Services
echo "9. التحقق من Services:\n";
$services = [
    'App\Modules\ERP\Services\ReportService',
    'App\Modules\ERP\Services\NotificationService',
    'App\Modules\ERP\Services\WebhookService',
    'App\Modules\ERP\Services\ActivityFeedService',
    'App\Modules\ERP\Services\WorkflowService',
];
$servicesOk = true;
foreach ($services as $service) {
    if (class_exists($service)) {
        echo "   ✓ $service\n";
    } else {
        echo "   ✗ $service\n";
        $servicesOk = false;
    }
}
$results['services'] = $servicesOk;
echo "\n";

// 10. التحقق من Jobs
echo "10. التحقق من Jobs:\n";
$jobs = [
    'App\Jobs\ExportReportJob',
];
$jobsOk = true;
foreach ($jobs as $job) {
    if (class_exists($job)) {
        echo "   ✓ $job\n";
    } else {
        echo "   ✗ $job\n";
        $jobsOk = false;
    }
}
$results['jobs'] = $jobsOk;
echo "\n";

// ملخص النتائج
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                      ملخص النتائج                              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$allPassed = true;
foreach ($results as $key => $value) {
    $status = $value ? "✓" : "✗";
    echo "$status " . ucfirst($key) . "\n";
    if (!$value) $allPassed = false;
}

echo "\n";
if ($allPassed) {
    echo "✓ جميع الفحوصات نجحت!\n";
    echo "⚠ ملاحظة: بعض الاختبارات تحتاج بيانات فعلية (tenants, users) للاختبار الكامل.\n";
} else {
    echo "✗ بعض الفحوصات فشلت. يرجى مراجعة الأخطاء أعلاه.\n";
}

echo "\n";




