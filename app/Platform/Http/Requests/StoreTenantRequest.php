<?php

namespace App\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:tenants,slug'],
            'subdomain' => ['nullable', 'string', 'max:255', 'unique:tenants,subdomain'],
            'domain' => ['nullable', 'string', 'max:255', 'unique:tenants,domain'],
            'status' => ['nullable', 'string', Rule::in(['active', 'suspended', 'inactive'])],
            // For existing user
            'owner_email' => ['nullable', 'email'],
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            // For creating new owner user
            'owner_name' => ['nullable', 'required_with:owner_email,owner_password', 'string', 'max:255'],
            'owner_password' => ['nullable', 'required_with:owner_name,owner_email', 'string', 'min:8'],
            'settings' => ['nullable', 'array'],
            'settings.modules' => ['nullable', 'array'],
            'settings.modules.erp' => ['nullable', 'boolean'],
            'settings.modules.crm' => ['nullable', 'boolean'],
            'settings.modules.hr' => ['nullable', 'boolean'],
            'settings.modules.website' => ['nullable', 'boolean'],
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
            'owner_name.required_with' => 'Owner name is required when creating a new owner.',
            'owner_password.required_with' => 'Owner password is required when creating a new owner.',
            'owner_password.min' => 'Owner password must be at least 8 characters.',
        ];
    }
}
