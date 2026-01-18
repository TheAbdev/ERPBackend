<?php

namespace App\Modules\CRM\Models;

use App\Core\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'parent_id',
        'noteable_type',
        'noteable_id',
        'body',
        'created_by',
    ];

    /**
     * Get the parent noteable model.
     *
     * @return MorphTo
     */
    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the tenant that owns the note.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Get the user who created the note.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the users mentioned in the note.
     *
     * @return BelongsToMany
     */
    public function mentions(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\User::class, 'note_mentions')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(NoteAttachment::class);
    }

    /**
     * Get the parent note (if this is a reply).
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Note::class, 'parent_id');
    }

    /**
     * Get all replies to this note.
     *
     * @return HasMany
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Note::class, 'parent_id')->orderBy('created_at', 'asc');
    }
}

