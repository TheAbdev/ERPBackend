<?php

namespace App\Modules\CRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardMetricResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'label' => $this->resource['label'] ?? null,
            'value' => $this->resource['value'] ?? null,
            'change' => $this->resource['change'] ?? null,
            'change_type' => $this->resource['change_type'] ?? null, // positive, negative, neutral
        ];
    }
}

