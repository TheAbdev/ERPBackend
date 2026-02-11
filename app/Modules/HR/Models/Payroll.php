<?php

namespace App\Modules\HR\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends HrBaseModel
{
    protected $table = 'hr_payrolls';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'period_start',
        'period_end',
        'base_salary',
        'allowances',
        'deductions',
        'net_salary',
        'status',
        'expense_account_id',
        'payable_account_id',
        'journal_entry_id',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'base_salary' => 'decimal:2',
            'allowances' => 'decimal:2',
            'deductions' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\ERP\Models\Account::class, 'expense_account_id');
    }

    public function payableAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\ERP\Models\Account::class, 'payable_account_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\ERP\Models\JournalEntry::class, 'journal_entry_id');
    }
}

