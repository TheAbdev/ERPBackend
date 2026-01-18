<?php

namespace App\Modules\ERP\Policies;

use App\Models\User;
use App\Modules\ERP\Models\Timesheet;

class TimesheetPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'timesheets';
    }

    /**
     * Determine if the user can approve timesheets.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Modules\ERP\Models\Timesheet  $timesheet
     * @return bool
     */
    public function approve(User $user, Timesheet $timesheet): bool
    {
        // Super Admin (Tenant Owner) has full access within their tenant
        if ($user->tenant_id) {
            // Check if user has super_admin role
            try {
                if ($user->hasRole('super_admin')) {
                    return $this->checkTenantAccess($user, $timesheet);
                }
            } catch (\Exception $e) {
                // Log error but continue
                \Illuminate\Support\Facades\Log::warning('Error checking super_admin role in approve', [
                    'user_id' => $user->id,
                    'tenant_id' => $user->tenant_id,
                    'error' => $e->getMessage(),
                ]);
            }
            
            // Fallback: Check if user is the tenant owner directly
            try {
                if (!$user->relationLoaded('tenant')) {
                    $user->load('tenant');
                }
                if ($user->tenant && $user->tenant->owner_user_id === $user->id) {
                    return $this->checkTenantAccess($user, $timesheet);
                }
            } catch (\Exception $e) {
                // Log error but continue
                \Illuminate\Support\Facades\Log::warning('Error checking tenant owner in approve', [
                    'user_id' => $user->id,
                    'tenant_id' => $user->tenant_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $this->checkTenantAccess($user, $timesheet)
            && $this->checkPermission($user, $this->getPermissionName('approve'));
    }
}

