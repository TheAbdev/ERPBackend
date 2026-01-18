<?php

namespace App\Modules\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecurringInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'customer_address' => $this->customer_address,
            'currency_id' => $this->currency_id,
            'frequency' => $this->frequency,
            'interval' => $this->interval,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'occurrences' => $this->occurrences,
            'day_of_month' => $this->day_of_month,
            'day_of_week' => $this->day_of_week,
            'next_run_date' => $this->next_run_date,
            'last_run_date' => $this->last_run_date,
            'generated_count' => $this->generated_count,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'invoice_data' => $this->invoice_data,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

