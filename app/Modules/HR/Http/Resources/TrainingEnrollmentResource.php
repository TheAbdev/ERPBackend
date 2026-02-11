<?php

namespace App\Modules\HR\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingEnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'training_course_id' => $this->training_course_id,
            'employee_id' => $this->employee_id,
            'status' => $this->status,
            'score' => $this->score,
            'completed_at' => $this->completed_at,
            'training_course' => $this->whenLoaded('trainingCourse', fn () => new TrainingCourseResource($this->trainingCourse)),
            'employee' => $this->whenLoaded('employee', fn () => new EmployeeResource($this->employee)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

