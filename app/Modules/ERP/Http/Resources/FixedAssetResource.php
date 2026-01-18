<?php

namespace App\Modules\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FixedAssetResource extends JsonResource
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
            'asset_code' => $this->asset_code,
            'name' => $this->name,
            'description' => $this->description,
            'acquisition_date' => $this->acquisition_date->format('Y-m-d'),
            'acquisition_cost' => (float) $this->acquisition_cost,
            'salvage_value' => (float) $this->salvage_value,
            'useful_life_months' => $this->useful_life_months,
            'depreciation_method' => $this->depreciation_method,
            'status' => $this->status,
            'is_draft' => $this->isDraft(),
            'is_active' => $this->isActive(),
            'is_disposed' => $this->isDisposed(),
            'asset_account' => $this->whenLoaded('assetAccount', fn () => [
                'id' => $this->assetAccount->id,
                'code' => $this->assetAccount->code,
                'name' => $this->assetAccount->name,
            ]),
            'depreciation_expense_account' => $this->whenLoaded('depreciationExpenseAccount', fn () => [
                'id' => $this->depreciationExpenseAccount->id,
                'code' => $this->depreciationExpenseAccount->code,
                'name' => $this->depreciationExpenseAccount->name,
            ]),
            'accumulated_depreciation_account' => $this->whenLoaded('accumulatedDepreciationAccount', fn () => [
                'id' => $this->accumulatedDepreciationAccount->id,
                'code' => $this->accumulatedDepreciationAccount->code,
                'name' => $this->accumulatedDepreciationAccount->name,
            ]),
            'currency' => $this->whenLoaded('currency', fn () => [
                'id' => $this->currency->id,
                'code' => $this->currency->code,
                'name' => $this->currency->name,
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
            'activation_date' => $this->activation_date?->format('Y-m-d'),
            'disposal_date' => $this->disposal_date?->format('Y-m-d'),
            'disposal_amount' => $this->disposal_amount ? (float) $this->disposal_amount : null,
            'monthly_depreciation' => (float) $this->calculateMonthlyDepreciation(),
            'accumulated_depreciation' => (float) $this->getAccumulatedDepreciation(),
            'net_book_value' => (float) $this->getNetBookValue(),
            'remaining_useful_life_months' => $this->getRemainingUsefulLifeMonths(),
            'notes' => $this->notes,
            'created_by' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'activated_by' => $this->whenLoaded('activator', fn () => [
                'id' => $this->activator->id,
                'name' => $this->activator->name,
            ]),
            'disposed_by' => $this->whenLoaded('disposer', fn () => [
                'id' => $this->disposer->id,
                'name' => $this->disposer->name,
            ]),
            'depreciations' => AssetDepreciationResource::collection($this->whenLoaded('depreciations')),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}

