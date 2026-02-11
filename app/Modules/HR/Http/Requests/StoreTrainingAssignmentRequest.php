<?php

namespace App\Modules\HR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrainingAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'training_id' => [
                'required',
                Rule::exists('hr_trainings', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'employee_id' => [
                'required',
                Rule::exists('hr_employees', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'status' => ['nullable', 'string', 'max:50'],
            'completion_date' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['tenant_id' => $this->user()->tenant_id]);
    }
}

