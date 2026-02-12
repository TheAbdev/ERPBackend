<?php

namespace App\Modules\HR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'employee_id' => [
                'required',
                Rule::exists('hr_employees', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'type' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'total_days' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:50'],
            'reason' => ['nullable', 'string'],
          //  'approved_by' => ['nullable', Rule::exists('users', 'id')],
          //  'approved_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['tenant_id' => $this->user()->tenant_id]);
    }
}

