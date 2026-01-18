<?php

namespace App\Modules\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowInstanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentStep = $this->getCurrentStep();

        return [
            'id' => $this->id,
            'workflow' => $this->whenLoaded('workflow', fn () => [
                'id' => $this->workflow->id,
                'name' => $this->workflow->name,
                'entity_type' => $this->workflow->entity_type,
            ]),
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'entity' => $this->whenLoaded('entity', fn () => $this->getEntityIdentifier()),
            'current_step' => $this->current_step,
            'current_step_details' => $currentStep ? [
                'step_order' => $currentStep->step_order,
                'role_id' => $currentStep->role_id,
                'permission' => $currentStep->permission,
                'auto_approve' => $currentStep->auto_approve,
                'description' => $currentStep->description,
            ] : null,
            'status' => $this->status,
            'is_pending' => $this->isPending(),
            'is_approved' => $this->isApproved(),
            'is_rejected' => $this->isRejected(),
            'initiator' => $this->whenLoaded('initiator', fn () => [
                'id' => $this->initiator->id,
                'name' => $this->initiator->name,
                'email' => $this->initiator->email,
            ]),
            'initiated_at' => $this->initiated_at->toDateTimeString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'actions' => WorkflowActionResource::collection($this->whenLoaded('actions')),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }

    /**
     * Get entity identifier for display.
     *
     * @return array|null
     */
    protected function getEntityIdentifier(): ?array
    {
        if (!$this->relationLoaded('entity') || !$this->entity) {
            return null;
        }

        $identifierFields = ['number', 'code', 'invoice_number', 'payment_number', 'entry_number', 'asset_code', 'name', 'title'];
        $identifier = null;

        foreach ($identifierFields as $field) {
            if (isset($this->entity->$field)) {
                $identifier = (string) $this->entity->$field;
                break;
            }
        }

        return [
            'id' => $this->entity->id,
            'identifier' => $identifier ?? "#{$this->entity->id}",
            'type' => class_basename($this->entity),
        ];
    }
}

