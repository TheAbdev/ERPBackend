<?php

namespace App\Modules\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderItemResource extends JsonResource
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
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'sku' => $this->product->sku,
                'name' => $this->product->name,
            ]),
            'product_variant' => $this->whenLoaded('productVariant', fn () => [
                'id' => $this->productVariant->id,
                'sku' => $this->productVariant->sku,
                'name' => $this->productVariant->name,
            ]),
            'unit_of_measure' => $this->unit_of_measure,
            'quantity' => (float) $this->quantity,
            'base_quantity' => (float) $this->base_quantity,
            'unit_cost' => (float) $this->unit_cost,
            'discount_percentage' => (float) $this->discount_percentage,
            'discount_amount' => (float) $this->discount_amount,
            'tax_percentage' => (float) $this->tax_percentage,
            'tax_amount' => (float) $this->tax_amount,
            'line_total' => (float) $this->line_total,
            'received_quantity' => (float) $this->received_quantity,
            'remaining_quantity' => $this->getRemainingQuantity(),
            'is_fully_received' => $this->isFullyReceived(),
            'notes' => $this->notes,
            'line_number' => $this->line_number,
        ];
    }
}

