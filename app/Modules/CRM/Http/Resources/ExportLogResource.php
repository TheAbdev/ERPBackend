<?php

namespace App\Modules\CRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExportLogResource extends JsonResource
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
            'export_type' => $this->export_type,
            'filters' => $this->filters,
            'file_name' => $this->file_name,
            'signed_url' => $this->signed_url,
            'expires_at' => $this->expires_at?->toDateTimeString(),
            'record_count' => $this->record_count,
            'created_by' => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}

