<?php

namespace App\Modules\HR\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'parent_id' => $this->parent_id,
            'manager_id' => $this->manager_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'parent' => $this->whenLoaded('parent', fn () => new DepartmentResource($this->parent)),
            'manager' => $this->whenLoaded('manager'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
