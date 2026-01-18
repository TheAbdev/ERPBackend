<?php

namespace App\Modules\ERP\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Modules\ERP\Models\JournalEntry;

class UpdateJournalEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update',JournalEntry::class);
       // return $this->user()->can('erp.accounting.journals.update');
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
                'nullable',
                Rule::exists('fiscal_years', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'fiscal_period_id' => [
                'nullable',
                Rule::exists('fiscal_periods', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'entry_date' => ['sometimes', 'required', 'date'],
            'reference_type' => ['nullable', 'string'],
            'reference_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'lines' => ['sometimes', 'required', 'array', 'min:2'],
            'lines.*.account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'lines.*.currency_id' => [
                'nullable',
                Rule::exists('currencies', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.debit_amount' => ['nullable', 'numeric', 'min:0'], // Support frontend format
            'lines.*.credit_amount' => ['nullable', 'numeric', 'min:0'], // Support frontend format
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.line_number' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $tenantId = $this->user()->tenant_id;

        // Convert debit_amount/credit_amount to debit/credit for backward compatibility
        $processedLines = [];
        if ($this->has('lines')) {
            $lines = $this->input('lines', []);
            foreach ($lines as $line) {
                $processedLine = $line;
                // Convert debit_amount to debit if debit_amount exists
                if (isset($line['debit_amount']) && !isset($line['debit'])) {
                    $processedLine['debit'] = $line['debit_amount'];
                }
                // Convert credit_amount to credit if credit_amount exists
                if (isset($line['credit_amount']) && !isset($line['credit'])) {
                    $processedLine['credit'] = $line['credit_amount'];
                }
                // Set default currency_id if not provided (use base currency)
                if (!isset($processedLine['currency_id'])) {
                    $baseCurrency = \App\Modules\ERP\Models\Currency::where('tenant_id', $tenantId)
                        ->where('is_base_currency', true)
                        ->first();
                    if ($baseCurrency) {
                        $processedLine['currency_id'] = $baseCurrency->id;
                    }
                }
                // Ensure at least one of debit or credit is set
                if (!isset($processedLine['debit']) && !isset($processedLine['credit'])) {
                    $processedLine['debit'] = 0;
                    $processedLine['credit'] = 0;
                } elseif (!isset($processedLine['debit'])) {
                    $processedLine['debit'] = 0;
                } elseif (!isset($processedLine['credit'])) {
                    $processedLine['credit'] = 0;
                }
                $processedLines[] = $processedLine;
            }
        }

        // Get current fiscal year and period if not provided
        $fiscalYearId = $this->input('fiscal_year_id');
        $fiscalPeriodId = $this->input('fiscal_period_id');

        if (!$fiscalYearId && $this->has('entry_date')) {
            // Try to get current fiscal year based on entry_date
            $entryDate = $this->input('entry_date') ? \Carbon\Carbon::parse($this->input('entry_date')) : now();
            $fiscalYear = \App\Modules\ERP\Models\FiscalYear::where('tenant_id', $tenantId)
                ->where('start_date', '<=', $entryDate)
                ->where('end_date', '>=', $entryDate)
                ->first();
            if ($fiscalYear) {
                $fiscalYearId = $fiscalYear->id;
            }
        }

        if (!$fiscalPeriodId && $fiscalYearId && $this->has('entry_date')) {
            // Try to get current fiscal period based on entry_date
            $entryDate = $this->input('entry_date') ? \Carbon\Carbon::parse($this->input('entry_date')) : now();
            $fiscalPeriod = \App\Modules\ERP\Models\FiscalPeriod::where('tenant_id', $tenantId)
                ->where('fiscal_year_id', $fiscalYearId)
                ->where('start_date', '<=', $entryDate)
                ->where('end_date', '>=', $entryDate)
                ->first();
            if ($fiscalPeriod) {
                $fiscalPeriodId = $fiscalPeriod->id;
            }
        }

        $mergeData = [];
        if (!empty($processedLines)) {
            $mergeData['lines'] = $processedLines;
        }
        if ($fiscalYearId !== null) {
            $mergeData['fiscal_year_id'] = $fiscalYearId;
        }
        if ($fiscalPeriodId !== null) {
            $mergeData['fiscal_period_id'] = $fiscalPeriodId;
        }

        if (!empty($mergeData)) {
            $this->merge($mergeData);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('lines')) {
                $lines = $this->input('lines', []);
                $totalDebits = 0;
                $totalCredits = 0;

                foreach ($lines as $line) {
                    // Support both debit/credit and debit_amount/credit_amount
                    $debit = $line['debit'] ?? $line['debit_amount'] ?? 0;
                    $credit = $line['credit'] ?? $line['credit_amount'] ?? 0;
                    $totalDebits += (float) $debit;
                    $totalCredits += (float) $credit;
                }

                if (abs($totalDebits - $totalCredits) > 0.01) {
                    $validator->errors()->add('lines', 'Journal entry must be balanced. Total debits must equal total credits.');
                }

                // Validate that each line has at least one of debit or credit > 0
                foreach ($lines as $index => $line) {
                    $debit = $line['debit'] ?? $line['debit_amount'] ?? 0;
                    $credit = $line['credit'] ?? $line['credit_amount'] ?? 0;
                    if ($debit == 0 && $credit == 0) {
                        $validator->errors()->add("lines.{$index}", 'Each line must have either a debit or credit amount.');
                    }
                    if ($debit > 0 && $credit > 0) {
                        $validator->errors()->add("lines.{$index}", 'Each line cannot have both debit and credit amounts.');
                    }
                }
            }
        });
    }
}

