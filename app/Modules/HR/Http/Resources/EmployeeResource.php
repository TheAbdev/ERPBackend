<?php

namespace App\Modules\HR\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'department_id' => $this->department_id,
            'position_id' => $this->position_id,
            'manager_id' => $this->manager_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => trim($this->first_name . ' ' . $this->last_name),
            'email' => $this->email,
            'phone' => $this->phone,
            'hire_date' => $this->hire_date?->toDateString(),
            'status' => $this->status,
            'employment_type' => $this->employment_type,
            'basic_salary' => $this->basic_salary,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'national_id' => $this->national_id,
            'address' => $this->address,
            'is_active' => $this->is_active,
            'department' => $this->whenLoaded('department', fn () => new DepartmentResource($this->department)),
            'position' => $this->whenLoaded('position', fn () => new PositionResource($this->position)),
            'manager' => $this->whenLoaded('manager', function () {
                return [
                    'id' => $this->manager->id,
                    'name' => trim($this->manager->first_name . ' ' . $this->manager->last_name),
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
