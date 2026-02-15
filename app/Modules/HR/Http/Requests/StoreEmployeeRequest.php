<?php

namespace App\Modules\HR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'user_id' => ['nullable', Rule::exists('users', 'id')],
            'department_id' => [
                'nullable',
                Rule::exists('hr_departments', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'position_id' => [
                'nullable',
                Rule::exists('hr_positions', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'manager_id' => [
                'nullable',
                Rule::exists('hr_employees', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'biotime_emp_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('hr_employees', 'biotime_emp_code')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'hire_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
            'employment_type' => ['nullable', 'string', 'max:50'],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'date_of_birth' => ['nullable', 'date'],
            'national_id' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
