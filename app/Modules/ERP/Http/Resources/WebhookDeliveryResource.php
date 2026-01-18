<?php

namespace App\Modules\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebhookDeliveryResource extends JsonResource
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
            'webhook_id' => $this->webhook_id,
            'event_type' => $this->event_type,
            'payload' => $this->payload,
            'status' => $this->status,
            'is_success' => $this->isSuccess(),
            'is_failure' => $this->isFailure(),
            'is_pending' => $this->isPending(),
            'response_code' => $this->response_code,
            'response_body' => $this->response_body,
            'error_message' => $this->error_message,
            'attempts' => $this->attempts,
            'delivered_at' => $this->delivered_at?->toDateTimeString(),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}

