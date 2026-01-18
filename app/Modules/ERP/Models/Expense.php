<?php

namespace App\Modules\ERP\Models;

use App\Core\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'expense_number',
        'expense_category_id',
        'account_id',
        'vendor_id',
        'currency_id',
        'payee_name',
        'description',
        'amount',
        'expense_date',
        'payment_method',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'receipt_path',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'expense_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\ERP\Models\Account::class, 'account_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\CRM\Models\Account::class, 'vendor_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}

