<?php

namespace App\Modules\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemHealthResource extends JsonResource
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
            'cpu_usage' => $this->cpu_usage,
            'memory_usage' => $this->memory_usage,
            'disk_usage' => $this->disk_usage,
            'active_connections' => $this->active_connections,
            'queue_size' => $this->queue_size,
            'metrics' => $this->metrics,
            'status' => $this->status,
            'is_healthy' => $this->isHealthy(),
            'is_warning' => $this->isWarning(),
            'is_critical' => $this->isCritical(),
            'last_checked_at' => $this->last_checked_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

