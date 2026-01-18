<?php

namespace App\Modules\ERP\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJournalEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('erp.accounting.journals.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'fiscal_year_id' => [
                'required',
                Rule::exists('fiscal_years', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'fiscal_period_id' => [
                'required',
                Rule::exists('fiscal_periods', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'entry_date' => ['required', 'date'],
            'reference_type' => ['nullable', 'string'],
            'reference_id' => ['nullable', 'integer'],
            'description' => ['required', 'string'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'posted'])],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => [
                'required',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'lines.*.currency_id' => [
                'required',
                Rule::exists('currencies', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'lines.*.debit' => ['required', 'numeric', 'min:0'],
            'lines.*.credit' => ['required', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.line_number' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'tenant_id' => $this->user()->tenant_id,
            'status' => $this->input('status', 'draft'),
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $lines = $this->input('lines', []);
            $totalDebits = collect($lines)->sum('debit');
            $totalCredits = collect($lines)->sum('credit');

            if (abs($totalDebits - $totalCredits) > 0.01) {
                $validator->errors()->add('lines', 'Journal entry must be balanced. Total debits must equal total credits.');
            }
        });
    }
}

