<?php

namespace App\Modules\ECommerce\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Page extends ECommerceBaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecommerce_pages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'store_id',
        'title',
        'slug',
        'content',
        'meta',
        'is_published',
        'sort_order',
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
            'meta' => 'array',
            'is_published' => 'boolean',
        ];
    }

    /**
     * Get the tenant that owns the page.
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







