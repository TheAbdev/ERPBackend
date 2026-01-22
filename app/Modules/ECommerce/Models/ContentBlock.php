<?php

namespace App\Modules\ECommerce\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentBlock extends ECommerceBaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecommerce_content_blocks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'store_id',
        'type',
        'name',
        'content',
        'settings',
        'is_reusable',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => 'array',
            'settings' => 'array',
            'is_reusable' => 'boolean',
        ];
    }

    /**
     * Get the tenant that owns the content block.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Get the store.
     *
     * @return BelongsTo
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}







