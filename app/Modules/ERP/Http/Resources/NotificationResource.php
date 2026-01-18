<?php

namespace App\Modules\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'metadata' => $this->metadata,
            'entity' => $this->whenLoaded('entity', fn () => $this->getEntityIdentifier()),
            'is_read' => $this->isRead(),
            'is_unread' => $this->isUnread(),
            'read_at' => $this->read_at?->toDateTimeString(),
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

