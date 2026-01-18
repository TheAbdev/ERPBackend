<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Service for managing system settings.
 */
class SettingsService extends BaseService
{
    /**
     * Get setting value.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $cacheKey = "setting_{$this->getTenantId()}_{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = SystemSetting::where('tenant_id', $this->getTenantId())
                ->where('key', $key)
                ->first();

            if (!$setting) {
                return $default;
            }

            return $setting->getDecryptedValue();
        });
    }

    /**
     * Set setting value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  string|null  $module
     * @param  string  $type
     * @param  bool  $encrypt
     * @return \App\Modules\ERP\Models\SystemSetting
     */
    public function set(
        string $key,
        $value,
        ?string $module = null,
        string $type = 'string',
        bool $encrypt = false
    ): SystemSetting {
        $setting = SystemSetting::updateOrCreate(
            [
                'tenant_id' => $this->getTenantId(),
                'key' => $key,
            ],
            [
                'value' => $encrypt ? null : (string) $value,
                'module' => $module,
                'type' => $type,
            ]
        );

        if ($encrypt) {
            $setting->setEncryptedValue($value);
            $setting->save();
        }

        // Clear cache
        Cache::forget("setting_{$this->getTenantId()}_{$key}");

        return $setting;
    }

    /**
     * Update setting value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return \App\Modules\ERP\Models\SystemSetting
     */
    public function update(string $key, $value): SystemSetting
    {
        $setting = SystemSetting::where('tenant_id', $this->getTenantId())
            ->where('key', $key)
            ->firstOrFail();

        if ($setting->is_encrypted) {
            $setting->setEncryptedValue($value);
        } else {
            $setting->value = (string) $value;
        }

        $setting->save();

        // Clear cache
        Cache::forget("setting_{$this->getTenantId()}_{$key}");

        return $setting;
    }

    /**
     * Get all settings for module.
     *
     * @param  string|null  $module
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(?string $module = null)
    {
        $query = SystemSetting::where('tenant_id', $this->getTenantId());

        if ($module) {
            $query->where('module', $module);
        }

        return $query->get()->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->getDecryptedValue()];
        });
    }
}

