<?php

namespace App\Modules\ERP\Models;

use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\ModelChangeTracker;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityFeed extends Model
{
    use BelongsToTenant, HasFactory, ModelChangeTracker;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'erp_activity_feed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'entity_type',
        'entity_id',
        'action',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * Get the tenant that owns the activity.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Get the user who performed the action.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get the entity that this activity is about.
     *
     * @return MorphTo
     */
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Format activity for display.
     *
     * @return string
     */
    public function formatForDisplay(): string
    {
        $userName = $this->user ? $this->user->name : 'System';
        $entityName = class_basename($this->entity_type);
        $action = ucfirst($this->action);

        return "{$userName} {$action} {$entityName}";
    }

    /**
     * Get recent activities for tenant.
     *
     * @param  int  $tenantId
     * @param  int  $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function recentForTenant(int $tenantId, int $limit = 50)
    {
        return static::where('tenant_id', $tenantId)
            ->with(['user', 'entity'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}

