<?php

namespace App\Modules\HR\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'employee_id' => $this->employee_id,
            'attendance_date' => $this->attendance_date,
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
            'hours_worked' => $this->hours_worked,
            'status' => $this->status,
            'notes' => $this->notes,
            'source' => $this->source,
            'external_id' => $this->external_id,
            'raw_payload' => $this->raw_payload,
            'employee' => $this->whenLoaded('employee', fn () => new EmployeeResource($this->employee)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

