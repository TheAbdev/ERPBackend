<?php

namespace App\Modules\ERP\Models;

use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\ModelChangeTracker;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemHealth extends Model
{
    use BelongsToTenant, HasFactory, ModelChangeTracker;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'erp_system_health';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'cpu_usage',
        'memory_usage',
        'disk_usage',
        'active_connections',
        'queue_size',
        'metrics',
        'status',
        'last_checked_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cpu_usage' => 'decimal:2',
            'memory_usage' => 'decimal:2',
            'disk_usage' => 'decimal:2',
            'metrics' => 'array',
            'last_checked_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Check if system is healthy.
     *
     * @return bool
     */
    public function isHealthy(): bool
    {
        return $this->status === 'healthy';
    }

    /**
     * Check if system is in warning state.
     *
     * @return bool
     */
    public function isWarning(): bool
    {
        return $this->status === 'warning';
    }

    /**
     * Check if system is critical.
     *
     * @return bool
     */
    public function isCritical(): bool
    {
        return $this->status === 'critical';
    }
}

