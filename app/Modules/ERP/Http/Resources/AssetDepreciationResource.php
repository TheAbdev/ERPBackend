<?php

namespace App\Modules\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetDepreciationResource extends JsonResource
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
            'fixed_asset' => $this->whenLoaded('fixedAsset', fn () => [
                'id' => $this->fixedAsset->id,
                'asset_code' => $this->fixedAsset->asset_code,
                'name' => $this->fixedAsset->name,
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
            'depreciation_date' => $this->depreciation_date->format('Y-m-d'),
            'amount' => (float) $this->amount,
            'is_posted' => $this->is_posted,
            'journal_entry' => $this->whenLoaded('journalEntry', fn () => [
                'id' => $this->journalEntry->id,
                'entry_number' => $this->journalEntry->entry_number,
            ]),
            'posted_by' => $this->whenLoaded('poster', fn () => [
                'id' => $this->poster->id,
                'name' => $this->poster->name,
            ]),
            'posted_at' => $this->posted_at?->toDateTimeString(),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}

