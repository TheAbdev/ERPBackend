<?php

namespace App\Modules\HR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePerformanceReviewRequest extends FormRequest
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
                'sometimes',
                Rule::exists('hr_employees', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'reviewer_id' => ['nullable', Rule::exists('users', 'id')],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'score' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:50'],
            'summary' => ['nullable', 'string'],
        ];
    }
}
