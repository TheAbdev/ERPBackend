<?php

namespace App\Modules\HR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecruitmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'position_id' => [
                'nullable',
                Rule::exists('hr_positions', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'candidate_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'applied_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}

