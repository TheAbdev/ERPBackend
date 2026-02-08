<?php

namespace App\Modules\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FiscalPeriodResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'fiscal_year_id' => $this->fiscal_year_id,
            'name' => $this->name,
            'code' => $this->code,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => $this->is_active,
            'is_locked' => $this->is_locked,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

