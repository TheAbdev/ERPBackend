<?php

namespace App\Modules\CRM\Services\Export;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\Contact;

class ContactExportService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Export contacts with filters.
     *
     * @param  array  $filters
     * @return array
     */
    public function export(array $filters = []): array
    {
        $query = Contact::where('tenant_id', $this->tenantContext->getTenantId());

        // Apply filters
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['user_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('created_by', $filters['user_id']);
            });
        }

        $contacts = $query->get();

        return $contacts->map(function ($contact) {
            return [
                'id' => $contact->id,
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'company' => $contact->company,
                'notes' => $contact->notes,
                'created_at' => $contact->created_at->toDateTimeString(),
            ];
        })->toArray();
    }
}







