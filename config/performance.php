<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for performance optimizations including caching, query
    | optimization, and queue settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */

    'cache' => [
        // Default cache TTL in seconds
        'default_ttl' => env('CACHE_DEFAULT_TTL', 3600),

        // Permission cache TTL (1 hour)
        'permission_cache_ttl' => env('CACHE_PERMISSION_TTL', 3600),

        // Role cache TTL (1 hour)
        'role_cache_ttl' => env('CACHE_ROLE_TTL', 3600),

        // User data cache TTL (30 minutes)
        'user_cache_ttl' => env('CACHE_USER_TTL', 1800),

        // Tenant data cache TTL (1 hour)
        'tenant_cache_ttl' => env('CACHE_TENANT_TTL', 3600),

        // Report cache TTL (15 minutes)
        'report_cache_ttl' => env('CACHE_REPORT_TTL', 900),
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Optimization
    |--------------------------------------------------------------------------
    */

    'query' => [
        // Enable query result caching
        'enable_caching' => env('QUERY_CACHE_ENABLED', true),

        // Maximum query result size before pagination required
        'max_result_size' => env('QUERY_MAX_RESULT_SIZE', 1000),

        // Enable eager loading by default
        'eager_load_relations' => env('QUERY_EAGER_LOAD', true),

        // Query timeout in seconds
        'timeout' => env('QUERY_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    */

    'queue' => [
        // Default queue connection
        'connection' => env('QUEUE_CONNECTION', 'redis'),

        // Maximum retry attempts
        'max_retries' => env('QUEUE_MAX_RETRIES', 3),

        // Retry delay in seconds (exponential backoff)
        'retry_delay' => env('QUEUE_RETRY_DELAY', 60),

        // Job timeout in seconds
        'job_timeout' => env('QUEUE_JOB_TIMEOUT', 300),

        // Failed job retention in days
        'failed_job_retention' => env('QUEUE_FAILED_RETENTION', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Performance
    |--------------------------------------------------------------------------
    */

    'api' => [
        // Enable API response caching
        'enable_caching' => env('API_CACHE_ENABLED', true),

        // API cache TTL in seconds
        'cache_ttl' => env('API_CACHE_TTL', 300),

        // Maximum pagination size
        'max_pagination_size' => env('API_MAX_PAGINATION', 100),

        // Default pagination size
        'default_pagination_size' => env('API_DEFAULT_PAGINATION', 15),

        // Enable rate limiting
        'rate_limiting_enabled' => env('API_RATE_LIMITING', true),

        // Rate limit per minute
        'rate_limit' => env('API_RATE_LIMIT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Optimization
    |--------------------------------------------------------------------------
    */

    'database' => [
        // Enable query logging (development only)
        'log_queries' => env('DB_LOG_QUERIES', false),

        // Slow query threshold in milliseconds
        'slow_query_threshold' => env('DB_SLOW_QUERY_THRESHOLD', 1000),

        // Enable connection pooling
        'connection_pooling' => env('DB_CONNECTION_POOLING', true),

        // Maximum connections per tenant
        'max_connections_per_tenant' => env('DB_MAX_CONNECTIONS_PER_TENANT', 10),
    ],
];

