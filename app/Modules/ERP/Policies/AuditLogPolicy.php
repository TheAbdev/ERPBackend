<?php

namespace App\Modules\ERP\Policies;

class AuditLogPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'audit';
    }

    /**
     * Determine if user can export audit logs.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function export(\App\Models\User $user): bool
    {
        return $user->can('erp.audit.export');
    }
}




