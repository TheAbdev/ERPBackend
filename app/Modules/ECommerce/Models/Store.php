<?php

namespace App\Modules\ECommerce\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends ECommerceBaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecommerce_stores';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'domain',
        'slug',
        'theme_id',
        'is_active',
        'settings',
        'description',
        'logo',
        'favicon',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    /**
     * Get the tenant that owns the store.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Get the theme used by this store.
     *
     * @return BelongsTo
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    /**
     * Get the product syncs for this store.
     *
     * @return HasMany
     */
    public function productSyncs(): HasMany
    {
        return $this->hasMany(ProductSync::class);
    }

    /**
     * Get the customers for this store.
     *
     * @return HasMany
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get the carts for this store.
     *
     * @return HasMany
     */
    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * Get the orders for this store.
     *
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the pages for this store.
     *
     * @return HasMany
     */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    /**
     * Get the content blocks for this store.
     *
     * @return HasMany
     */
    public function contentBlocks(): HasMany
    {
        return $this->hasMany(ContentBlock::class);
    }
}







