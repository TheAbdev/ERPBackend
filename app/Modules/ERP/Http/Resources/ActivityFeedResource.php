<?php

namespace App\Modules\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityFeedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'entity' => $this->whenLoaded('entity', fn () => $this->getEntityIdentifier()),
            'action' => $this->action,
            'action_label' => ucfirst($this->action),
            'metadata' => $this->metadata,
            'description' => $this->formatForDisplay(),
            'created_at' => $this->created_at->toDateTimeString(),
            'created_at_human' => $this->created_at->diffForHumans(),
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

        $identifierFields = ['number', 'code', 'invoice_number', 'payment_number', 'entry_number', 'order_number', 'asset_code', 'name', 'title'];
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

