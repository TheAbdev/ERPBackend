<?php

namespace App\Modules\ECommerce\Models;

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
        'source_template',
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
     * Get the pages for this theme.
     *
     * @return HasMany
     */
    public function pages(): HasMany
    {
        return $this->hasMany(ThemePage::class);
    }

    /**
     * Get a specific page by type.
     *
     * @param string $pageType
     * @return ThemePage|null
     */
    public function getPage(string $pageType): ?ThemePage
    {
        return $this->pages()->where('page_type', $pageType)->first();
    }
}
