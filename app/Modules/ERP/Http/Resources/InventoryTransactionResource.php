<?php

namespace App\Modules\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryTransactionResource extends JsonResource
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
            'warehouse' => $this->whenLoaded('warehouse', fn () => [
                'id' => $this->warehouse->id,
                'name' => $this->warehouse->name,
            ]),
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
            'batch' => $this->whenLoaded('batch', fn () => [
                'id' => $this->batch->id,
                'batch_number' => $this->batch->batch_number,
            ]),
            'transaction_type' => $this->transaction_type,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'quantity' => (float) $this->quantity,
            'unit_cost' => (float) $this->unit_cost,
            'total_cost' => (float) $this->total_cost,
            'unit_of_measure_id' => $this->unit_of_measure_id,
            'unit_of_measure' => $this->unit_of_measure ?? ($this->whenLoaded('unitOfMeasure', fn () => $this->unitOfMeasure->code)),
            'unit_of_measure_detail' => $this->whenLoaded('unitOfMeasure', fn () => [
                'id' => $this->unitOfMeasure->id,
                'code' => $this->unitOfMeasure->code,
                'name' => $this->unitOfMeasure->name,
            ]),
            'base_quantity' => (float) $this->base_quantity,
            'notes' => $this->notes,
            'created_by' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'transaction_date' => $this->transaction_date->toDateTimeString(),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}

