<?php

namespace App\Modules\HR\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'department_id' => $this->department_id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'department' => $this->whenLoaded('department', fn () => new DepartmentResource($this->department)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
