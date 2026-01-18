<?php

namespace App\Modules\ERP\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceivePurchaseOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('purchaseOrder'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.batch_data' => ['nullable', 'array'],
            'items.*.batch_data.batch_number' => ['nullable', 'string', 'max:255'],
            'items.*.batch_data.lot_number' => ['nullable', 'string', 'max:255'],
            'items.*.batch_data.manufacturing_date' => ['nullable', 'date'],
            'items.*.batch_data.expiry_date' => ['nullable', 'date'],
        ];
    }
}

