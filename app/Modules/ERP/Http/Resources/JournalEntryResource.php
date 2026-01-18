<?php

namespace App\Modules\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
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
            'entry_number' => $this->entry_number,
            'fiscal_year' => $this->whenLoaded('fiscalYear', fn () => [
                'id' => $this->fiscalYear->id,
                'name' => $this->fiscalYear->name,
            ]),
            'fiscal_period' => $this->whenLoaded('fiscalPeriod', fn () => [
                'id' => $this->fiscalPeriod->id,
                'name' => $this->fiscalPeriod->name,
                'code' => $this->fiscalPeriod->code,
            ]),
            'entry_date' => $this->entry_date->format('Y-m-d'),
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'reference' => $this->whenLoaded('reference'),
            'description' => $this->description,
            'status' => $this->status,
            'is_posted' => $this->isPosted(),
            'is_draft' => $this->isDraft(),
            'total_debits' => $this->getTotalDebits(),
            'total_credits' => $this->getTotalCredits(),
            'is_balanced' => $this->isBalanced(),
            'created_by' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'posted_by' => $this->whenLoaded('poster', fn () => [
                'id' => $this->poster->id,
                'name' => $this->poster->name,
            ]),
            'posted_at' => $this->posted_at?->toDateTimeString(),
            'lines' => JournalEntryLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}

