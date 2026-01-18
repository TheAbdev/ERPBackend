<?php

namespace App\Modules\ERP\Models;

use App\Core\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReorderRule extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'warehouse_id',
        'reorder_point',
        'reorder_quantity',
        'maximum_stock',
        'is_active',
        'supplier_id',
    ];

    protected function casts(): array
    {
        return [
            'reorder_point' => 'decimal:4',
            'reorder_quantity' => 'decimal:4',
            'maximum_stock' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\CRM\Models\Account::class, 'supplier_id');
    }
}

