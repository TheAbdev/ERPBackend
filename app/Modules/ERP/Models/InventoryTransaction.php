<?php

namespace App\Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryTransaction extends ErpBaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'product_id',
        'product_variant_id',
        'batch_id',
        'transaction_type',
        'reference_type',
        'reference_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'unit_of_measure_id',
        'unit_of_measure',
        'base_quantity',
        'notes',
        'created_by',
        'transaction_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:4',
            'base_quantity' => 'decimal:4',
            'transaction_date' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($transaction) {
            // Calculate total cost
            if ($transaction->total_cost == 0 && $transaction->quantity != 0) {
                $transaction->total_cost = abs($transaction->quantity) * $transaction->unit_cost;
            }
        });
    }

    /**
     * Get the tenant that owns the transaction.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Get the warehouse.
     *
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the product.
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product variant.
     *
     * @return BelongsTo
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get the inventory batch.
     *
     * @return BelongsTo
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class);
    }

    /**
     * Get the unit of measure.
     *
     * @return BelongsTo
     */
    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    /**
     * Get the user who created the transaction.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the reference model (polymorphic).
     *
     * @return MorphTo
     */
    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }

    /**
     * Check if transaction is a receipt (increases stock).
     *
     * @return bool
     */
    public function isReceipt(): bool
    {
        return in_array($this->transaction_type, ['opening_balance', 'receipt', 'transfer']) && $this->quantity > 0;
    }

    /**
     * Check if transaction is an issue (decreases stock).
     *
     * @return bool
     */
    public function isIssue(): bool
    {
        return in_array($this->transaction_type, ['issue', 'transfer']) && $this->quantity < 0;
    }
}

