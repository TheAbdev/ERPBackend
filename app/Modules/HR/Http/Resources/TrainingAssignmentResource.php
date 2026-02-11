<?php

namespace App\Modules\HR\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'training' => $this->training ? [
                'id' => $this->training->id,
                'title' => $this->training->title,
            ] : null,
            'employee' => $this->employee ? [
                'id' => $this->employee->id,
                'name' => trim($this->employee->first_name . ' ' . $this->employee->last_name),
            ] : null,
            'status' => $this->status,
            'completion_date' => $this->completion_date?->toDateString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}

