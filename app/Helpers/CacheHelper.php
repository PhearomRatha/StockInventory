<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

class CacheHelper
{
    /**
     * Get cached data or execute callback
     */
    public static function remember($key, $ttl, $callback)
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Clear specific cache key
     */
    public static function forget($key)
    {
        return Cache::forget($key);
    }

    /**
     * Clear all cache
     */
    public static function clear()
    {
        return Cache::flush();
    }

    /**
     * Generate cache key for products
     */
    public static function productsKey()
    {
        return 'products_list';
    }

    /**
     * Generate cache key for product totals
     */
    public static function productsTotalKey()
    {
        return 'products_total';
    }

    /**
     * Generate cache key for stock status
     */
    public static function productsStockKey()
    {
        return 'products_stock_status';
    }

    /**
     * Generate cache key for reports
     */
    public static function reportKey($type, $params = [])
    {
        return 'report_' . $type . '_' . md5(serialize($params));
    }
}