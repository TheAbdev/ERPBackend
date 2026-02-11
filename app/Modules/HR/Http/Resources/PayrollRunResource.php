<?php

namespace App\Modules\HR\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'currency_id' => $this->currency_id,
            'expense_account_id' => $this->expense_account_id,
            'liability_account_id' => $this->liability_account_id,
            'journal_entry_id' => $this->journal_entry_id,
            'posted_by' => $this->posted_by,
            'period_start' => $this->period_start,
            'period_end' => $this->period_end,
            'status' => $this->status,
            'total_gross' => $this->total_gross,
            'total_deductions' => $this->total_deductions,
            'total_net' => $this->total_net,
            'posted_at' => $this->posted_at,
            'notes' => $this->notes,
            'currency' => $this->whenLoaded('currency'),
            'expense_account' => $this->whenLoaded('expenseAccount'),
            'liability_account' => $this->whenLoaded('liabilityAccount'),
            'journal_entry' => $this->whenLoaded('journalEntry'),
            'poster' => $this->whenLoaded('poster'),
            'items' => PayrollItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

