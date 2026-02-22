<?php

namespace App\Modules\Website\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebsiteSite extends WebsiteBaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'website_sites';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'domain',
        'status',
        'template_id',
        'settings',
        'published_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Template relationship.
     *
     * @return BelongsTo
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(WebsiteTemplate::class, 'template_id');
    }

    /**
     * Pages relationship.
     *
     * @return HasMany
     */
    public function pages(): HasMany
    {
        return $this->hasMany(WebsitePage::class, 'site_id');
    }
}
