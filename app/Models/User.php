<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'is_active',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant that owns the user.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Get the roles for the user.
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(\App\Core\Models\Role::class, 'user_role')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    /**
     * Check if user has a specific role.
     *
     * @param  string|int  $role
     * @return bool
     */
    public function hasRole($role): bool
    {
        if (is_string($role)) {
            return $this->roles()
                ->where('slug', $role)
                ->wherePivot('tenant_id', $this->tenant_id)
                ->exists();
        }

        return $this->roles()
            ->where('roles.id', $role)
            ->wherePivot('tenant_id', $this->tenant_id)
            ->exists();
    }

    /**
     * Check if user has any of the given roles.
     *
     * @param  array  $roles
     * @return bool
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()
            ->whereIn('slug', $roles)
            ->wherePivot('tenant_id', $this->tenant_id)
            ->exists();
    }

    /**
     * Check if user has a specific permission.
     *
     * @param  string  $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        return $this->roles()
            ->wherePivot('tenant_id', $this->tenant_id)
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('slug', $permission);
            })
            ->exists();
    }

    /**
     * Check if user has any of the given permissions.
     *
     * @param  array  $permissions
     * @return bool
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->roles()
            ->wherePivot('tenant_id', $this->tenant_id)
            ->whereHas('permissions', function ($query) use ($permissions) {
                $query->whereIn('slug', $permissions);
            })
            ->exists();
    }

    /**
     * Check if user is tenant owner.
     *
     * @return bool
     */
    public function isTenantOwner(): bool
    {
        // Check if user has super_admin role
        if ($this->hasRole('super_admin')) {
            return true;
        }

        // Check if user is the owner of their tenant
        if ($this->tenant_id) {
            // Load tenant if not already loaded
            if (!$this->relationLoaded('tenant')) {
                $this->load('tenant');
            }

            if ($this->tenant && $this->tenant->owner_user_id === $this->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all permissions for the user (through roles).
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPermissions()
    {
        return $this->roles()
            ->wherePivot('tenant_id', $this->tenant_id)
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id');
    }

    /**
     * Get the notifications for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function notifications()
    {
        return $this->morphMany(\App\Core\Models\Notification::class, 'notifiable')
            ->where('tenant_id', $this->tenant_id)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get the unread notifications for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function unreadNotifications()
    {
        return $this->notifications()->whereNull('read_at');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(\App\Core\Models\Team::class, 'team_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get the leads assigned to the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function leads()
    {
        return $this->hasMany(\App\Modules\CRM\Models\Lead::class, 'assigned_to')
            ->where('tenant_id', $this->tenant_id);
    }

    /**
     * Get the contacts created by the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contacts()
    {
        return $this->hasMany(\App\Modules\CRM\Models\Contact::class, 'created_by')
            ->where('tenant_id', $this->tenant_id);
    }

    /**
     * Get the deals assigned to the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function deals()
    {
        return $this->hasMany(\App\Modules\CRM\Models\Deal::class, 'assigned_to')
            ->where('tenant_id', $this->tenant_id);
    }

    /**
     * Get the login history for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function loginHistory()
    {
        return $this->hasMany(\App\Core\Models\UserLoginHistory::class)
            ->where('tenant_id', $this->tenant_id)
            ->orderBy('logged_in_at', 'desc');
    }

    /**
     * Enable two-factor authentication for the user.
     */
    public function enableTwoFactorAuth(): void
    {
        $google2fa = app('pragmarx.google2fa');
        $secret = $google2fa->generateSecretKey();

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();

        $this->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_enabled' => true,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => null, // Will be set after verification
        ]);
    }

    /**
     * Generate recovery codes for 2FA.
     *
     * @param  int  $count
     * @return array
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(4)));
        }
        return $codes;
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disableTwoFactorAuth(): void
    {
        $this->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    /**
     * Verify two-factor authentication code.
     */
    public function verifyTwoFactorCode(string $code): bool
    {
        if (! $this->two_factor_enabled || ! $this->two_factor_secret) {
            return false;
        }

        // Check if it's a recovery code
        if ($this->useRecoveryCode($code)) {
            return true;
        }

        $google2fa = app('pragmarx.google2fa');
        $secret = decrypt($this->two_factor_secret);

        return $google2fa->verifyKey($secret, $code);
    }

    /**
     * Use a recovery code if valid.
     *
     * @param  string  $code
     * @return bool
     */
    public function useRecoveryCode(string $code): bool
    {
        $recoveryCodes = $this->two_factor_recovery_codes ?? [];

        if (empty($recoveryCodes)) {
            return false;
        }

        $index = array_search($code, $recoveryCodes);

        if ($index === false) {
            return false;
        }

        // Remove used recovery code
        unset($recoveryCodes[$index]);
        $recoveryCodes = array_values($recoveryCodes); // Re-index array

        $this->update([
            'two_factor_recovery_codes' => $recoveryCodes,
        ]);

        return true;
    }

    /**
     * Get recovery codes (only when first generated).
     *
     * @return array
     */
    public function getRecoveryCodes(): array
    {
        return $this->two_factor_recovery_codes ?? [];
    }

    /**
     * Generate QR code for two-factor authentication.
     */
    public function getTwoFactorQrCode(): string
    {
        if (! $this->two_factor_secret) {
            $this->enableTwoFactorAuth();
        }

        $google2fa = app('pragmarx.google2fa');
        $secret = decrypt($this->two_factor_secret);

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $this->email,
            $secret
        );

        return $qrCodeUrl;
    }
}
