<?php

namespace App\Modules\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'barcode' => $this->barcode,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null,
            'unit_of_measure' => $this->unit_of_measure,
            'quantity' => $this->quantity ?? 0,
            'is_tracked' => $this->is_tracked,
            'is_serialized' => $this->is_serialized,
            'is_batch_tracked' => $this->is_batch_tracked,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'total_quantity_on_hand' => $this->when($this->is_tracked, fn () => $this->getTotalQuantityOnHand()),
            'total_available_quantity' => $this->when($this->is_tracked, fn () => $this->getTotalAvailableQuantity()),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}

