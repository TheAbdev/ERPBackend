<?php

namespace App\Modules\CRM\Services\Export;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\Account;

class AccountExportService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Export accounts with filters.
     *
     * @param  array  $filters
     * @return array
     */
    public function export(array $filters = []): array
    {
        $query = Account::where('tenant_id', $this->tenantContext->getTenantId());

        // Apply filters
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['user_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('assigned_to', $filters['user_id'])
                  ->orWhere('created_by', $filters['user_id']);
            });
        }

        $accounts = $query->get();

        return $accounts->map(function ($account) {
            return [
                'id' => $account->id,
                'name' => $account->name,
                'industry' => $account->industry,
                'website' => $account->website,
                'phone' => $account->phone,
                'email' => $account->email,
                'billing_address' => $account->billing_address,
                'assigned_to' => $account->assignee ? $account->assignee->name : null,
                'created_at' => $account->created_at->toDateTimeString(),
            ];
        })->toArray();
    }
}






