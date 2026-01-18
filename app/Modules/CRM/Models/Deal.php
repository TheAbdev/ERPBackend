<?php

namespace App\Modules\CRM\Models;

use App\Core\Services\TenantContext;
use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\ModelChangeTracker;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Deal extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes, ModelChangeTracker;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'pipeline_id',
        'stage_id',
        'lead_id',
        'contact_id',
        'account_id',
        'title',
        'amount',
        'currency',
        'probability',
        'expected_close_date',
        'status',
        'created_by',
        'assigned_to',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'probability' => 'integer',
            'expected_close_date' => 'date',
        ];
    }

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::updating(function ($deal) {
            $original = $deal->getOriginal();

            // Log stage change
            if (isset($original['stage_id']) && $original['stage_id'] != $deal->stage_id) {
                $deal->logHistory('stage_changed', [
                    'from_stage_id' => $original['stage_id'],
                    'to_stage_id' => $deal->stage_id,
                ]);
            }

            // Log status change and dispatch event
            if (isset($original['status']) && $original['status'] != $deal->status) {
                $deal->logHistory($deal->status, [
                    'from_status' => $original['status'],
                    'to_status' => $deal->status,
                ]);

                // Dispatch notification event
                event(new \App\Events\DealStatusChanged($deal, $deal->status, $original['status']));
            }

            // Dispatch event for stage change
            if (isset($original['stage_id']) && $original['stage_id'] != $deal->stage_id) {
                event(new \App\Events\DealStatusChanged($deal, 'stage_changed', $original['status'] ?? null));
            }
        });

        static::created(function ($deal) {
            $deal->logHistory('created', [
                'title' => $deal->title,
                'amount' => $deal->amount,
            ]);
        });
    }

    /**
     * Get the tenant that owns the deal.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Get the pipeline for the deal.
     *
     * @return BelongsTo
     */
    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    /**
     * Get the stage for the deal.
     *
     * @return BelongsTo
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'stage_id');
    }

    /**
     * Get the lead associated with the deal.
     *
     * @return BelongsTo
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the contact associated with the deal.
     *
     * @return BelongsTo
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the account associated with the deal.
     *
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user who created the deal.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user assigned to the deal.
     *
     * @return BelongsTo
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    /**
     * Get the history for the deal.
     *
     * @return HasMany
     */
    public function histories(): HasMany
    {
        return $this->hasMany(DealHistory::class);
    }

    /**
     * Log a history entry.
     *
     * @param  string  $action
     * @param  array  $meta
     * @return void
     */
    public function logHistory(string $action, array $meta = []): void
    {
        $tenantId = app(TenantContext::class)->getTenantId();
        $userId = Auth::id();

        if ($tenantId && $userId) {
            $this->histories()->create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'action' => $action,
                'meta' => $meta,
            ]);
        }
    }
}

