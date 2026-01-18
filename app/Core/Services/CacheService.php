<?php

namespace App\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheService
{
    /**
     * Get cache key with tenant prefix.
     *
     * @param  string  $key
     * @param  int|null  $tenantId
     * @return string
     */
    public function getKey(string $key, ?int $tenantId = null): string
    {
        $tenantId = $tenantId ?? app(TenantContext::class)->getTenantId();
        return "tenant:{$tenantId}:{$key}";
    }

    /**
     * Get cached value.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @param  int|null  $tenantId
     * @return mixed
     */
    public function get(string $key, mixed $default = null, ?int $tenantId = null): mixed
    {
        return Cache::get($this->getKey($key, $tenantId), $default);
    }

    /**
     * Set cached value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int|null  $seconds
     * @param  int|null  $tenantId
     * @return bool
     */
    public function put(string $key, mixed $value, ?int $seconds = null, ?int $tenantId = null): bool
    {
        $cacheKey = $this->getKey($key, $tenantId);

        if ($seconds === null) {
            return Cache::forever($cacheKey, $value);
        }

        return Cache::put($cacheKey, $value, $seconds);
    }

    /**
     * Remember cached value (get or compute and cache).
     *
     * @param  string  $key
     * @param  int|null  $seconds
     * @param  callable  $callback
     * @param  int|null  $tenantId
     * @return mixed
     */
    public function remember(string $key, ?int $seconds, callable $callback, ?int $tenantId = null): mixed
    {
        $cacheKey = $this->getKey($key, $tenantId);

        if ($seconds === null) {
            return Cache::rememberForever($cacheKey, $callback);
        }

        return Cache::remember($cacheKey, $seconds, $callback);
    }

    /**
     * Forget cached value.
     *
     * @param  string  $key
     * @param  int|null  $tenantId
     * @return bool
     */
    public function forget(string $key, ?int $tenantId = null): bool
    {
        return Cache::forget($this->getKey($key, $tenantId));
    }

    /**
     * Forget all cache for a tenant.
     *
     * @param  int|null  $tenantId
     * @return void
     */
    public function forgetTenant(?int $tenantId = null): void
    {
        $tenantId = $tenantId ?? app(TenantContext::class)->getTenantId();
        $pattern = "tenant:{$tenantId}:*";

        // Use Redis directly for pattern matching
        if (config('cache.default') === 'redis') {
            $keys = Redis::keys($pattern);
            if (! empty($keys)) {
                Redis::del($keys);
            }
        } else {
            // Fallback: clear entire cache (less efficient)
            Cache::flush();
        }
    }

    /**
     * Forget cache by pattern.
     *
     * @param  string  $pattern
     * @param  int|null  $tenantId
     * @return void
     */
    public function forgetPattern(string $pattern, ?int $tenantId = null): void
    {
        $tenantId = $tenantId ?? app(TenantContext::class)->getTenantId();
        $fullPattern = $this->getKey($pattern, $tenantId);

        if (config('cache.default') === 'redis') {
            $keys = Redis::keys($fullPattern);
            if (! empty($keys)) {
                Redis::del($keys);
            }
        } else {
            // Fallback: clear entire cache
            Cache::flush();
        }
    }

    /**
     * Increment cached value.
     *
     * @param  string  $key
     * @param  int  $value
     * @param  int|null  $tenantId
     * @return int
     */
    public function increment(string $key, int $value = 1, ?int $tenantId = null): int
    {
        return Cache::increment($this->getKey($key, $tenantId), $value);
    }

    /**
     * Decrement cached value.
     *
     * @param  string  $key
     * @param  int  $value
     * @param  int|null  $tenantId
     * @return int
     */
    public function decrement(string $key, int $value = 1, ?int $tenantId = null): int
    {
        return Cache::decrement($this->getKey($key, $tenantId), $value);
    }

    /**
     * Check if key exists in cache.
     *
     * @param  string  $key
     * @param  int|null  $tenantId
     * @return bool
     */
    public function has(string $key, ?int $tenantId = null): bool
    {
        return Cache::has($this->getKey($key, $tenantId));
    }
}

