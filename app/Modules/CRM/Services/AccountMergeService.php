<?php

namespace App\Modules\CRM\Services;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\Account;
use Illuminate\Support\Facades\DB;

class AccountMergeService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Merge source account into target account.
     */
    public function merge(Account $targetAccount, Account $sourceAccount): Account
    {
        return DB::transaction(function () use ($targetAccount, $sourceAccount) {
            // Merge contacts
            $sourceContacts = $sourceAccount->contacts()->get();
            foreach ($sourceContacts as $contact) {
                if (! $targetAccount->contacts()->where('contacts.id', $contact->id)->exists()) {
                    $targetAccount->contacts()->attach($contact->id, [
                        'tenant_id' => $this->tenantContext->getTenantId(),
                    ]);
                }
            }

            // Update related deals
            DB::table('deals')
                ->where('tenant_id', $this->tenantContext->getTenantId())
                ->where('account_id', $sourceAccount->id)
                ->update(['account_id' => $targetAccount->id]);

            // Update related activities
            DB::table('activities')
                ->where('tenant_id', $this->tenantContext->getTenantId())
                ->where('related_type', Account::class)
                ->where('related_id', $sourceAccount->id)
                ->update(['related_id' => $targetAccount->id]);

            // Update related notes
            DB::table('notes')
                ->where('tenant_id', $this->tenantContext->getTenantId())
                ->where('noteable_type', Account::class)
                ->where('noteable_id', $sourceAccount->id)
                ->update(['noteable_id' => $targetAccount->id]);

            // Merge tags
            $sourceTags = $sourceAccount->tags()->get();
            foreach ($sourceTags as $tag) {
                if (! $targetAccount->tags()->where('tags.id', $tag->id)->exists()) {
                    $targetAccount->tags()->attach($tag->id);
                }
            }

            // Delete source account
            $sourceAccount->delete();

            return $targetAccount->fresh();
        });
    }
}

