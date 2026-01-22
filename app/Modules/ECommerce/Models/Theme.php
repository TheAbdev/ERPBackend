<?php

namespace App\Modules\ECommerce\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends ECommerceBaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecommerce_themes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'is_active',
        'is_default',
        'config',
        'assets',
        'preview_image',
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
            'is_default' => 'boolean',
            'config' => 'array',
            'assets' => 'array',
        ];
    }

    /**
     * Get the tenant that owns the theme.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Get the stores using this theme.
     *
     * @return HasMany
     */
    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($theme) {
            // If this is set as default, unset other defaults for this tenant
            if ($theme->is_default) {
                static::where('tenant_id', $theme->tenant_id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });

        static::updating(function ($theme) {
            // If this is set as default, unset other defaults for this tenant
            if ($theme->is_default && !$theme->getOriginal('is_default')) {
                static::where('tenant_id', $theme->tenant_id)
                    ->where('id', '!=', $theme->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }
}







