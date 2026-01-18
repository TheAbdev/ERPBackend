<?php

namespace App\Modules\CRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FunnelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'stage_id' => $this->resource['stage_id'] ?? null,
            'stage_name' => $this->resource['stage_name'] ?? null,
            'position' => $this->resource['position'] ?? null,
            'deal_count' => $this->resource['deal_count'] ?? 0,
            'deal_value' => $this->resource['deal_value'] ?? 0,
        ];
    }
}

