<?php

namespace App\Modules\CRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkflowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('workflow'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'event' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['lead.created', 'deal.status_changed', 'activity.overdue']),
            ],
            'conditions' => ['sometimes', 'nullable', 'array'],
            'conditions.*.type' => ['required_with:conditions', 'string'],
            'conditions.*.field' => ['nullable', 'string'],
            'conditions.*.operator' => ['nullable', 'string'],
            'conditions.*.value' => ['nullable'],
            'actions' => ['sometimes', 'required', 'array', 'min:1'],
            'actions.*.type' => ['required', 'string', Rule::in(['create_activity', 'update_deal_status', 'assign_user', 'send_notification'])],
            'is_active' => ['sometimes', 'boolean'],
            'priority' => ['sometimes', 'integer', 'min:0', 'max:100'],
        ];
    }
}

