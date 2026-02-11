<?php

namespace App\Modules\HR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecruitmentOpeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => ['nullable', 'exists:hr_departments,id'],
            'position_id' => ['nullable', 'exists:hr_positions,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'openings_count' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'max:50'],
            'posted_date' => ['nullable', 'date'],
            'close_date' => ['nullable', 'date', 'after_or_equal:posted_date'],
        ];
    }
}

