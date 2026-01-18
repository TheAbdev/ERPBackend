<?php

namespace App\Modules\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'credit_note_number' => $this->credit_note_number,
            'sales_invoice_id' => $this->sales_invoice_id,
            'sales_invoice' => $this->whenLoaded('salesInvoice'),
            'fiscal_year_id' => $this->fiscal_year_id,
            'fiscal_period_id' => $this->fiscal_period_id,
            'currency_id' => $this->currency_id,
            'currency' => $this->whenLoaded('currency'),
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'customer_address' => $this->customer_address,
            'reason' => $this->reason,
            'reason_description' => $this->reason_description,
            'status' => $this->status,
            'issue_date' => $this->issue_date,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'total' => $this->total,
            'remaining_amount' => $this->remaining_amount,
            'notes' => $this->notes,
            'items' => CreditNoteItemResource::collection($this->whenLoaded('items')),
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator'),
            'issued_by' => $this->issued_by,
            'issuer' => $this->whenLoaded('issuer'),
            'issued_at' => $this->issued_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

