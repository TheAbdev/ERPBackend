<?php

namespace App\Modules\CRM\Services;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\Contact;
use App\Modules\CRM\Models\Deal;
use App\Modules\CRM\Models\Lead;
use Illuminate\Support\Facades\DB;

class LeadConversionService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Convert lead to contact.
     */
    public function convertToContact(Lead $lead, array $additionalData = []): Contact
    {
        return DB::transaction(function () use ($lead, $additionalData) {
            $contact = Contact::create([
                'tenant_id' => $lead->tenant_id,
                'lead_id' => $lead->id,
                'first_name' => $additionalData['first_name'] ?? explode(' ', $lead->name)[0] ?? $lead->name,
                'last_name' => $additionalData['last_name'] ?? (count(explode(' ', $lead->name)) > 1 ? explode(' ', $lead->name)[1] : ''),
                'email' => $lead->email,
                'phone' => $lead->phone,
                'created_by' => $lead->created_by,
            ]);

            // Update lead status
            $lead->update(['status' => 'converted']);

            return $contact;
        });
    }

    /**
     * Convert lead to deal.
     */
    public function convertToDeal(Lead $lead, array $dealData): Deal
    {
        return DB::transaction(function () use ($lead, $dealData) {
            // Get default pipeline if not provided
            $pipelineId = $dealData['pipeline_id'] ?? null;
            $stageId = $dealData['stage_id'] ?? null;
            
            if (!$pipelineId) {
                // Try to get default pipeline
                $defaultPipeline = \App\Modules\CRM\Models\Pipeline::where('tenant_id', $lead->tenant_id)
                    ->where('is_default', true)
                    ->first();
                
                if ($defaultPipeline) {
                    $pipelineId = $defaultPipeline->id;
                    // Get first stage if no stage provided
                    if (!$stageId) {
                        $firstStage = $defaultPipeline->stages()->orderBy('position')->first();
                        if ($firstStage) {
                            $stageId = $firstStage->id;
                        }
                    }
                }
            }
            
            // Pipeline and stage are required
            if (!$pipelineId || !$stageId) {
                throw new \InvalidArgumentException('Pipeline and stage are required to create a deal.');
            }

            $deal = Deal::create([
                'tenant_id' => $lead->tenant_id,
                'lead_id' => $lead->id,
                'title' => $dealData['name'] ?? $lead->name, // Use 'title' field, not 'name'
                'amount' => $dealData['amount'] ?? 0,
                'currency' => $dealData['currency'] ?? 'USD',
                'pipeline_id' => $pipelineId,
                'stage_id' => $stageId,
                'probability' => $dealData['probability'] ?? 50,
                'expected_close_date' => $dealData['expected_close_date'] ?? null,
                'status' => 'open', // Set default status
                'assigned_to' => $dealData['assigned_to'] ?? $lead->assigned_to,
                'created_by' => $lead->created_by,
            ]);

            // Update lead status
            $lead->update(['status' => 'converted']);

            return $deal;
        });
    }

    /**
     * Convert lead to both contact and deal.
     */
    public function convertToContactAndDeal(Lead $lead, array $contactData = [], array $dealData = []): array
    {
        return DB::transaction(function () use ($lead, $contactData, $dealData) {
            $contact = $this->convertToContact($lead, $contactData);
            $deal = $this->convertToDeal($lead, array_merge($dealData, [
                'contact_id' => $contact->id,
            ]));

            return [
                'contact' => $contact,
                'deal' => $deal,
            ];
        });
    }
}

