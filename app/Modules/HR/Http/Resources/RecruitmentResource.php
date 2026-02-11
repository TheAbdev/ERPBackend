<?php

namespace App\Modules\HR\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecruitmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'position' => $this->position ? [
                'id' => $this->position->id,
                'title' => $this->position->title,
            ] : null,
            'candidate_name' => $this->candidate_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'applied_at' => $this->applied_at?->toDateTimeString(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}

