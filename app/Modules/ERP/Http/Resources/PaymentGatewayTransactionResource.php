<?php

namespace App\Modules\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentGatewayTransactionResource extends JsonResource
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
            'payment_gateway_id' => $this->payment_gateway_id,
            'payment_id' => $this->payment_id,
            'gateway_transaction_id' => $this->gateway_transaction_id,
            'gateway_type' => $this->gateway_type,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method,
            'metadata' => $this->metadata,
            'failure_reason' => $this->failure_reason,
            'processed_at' => $this->processed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'payment_gateway' => $this->whenLoaded('paymentGateway', function () {
                return new PaymentGatewayResource($this->paymentGateway);
            }),
            'payment' => $this->whenLoaded('payment', function () {
                return new PaymentResource($this->payment);
            }),
        ];
    }
}
















