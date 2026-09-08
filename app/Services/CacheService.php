<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    protected int $defaultTTL = 30; // 30 minutes default

    /**
     * Get value from cache
     */
    public function get(string $key)
    {
        return Cache::get($key);
    }

    /**
     * Store value in cache
     */
    public function put(string $key, $value, ?int $minutes = null): bool
    {
        $ttl = $minutes ?? $this->defaultTTL;
        return Cache::put($key, $value, now()->addMinutes($ttl));
    }

    /**
     * Remove value from cache
     */
    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Invalidate product cache
     */
    public function invalidateProductCache(int $tenantId, ?int $productId = null): void
    {
        if ($productId) {
            Cache::tags(["product_{$productId}"])->flush();
        }
        Cache::tags(["tenant_{$tenantId}_products"])->flush();
    }

    /**
     * Invalidate sales cache
     */
    public function invalidateSalesCache(int $tenantId, ?string $date = null): void
    {
        if ($date) {
            Cache::tags(["sales_{$tenantId}_{$date}"])->flush();
        }
        Cache::tags(["tenant_{$tenantId}_sales"])->flush();
    }

    /**
     * Invalidate reports cache
     */
    public function invalidateReportsCache(int $tenantId): void
    {
        Cache::tags(["tenant_{$tenantId}_reports"])->flush();
    }

    /**
     * Invalidate inventory cache
     */
    public function invalidateInventoryCache(int $tenantId): void
    {
        Cache::tags(["tenant_{$tenantId}_inventory"])->flush();
    }

    /**
     * Get or put value in cache
     */
    public function remember(string $key, callable $callback, ?int $minutes = null)
    {
        $ttl = $minutes ?? $this->defaultTTL;
        return Cache::remember($key, now()->addMinutes($ttl), $callback);
    }

    /**
     * Generate cache key for tenant search
     */
    public function getCacheKeyForSearch(int $tenantId, string $query, int $page = 1): string
    {
        return "search:tenant_{$tenantId}_" . md5($query) . "_{$page}";
    }

    /**
     * Generate cache key for sales report
     */
    public function getCacheKeyForSalesReport(int $tenantId, string $date): string
    {
        return "sales:tenant_{$tenantId}:date_{$date}";
    }

    /**
     * Flush all cache
     */
    public function flushAll(): bool
    {
        return Cache::flush();
    }
}
