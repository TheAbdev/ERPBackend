<?php

namespace App\Modules\CRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
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
            'subject' => $this->subject,
            'description' => $this->description,
            'due_date' => $this->due_date,
            'priority' => $this->priority,
            'status' => $this->status,
            'related' => $this->whenLoaded('related', function () {
                return [
                    'type' => $this->related_type,
                    'id' => $this->related_id,
                    'name' => $this->getRelatedName(),
                ];
            }),
            'created_by' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            'assigned_to' => $this->whenLoaded('assignee', function () {
                return [
                    'id' => $this->assignee->id,
                    'name' => $this->assignee->name,
                    'email' => $this->assignee->email,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get the name of the related entity.
     *
     * @return string|null
     */
    protected function getRelatedName(): ?string
    {
        if (! $this->related) {
            return null;
        }

        return match ($this->related_type) {
            'lead' => $this->related->name ?? null,
            'contact' => ($this->related->first_name ?? '') . ' ' . ($this->related->last_name ?? ''),
            'account' => $this->related->name ?? null,
            'deal' => $this->related->title ?? null,
            default => null,
        };
    }
}

