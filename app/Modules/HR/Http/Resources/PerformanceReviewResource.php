<?php

namespace App\Modules\HR\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerformanceReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'employee_id' => $this->employee_id,
            'reviewer_id' => $this->reviewer_id,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'score' => $this->score,
            'status' => $this->status,
            'summary' => $this->summary,
            'employee' => $this->whenLoaded('employee', fn () => new EmployeeResource($this->employee)),
            'reviewer' => $this->whenLoaded('reviewer'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
