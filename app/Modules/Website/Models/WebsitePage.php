<?php

namespace App\Modules\Website\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsitePage extends WebsiteBaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'website_pages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'site_id',
        'title',
        'slug',
        'page_type',
        'status',
        'content',
        'published_content',
        'sort_order',
        'meta',
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
            'published_content' => 'array',
            'meta' => 'array',
        ];
    }

    /**
     * Site relationship.
     *
     * @return BelongsTo
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(WebsiteSite::class, 'site_id');
    }
}
