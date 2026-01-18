<?php

namespace App\Modules\CRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
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
            'parent_id' => $this->parent_id,
            'body' => $this->body,
            'noteable' => $this->whenLoaded('noteable', function () {
                return [
                    'type' => $this->noteable_type,
                    'id' => $this->noteable_id,
                    'name' => $this->getNoteableName(),
                ];
            }),
            'created_by' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            'mentions' => $this->whenLoaded('mentions', function () {
                return $this->mentions->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                });
            }),
            'replies' => $this->whenLoaded('replies', function () {
                return NoteResource::collection($this->replies);
            }),
            'replies_count' => $this->when(isset($this->replies_count), $this->replies_count ?? $this->replies()->count()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get the name of the noteable entity.
     *
     * @return string|null
     */
    protected function getNoteableName(): ?string
    {
        if (! $this->noteable) {
            return null;
        }

        return match ($this->noteable_type) {
            'lead' => $this->noteable->name ?? null,
            'contact' => ($this->noteable->first_name ?? '') . ' ' . ($this->noteable->last_name ?? ''),
            'account' => $this->noteable->name ?? null,
            'deal' => $this->noteable->title ?? null,
            default => null,
        };
    }
}

