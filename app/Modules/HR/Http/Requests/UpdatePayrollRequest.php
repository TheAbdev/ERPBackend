<?php

namespace App\Modules\HR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['sometimes', Rule::exists('hr_employees', 'id')],
            'period_start' => ['sometimes', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'allowances' => ['nullable', 'numeric', 'min:0'],
            'deductions' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:50'],
            'expense_account_id' => ['nullable', Rule::exists('chart_of_accounts', 'id')],
            'payable_account_id' => ['nullable', Rule::exists('chart_of_accounts', 'id')],
            'notes' => ['nullable', 'string'],
        ];
    }
}

