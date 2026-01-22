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
        // Site Owner (site_owner role): has full access to all audit logs - check without tenant_id restriction
        $isSiteOwner = $user->roles()
            ->where('slug', 'site_owner')
            ->exists();

        if ($isSiteOwner) {
            return true;
        }

        // Super Admin (super_admin role): has access to their tenant's audit logs
        if ($user->tenant_id) {
            try {
                // Check if user has super_admin role
                if ($user->hasRole('super_admin')) {
                    return true;
                }

                // Also check if user is the tenant owner
                if ($user->isTenantOwner()) {
                    return true;
                }
            } catch (\Exception $e) {
                // Log error but continue
                \Illuminate\Support\Facades\Log::warning('Error checking tenant owner in AuditLogPolicy::viewAny', [
                    'user_id' => $user->id,
                    'tenant_id' => $user->tenant_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

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

    /**
     * Determine if user can delete audit logs.
     * For bulk delete, $model can be null.
     */
    public function delete(User $user, $model = null): bool
    {
        // Site Owner (site_owner role): can delete all audit logs
        $isSiteOwner = $user->roles()
            ->where('slug', 'site_owner')
            ->exists();

        if ($isSiteOwner) {
            return true;
        }

        // If model is provided, check tenant access
       /* if ($model instanceof AuditLog) {
            if ($model->tenant_id !== $user->tenant_id) {
                return false;
            }
        }*/

        // Super Admin (super_admin role): can delete their tenant's audit logs
        if ($user->tenant_id) {
            try {
                // Check if user has super_admin role
                if ($user->hasRole('super_admin')) {
                    return true;
                }

                // Also check if user is the tenant owner
                if ($user->isTenantOwner()) {
                    return true;
                }
            } catch (\Exception $e) {
                // Log error but continue
                \Illuminate\Support\Facades\Log::warning('Error checking tenant owner in AuditLogPolicy::delete', [
                    'user_id' => $user->id,
                    'tenant_id' => $user->tenant_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->checkPermission($user, 'core.audit_logs.delete');
    }
}

