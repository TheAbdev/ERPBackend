<?php

namespace App\Modules\ERP\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Modules\ERP\Models\InventoryTransaction::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'product_variant_id' => [
                'nullable',
                Rule::exists('product_variants', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'batch_id' => [
                'nullable',
                Rule::exists('inventory_batches', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'transaction_type' => [
                'required',
                'string',
                Rule::in(['opening_balance', 'adjustment', 'transfer', 'receipt', 'issue']),
            ],
            'reference_type' => ['nullable', 'string'],
            'reference_id' => ['nullable', 'integer'],
            'quantity' => ['required', 'numeric'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'total_cost' => ['nullable', 'numeric', 'min:0'],
            'unit_of_measure_id' => [
                'nullable',
                Rule::exists('units_of_measure', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'unit_of_measure' => [
                'nullable',
                'string',
                'max:50',
                Rule::in(\App\Modules\ERP\Constants\UnitsOfMeasure::codes()),
            ],
            'base_quantity' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
            'transaction_date' => ['nullable', 'date'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'tenant_id' => $this->user()->tenant_id,
        ]);
    }
}

