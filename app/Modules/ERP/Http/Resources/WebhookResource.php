<?php

namespace App\Modules\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebhookResource extends JsonResource
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
            'url' => $this->url,
            'secret' => $this->when($request->user()->can('erp.webhooks.viewSecret'), $this->secret),
            'is_active' => $this->is_active,
            'module' => $this->module,
            'event_types' => $this->event_types,
            'last_delivery_status' => $this->last_delivery_status,
            'last_delivery_at' => $this->last_delivery_at?->toDateTimeString(),
            'deliveries' => WebhookDeliveryResource::collection($this->whenLoaded('deliveries')),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}

