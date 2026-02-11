<?php

namespace App\Modules\HR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrainingEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'training_course_id' => ['sometimes', 'exists:hr_training_courses,id'],
            'employee_id' => ['sometimes', 'exists:hr_employees,id'],
            'status' => ['nullable', 'string', 'max:50'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'completed_at' => ['nullable', 'date'],
        ];
    }
}

