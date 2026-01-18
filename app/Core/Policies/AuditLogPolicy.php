<?php

namespace App\Core\Policies;

use App\Core\Models\AuditLog;
use App\Models\User;
use App\Policies\BasePolicy;

class AuditLogPolicy extends BasePolicy
{
    protected function getModuleName(): string
    {
        return 'core';
    }

    protected function getResourceName(): string
    {
        return 'audit_logs';
    }

    /**
     * Determine if user can view any audit logs.
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'core.audit_logs.viewAny');
    }

    /**
     * Determine if user can view audit log.
     */
    public function view(User $user, $model): bool
    {
        $auditLog = $model instanceof AuditLog ? $model : null;

        if (! $auditLog) {
            return false;
        }

        // Users can only view audit logs from their tenant
        if ($auditLog->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $this->checkPermission($user, 'core.audit_logs.view');
    }
}

