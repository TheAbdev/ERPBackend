# Performance & Optimization Guide

This document outlines the performance optimizations implemented in the ERP/CRM system.

## Table of Contents

1. [Caching Strategy](#caching-strategy)
2. [Query Optimization](#query-optimization)
3. [Queue Optimization](#queue-optimization)
4. [Database Indexes](#database-indexes)
5. [Best Practices](#best-practices)

## Caching Strategy

### Tenant-Aware Caching

All cache keys are prefixed with tenant ID to ensure multi-tenant isolation:

```php
use App\Core\Services\CacheService;

$cacheService = app(CacheService::class);

// Cache with tenant prefix
$cacheService->put('key', $value, 3600); // tenant:1:key
$cacheService->get('key'); // Automatically uses current tenant
```

### Permission Caching

User permissions and roles are cached to reduce database queries:

```php
use App\Core\Services\PermissionCacheService;

$permissionService = app(PermissionCacheService::class);

// Get cached user permissions
$permissions = $permissionService->getUserPermissions($user);

// Check permission (cached)
if ($permissionService->userHasPermission($user, 'crm.leads.view')) {
    // ...
}
```

### Cache Invalidation

Automatic cache invalidation on model changes:

- Model observers automatically clear related caches
- Permission cache cleared when roles/permissions change
- Tenant-wide cache clearing available

### Warm Up Cache

Warm up permission cache for better performance:

```bash
# Warm up current tenant
php artisan cache:warm-permissions

# Warm up specific tenant
php artisan cache:warm-permissions --tenant=1

# Warm up all tenants
php artisan cache:warm-permissions --all
```

## Query Optimization

### Eager Loading

Always eager load relationships to avoid N+1 queries:

```php
// Good: Eager load relationships
$deals = Deal::with(['pipeline', 'stage', 'assignee'])->get();

// Bad: N+1 query problem
$deals = Deal::all();
foreach ($deals as $deal) {
    echo $deal->pipeline->name; // Query executed for each deal
}
```

### Select Specific Columns

Only select columns you need:

```php
// Good: Select only needed columns
$leads = Lead::select(['id', 'name', 'email', 'status'])->get();

// Bad: Select all columns
$leads = Lead::all();
```

### Use Indexes

Always query using indexed columns (especially `tenant_id`):

```php
// Good: Uses index on (tenant_id, status)
$leads = Lead::where('tenant_id', $tenantId)
    ->where('status', 'new')
    ->get();

// Bad: Full table scan
$leads = Lead::where('status', 'new')->get();
```

### Pagination

Always paginate large result sets:

```php
// Good: Paginated
$leads = Lead::where('tenant_id', $tenantId)
    ->paginate(15);

// Bad: Loads all records
$leads = Lead::where('tenant_id', $tenantId)->get();
```

### Optimized Query Service

Use the `OptimizedQueryService` for common query patterns:

```php
use App\Core\Services\OptimizedQueryService;

$queryService = app(OptimizedQueryService::class);

// Get leads with relations (cached)
$leads = $queryService->getLeadsWithRelations(['status' => 'new']);

// Get paginated deals
$deals = $queryService->getPaginatedDeals(15, ['status' => 'open']);

// Get cached statistics
$stats = $queryService->getDealStatistics(['date_from' => '2024-01-01']);
```

## Queue Optimization

### Base Job Class

All jobs should extend `BaseJob` for consistent retry handling:

```php
use App\Jobs\BaseJob;

class MyJob extends BaseJob
{
    public function handle(): void
    {
        // Job logic
    }
}
```

### Retry Configuration

Configure retry attempts and backoff in `config/performance.php`:

```php
'queue' => [
    'max_retries' => 3,
    'retry_delay' => 60, // Exponential backoff: 60s, 120s, 240s
    'job_timeout' => 300,
],
```

### Failed Job Handling

Failed jobs are automatically logged. Configure retention:

```php
'queue' => [
    'failed_job_retention' => 7, // Days
],
```

## Database Indexes

### Performance Indexes

The following indexes have been added for optimal query performance:

- **Users**: `(tenant_id, email)`
- **Leads**: `(tenant_id, status)`, `(tenant_id, assigned_to)`, `(tenant_id, created_at)`
- **Deals**: `(tenant_id, status)`, `(tenant_id, pipeline_id)`, `(tenant_id, stage_id)`, `(tenant_id, assigned_to)`, `(tenant_id, expected_close_date)`
- **Activities**: `(tenant_id, status)`, `(tenant_id, assigned_to)`, `(tenant_id, due_date)`, `(tenant_id, related_type, related_id)`
- **Notifications**: `(tenant_id, read_at)`, `(notifiable_type, notifiable_id, read_at)`

### Running Index Migration

```bash
php artisan migrate
```

## Best Practices

### 1. Always Use Tenant Scoping

```php
// Good
$leads = Lead::where('tenant_id', $tenantId)->get();

// Bad (security risk)
$leads = Lead::all();
```

### 2. Cache Expensive Operations

```php
// Cache report generation
$report = $cacheService->remember(
    "report:deals:{$filters}",
    900, // 15 minutes
    fn() => $this->generateReport($filters)
);
```

### 3. Use Database Transactions

```php
DB::transaction(function () {
    // Multiple database operations
});
```

### 4. Limit Result Sets

```php
// Always limit large queries
$items = Model::where('tenant_id', $tenantId)
    ->limit(1000)
    ->get();
```

### 5. Monitor Slow Queries

Enable query logging in development:

```php
// config/performance.php
'database' => [
    'log_queries' => true,
    'slow_query_threshold' => 1000, // milliseconds
],
```

### 6. Use Queue for Heavy Operations

```php
// Heavy operations should be queued
ProcessImportJob::dispatch($importId);
```

### 7. Optimize API Responses

- Use API Resources for consistent formatting
- Implement pagination
- Cache frequently accessed data
- Use eager loading to avoid N+1 queries

## Configuration

All performance settings are in `config/performance.php`:

- Cache TTLs
- Query limits
- Queue settings
- API rate limiting
- Database optimization

## Monitoring

Monitor performance using:

- Laravel Telescope (development)
- Queue monitoring: `php artisan queue:monitor`
- Cache statistics: Redis `INFO` command
- Database slow query log

## Redis Configuration

Ensure Redis is configured for caching:

```env
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## Queue Configuration

Configure queue connection:

```env
QUEUE_CONNECTION=redis
```

Run queue worker:

```bash
php artisan queue:work redis --tries=3 --timeout=300
```

