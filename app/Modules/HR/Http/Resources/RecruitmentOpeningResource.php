<?php

namespace App\Modules\HR\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecruitmentOpeningResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'department_id' => $this->department_id,
            'position_id' => $this->position_id,
            'title' => $this->title,
            'description' => $this->description,
            'openings_count' => $this->openings_count,
            'status' => $this->status,
            'posted_date' => $this->posted_date,
            'close_date' => $this->close_date,
            'department' => $this->whenLoaded('department', fn () => new DepartmentResource($this->department)),
            'position' => $this->whenLoaded('position', fn () => new PositionResource($this->position)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

