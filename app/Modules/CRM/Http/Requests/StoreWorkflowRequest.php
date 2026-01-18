<?php

namespace App\Modules\CRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkflowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Modules\CRM\Models\Workflow::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'event' => [
                'required',
                'string',
                Rule::in(['lead.created', 'deal.status_changed', 'activity.overdue']),
            ],
            'conditions' => ['nullable', 'array'],
            'conditions.*.type' => ['required_with:conditions', 'string'],
            'conditions.*.field' => ['nullable', 'string'],
            'conditions.*.operator' => ['nullable', 'string'],
            'conditions.*.value' => ['nullable'],
            'actions' => ['required', 'array', 'min:1'],
            'actions.*.type' => ['required', 'string', Rule::in(['create_activity', 'update_deal_status', 'assign_user', 'send_notification'])],
            'is_active' => ['boolean'],
            'priority' => ['integer', 'min:0', 'max:100'],
        ];
    }
}

