<?php

namespace App\Modules\HR\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmploymentContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'employee_id' => $this->employee_id,
            'currency_id' => $this->currency_id,
            'contract_type' => $this->contract_type,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'salary' => $this->salary,
            'status' => $this->status,
            'terms' => $this->terms,
            'employee' => $this->whenLoaded('employee', fn () => new EmployeeResource($this->employee)),
            'currency' => $this->whenLoaded('currency'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

