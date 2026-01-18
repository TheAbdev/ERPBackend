<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audit Log Retention Policy
    |--------------------------------------------------------------------------
    |
    | Configure how long audit logs should be retained before automatic cleanup.
    | Set to null to disable automatic cleanup (logs retained indefinitely).
    | Value is in days.
    |
    */

    'retention_days' => env('AUDIT_RETENTION_DAYS', 2555), // Default: 7 years

    /*
    |--------------------------------------------------------------------------
    | Audit Log Exclusions
    |--------------------------------------------------------------------------
    |
    | Fields that should be excluded from audit logging by default.
    | These fields are typically non-critical or system-managed.
    |
    */

    'excluded_fields' => [
        'updated_at',
        'created_at',
        'deleted_at',
        'remember_token',
        'password',
        'password_confirmation',
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Log Actions
    |--------------------------------------------------------------------------
    |
    | Standard audit log actions that are tracked.
    |
    */

    'actions' => [
        'create',
        'update',
        'delete',
        'post',
        'cancel',
        'approve',
        'activate',
        'dispose',
        'issue',
        'apply',
        'reverse',
    ],

    /*
    |--------------------------------------------------------------------------
    | Enable Audit Logging
    |--------------------------------------------------------------------------
    |
    | Set to false to disable audit logging globally.
    |
    */

    'enabled' => env('AUDIT_ENABLED', true),
];

