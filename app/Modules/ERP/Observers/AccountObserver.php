<?php

namespace App\Modules\ERP\Observers;

use App\Core\Services\AuditService;
use App\Modules\ERP\Models\Account;

/**
 * Observer for Account (Chart of Accounts) audit logging.
 */
class AccountObserver
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle the Account "created" event.
     *
     * @param  \App\Modules\ERP\Models\Account  $account
     * @return void
     */
    public function created(Account $account): void
    {
        $this->auditService->log(
            'create',
            $account,
            null,
            [
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'is_active' => $account->is_active,
            ],
            [
                'description' => "Created account: {$account->code} - {$account->name}",
            ]
        );
    }

    /**
     * Handle the Account "updated" event.
     *
     * @param  \App\Modules\ERP\Models\Account  $account
     * @return void
     */
    public function updated(Account $account): void
    {
        $changes = $account->getChanges();
        $exclusions = ['updated_at'];

        // Filter out non-critical fields
        $relevantChanges = array_diff_key($changes, array_flip($exclusions));

        if (!empty($relevantChanges)) {
            $oldValues = [];
            $newValues = [];

            foreach ($relevantChanges as $field => $newValue) {
                $oldValues[$field] = $account->getOriginal($field);
                $newValues[$field] = $newValue;
            }

            $this->auditService->log(
                'update',
                $account,
                $oldValues,
                $newValues,
                [
                    'description' => "Updated account: {$account->code} - {$account->name}",
                ]
            );
        }
    }

    /**
     * Handle the Account "deleted" event.
     *
     * @param  \App\Modules\ERP\Models\Account  $account
     * @return void
     */
    public function deleted(Account $account): void
    {
        $this->auditService->log(
            'delete',
            $account,
            [
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
            ],
            null,
            [
                'description' => "Deleted account: {$account->code} - {$account->name}",
            ]
        );
    }
}

