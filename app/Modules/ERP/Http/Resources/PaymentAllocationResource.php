<?php

namespace App\Modules\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentAllocationResource extends JsonResource
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
            'payment' => $this->whenLoaded('payment', fn () => [
                'id' => $this->payment->id,
                'payment_number' => $this->payment->payment_number,
            ]),
            'invoice_type' => $this->invoice_type,
            'invoice_id' => $this->invoice_id,
            'invoice' => $this->whenLoaded('invoice'),
            'amount' => (float) $this->amount,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}

