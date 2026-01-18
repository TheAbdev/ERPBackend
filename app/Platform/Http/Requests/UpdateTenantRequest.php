<?php

namespace App\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = $this->route('tenant');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('tenants', 'slug')->ignore($tenantId)],
            'subdomain' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('tenants', 'subdomain')->ignore($tenantId)],
            'domain' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('tenants', 'domain')->ignore($tenantId)],
            'status' => ['sometimes', 'nullable', 'string', Rule::in(['active', 'suspended', 'inactive'])],
            'settings' => ['sometimes', 'nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tenant name is required.',
            'slug.unique' => 'This slug is already taken.',
            'subdomain.unique' => 'This subdomain is already taken.',
            'domain.unique' => 'This domain is already taken.',
            'status.in' => 'Status must be one of: active, suspended, inactive.',
        ];
    }
}

