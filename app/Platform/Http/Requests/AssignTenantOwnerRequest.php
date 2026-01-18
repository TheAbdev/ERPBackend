<?php

namespace App\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTenantOwnerRequest extends FormRequest
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
            // For existing user
            'user_id' => ['nullable', 'required_without_all:email,owner_name', 'integer', 'exists:users,id'],
            'email' => ['nullable', 'required_without_all:user_id,owner_name', 'email'],
            // For creating new owner user
            'owner_name' => ['nullable', 'required_with:owner_email,owner_password', 'string', 'max:255'],
            'owner_email' => ['nullable', 'required_with:owner_name,owner_password', 'email', 'unique:users,email'],
            'owner_password' => ['nullable', 'required_with:owner_name,owner_email', 'string', 'min:8'],
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
            'user_id.required_without_all' => 'Either user_id, email, or owner details (name, email, password) are required.',
            'email.required_without_all' => 'Either user_id, email, or owner details (name, email, password) are required.',
            'user_id.exists' => 'The specified user does not exist.',
            'owner_name.required_with' => 'Owner name is required when creating a new owner.',
            'owner_email.required_with' => 'Owner email is required when creating a new owner.',
            'owner_email.unique' => 'This email is already registered.',
            'owner_password.required_with' => 'Owner password is required when creating a new owner.',
            'owner_password.min' => 'Owner password must be at least 8 characters.',
        ];
    }
}

