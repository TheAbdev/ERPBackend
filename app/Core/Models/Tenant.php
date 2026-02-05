<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'subdomain',
        'domain',
        'status',
        'owner_user_id',
        'settings',
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
        ];
    }

    /**
     * Check if tenant is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get a setting value.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set a setting value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
    }

    /**
     * Get the users for the tenant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(\App\Models\User::class);
    }

    /**
     * Get the roles for the tenant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    /**
     * Get the owner user of the tenant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner()
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_user_id');
    }

    /**
     * Get the leads for the tenant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function leads()
    {
        return $this->hasMany(\App\Modules\CRM\Models\Lead::class);
    }

    /**
     * Get the contacts for the tenant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contacts()
    {
        return $this->hasMany(\App\Modules\CRM\Models\Contact::class);
    }

    /**
     * Get the accounts for the tenant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accounts()
    {
        return $this->hasMany(\App\Modules\CRM\Models\Account::class);
    }

    /**
     * Get the deals for the tenant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function deals()
    {
        return $this->hasMany(\App\Modules\CRM\Models\Deal::class);
    }

    /**
     * Get the activities for the tenant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activities()
    {
        return $this->hasMany(\App\Modules\CRM\Models\Activity::class);
    }

    /**
     * Get the notes for the tenant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notes()
    {
        return $this->hasMany(\App\Modules\CRM\Models\Note::class);
    }

    /**
     * Get the sales orders for the tenant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function salesOrders()
    {
        return $this->hasMany(\App\Modules\ERP\Models\SalesOrder::class);
    }

    /**
     * Get the invoices for the tenant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invoices()
    {
        return $this->hasMany(\App\Modules\ERP\Models\SalesInvoice::class);
    }

    /**
     * Get the products for the tenant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(\App\Modules\ERP\Models\Product::class);
    }

    /**
     * Get the projects for the tenant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function projects()
    {
        return $this->hasMany(\App\Modules\ERP\Models\Project::class);
    }

    /**
     * Get tenant usage statistics.
     *
     * @return array
     */
    public function getUsageStats(): array
    {
        $lastActivity = $this->users()
            ->whereNotNull('updated_at')
            ->max('updated_at');

        return [
            'users_count' => $this->users()->count(),
            'roles_count' => $this->roles()->count(),
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'last_activity' => $lastActivity ? \Carbon\Carbon::parse($lastActivity)->toDateTimeString() : null,
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return \Database\Factories\TenantFactory::new();
    }
}

