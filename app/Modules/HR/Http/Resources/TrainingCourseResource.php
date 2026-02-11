<?php

namespace App\Modules\HR\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingCourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'currency_id' => $this->currency_id,
            'title' => $this->title,
            'description' => $this->description,
            'provider' => $this->provider,
            'cost' => $this->cost,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'currency' => $this->whenLoaded('currency'),
            'enrollments' => TrainingEnrollmentResource::collection($this->whenLoaded('enrollments')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

