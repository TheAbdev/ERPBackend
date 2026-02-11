<?php

namespace App\Modules\HR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrainingAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'training_id' => ['sometimes', Rule::exists('hr_trainings', 'id')],
            'employee_id' => ['sometimes', Rule::exists('hr_employees', 'id')],
            'status' => ['nullable', 'string', 'max:50'],
            'completion_date' => ['nullable', 'date'],
        ];
    }
}

