<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Collection;

abstract class BaseRepository
{
    protected const CACHE_PREFIX = 'repo:';
    protected const CACHE_TTL = 3600;
    protected const MODEL_CLASS = '';

    protected function cacheKey(string $suffix): string
    {
        return static::CACHE_PREFIX . static::MODEL_CLASS . ':' . $suffix;
    }

    protected function remember(string $key, \Closure $callback, ?int $ttl = null)
    {
        return Cache::remember($this->cacheKey($key), $ttl ?? static::CACHE_TTL, $callback);
    }

    protected function forget(string $key): void
    {
        Cache::forget($this->cacheKey($key));
    }

    protected function clearCacheByPrefix(): void
    {
        $prefix = $this->cacheKey('');
        Cache::forget($prefix . '*');
    }

    protected function safeDatabaseCall(\Closure $dbCallback, \Closure $fallbackCallback)
    {
        try {
            return $dbCallback();
        } catch (\Exception $e) {
            report($e);
            return $fallbackCallback();
        }
    }

    protected function configFallback(string $configKey, $default = null)
    {
        return Config::get($configKey, $default);
    }

    abstract public function clearCache(): void;
}
