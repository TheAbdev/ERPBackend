<?php

namespace App\Modules\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'payment_number' => $this->payment_number,
            'type' => $this->type,
            'is_incoming' => $this->isIncoming(),
            'is_outgoing' => $this->isOutgoing(),
            'fiscal_year' => $this->whenLoaded('fiscalYear', fn () => [
                'id' => $this->fiscalYear->id,
                'name' => $this->fiscalYear->name,
            ]),
            'fiscal_period' => $this->whenLoaded('fiscalPeriod', fn () => [
                'id' => $this->fiscalPeriod->id,
                'name' => $this->fiscalPeriod->name,
                'code' => $this->fiscalPeriod->code,
            ]),
            'currency' => $this->whenLoaded('currency', fn () => [
                'id' => $this->currency->id,
                'code' => $this->currency->code,
                'name' => $this->currency->name,
            ]),
            'payment_date' => $this->payment_date->format('Y-m-d'),
            'amount' => (float) $this->amount,
            'payment_method' => $this->payment_method,
            'reference_number' => $this->reference_number,
            'reference' => $this->whenLoaded('reference'),
            'notes' => $this->notes,
            'total_allocated' => $this->getTotalAllocated(),
            'unallocated_amount' => $this->getUnallocatedAmount(),
            'created_by' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'allocations' => PaymentAllocationResource::collection($this->whenLoaded('allocations')),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}

