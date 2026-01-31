<?php

namespace App\Modules\ECommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ThemePage Model
 * 
 * Represents a page within a theme (home, products, cart, etc.)
 * Each theme can have multiple pages, one for each page type.
 */
class ThemePage extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecommerce_theme_pages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'theme_id',
        'page_type',
        'title',
        'content',
        'draft_content',
        'is_published',
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
            'content' => 'array',
            'draft_content' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Available page types
     */
    public const PAGE_TYPES = [
        'home',
        'products',
        'product',
        'cart',
        'checkout',
        'account',
    ];

    /**
     * Get the theme that owns this page.
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    /**
     * Get the content to display (published or draft).
     * 
     * @param bool $preferDraft Whether to prefer draft content
     * @return array|null
     */
    public function getDisplayContent(bool $preferDraft = false): ?array
    {
        if ($preferDraft && $this->draft_content) {
            return $this->draft_content;
        }
        return $this->content;
    }

    /**
     * Publish the draft content.
     */
    public function publish(): void
    {
        if ($this->draft_content) {
            $this->content = $this->draft_content;
        }
        $this->is_published = true;
        $this->published_at = now();
        $this->save();
    }

    /**
     * Save content as draft.
     * 
     * @param array $content
     */
    public function saveDraft(array $content): void
    {
        $this->draft_content = $content;
        $this->save();
    }
}

