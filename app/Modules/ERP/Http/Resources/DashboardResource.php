<?php

namespace App\Modules\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'erp' => $this->resource['erp'] ?? [],
            'crm' => $this->resource['crm'] ?? [],
            'financial' => $this->resource['financial'] ?? [],
        ];
    }
}

