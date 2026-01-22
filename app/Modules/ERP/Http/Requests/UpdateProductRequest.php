<?php

namespace App\Modules\ERP\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by ProductPolicy in the controller
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        $productId = $this->route('product')->id;

        return [
            'category_id' => [
                'nullable',
                Rule::exists('product_categories', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'sku')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($productId),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'unit_of_measure' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::in(\App\Modules\ERP\Constants\UnitsOfMeasure::codes()),
            ],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'is_tracked' => ['boolean'],
            'is_serialized' => ['boolean'],
            'is_batch_tracked' => ['boolean'],
            'type' => ['sometimes', 'required', 'string', Rule::in(['stock', 'service', 'kit'])],
            'is_active' => ['boolean'],
        ];
    }
}

