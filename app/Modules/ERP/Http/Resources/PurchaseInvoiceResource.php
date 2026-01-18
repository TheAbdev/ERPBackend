<?php

namespace App\Modules\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseInvoiceResource extends JsonResource
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
            'invoice_number' => $this->invoice_number,
            'purchase_order' => $this->whenLoaded('purchaseOrder', fn () => [
                'id' => $this->purchaseOrder->id,
                'order_number' => $this->purchaseOrder->order_number,
            ]),
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
            'supplier_name' => $this->supplier_name,
            'supplier_email' => $this->supplier_email,
            'supplier_phone' => $this->supplier_phone,
            'supplier_address' => $this->supplier_address,
            'status' => $this->status,
            'is_draft' => $this->isDraft(),
            'is_issued' => $this->isIssued(),
            'is_partially_paid' => $this->isPartiallyPaid(),
            'is_paid' => $this->isPaid(),
            'is_cancelled' => $this->isCancelled(),
            'issue_date' => $this->issue_date->format('Y-m-d'),
            'due_date' => $this->due_date->format('Y-m-d'),
            'subtotal' => (float) $this->subtotal,
            'tax_amount' => (float) $this->tax_amount,
            'total' => (float) $this->total,
            'balance_due' => (float) $this->balance_due,
            'notes' => $this->notes,
            'created_by' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'issued_by' => $this->whenLoaded('issuer', fn () => [
                'id' => $this->issuer->id,
                'name' => $this->issuer->name,
            ]),
            'issued_at' => $this->issued_at?->toDateTimeString(),
            'items' => PurchaseInvoiceItemResource::collection($this->whenLoaded('items')),
            'payment_allocations' => PaymentAllocationResource::collection($this->whenLoaded('paymentAllocations')),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}

