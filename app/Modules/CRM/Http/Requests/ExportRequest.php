<?php

namespace App\Modules\CRM\Http\Requests;

use App\Modules\CRM\Models\ExportLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // User is authenticated, allow export
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'export_type' => ['required', 'string', Rule::in(['leads', 'contacts', 'accounts', 'activities', 'deals'])],
            'format' => ['nullable', 'string', Rule::in(['csv'])],
            'filters' => ['nullable', 'array'],
            'filters.date_from' => ['nullable', 'date'],
            'filters.date_to' => ['nullable', 'date', 'after_or_equal:filters.date_from'],
            'filters.user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('tenant_id', $this->user()->tenant_id)),
            ],
            'filters.status' => ['nullable', 'string'],
            'filters.pipeline_id' => [
                'nullable',
                'integer',
                Rule::exists('pipelines', 'id')->where(fn ($query) => $query->where('tenant_id', $this->user()->tenant_id)),
            ],
            'filters.type' => ['nullable', 'string'],
        ];
    }
}

