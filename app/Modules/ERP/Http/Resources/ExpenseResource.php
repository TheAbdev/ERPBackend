<?php

namespace App\Modules\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'expense_number' => $this->expense_number,
            'expense_category_id' => $this->expense_category_id,
            'category' => $this->whenLoaded('category'),
            'account_id' => $this->account_id,
            'account' => $this->whenLoaded('account'),
            'vendor_id' => $this->vendor_id,
            'vendor' => $this->whenLoaded('vendor'),
            'currency_id' => $this->currency_id,
            'currency' => $this->whenLoaded('currency'),
            'payee_name' => $this->payee_name,
            'description' => $this->description,
            'amount' => $this->amount,
            'expense_date' => $this->expense_date,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'approved_by' => $this->approved_by,
            'approver' => $this->whenLoaded('approver'),
            'approved_at' => $this->approved_at,
            'rejection_reason' => $this->rejection_reason,
            'receipt_path' => $this->receipt_path,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

