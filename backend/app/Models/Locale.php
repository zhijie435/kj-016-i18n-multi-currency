<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Locale extends Model
{
    protected $fillable = [
        'code',
        'name',
        'native_name',
        'flag',
        'element_locale',
        'is_default',
        'is_enabled',
        'sort_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_enabled' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    public static function getAvailableLocales(): array
    {
        return static::enabled()->ordered()->get()->keyBy('code')->map(function ($locale) {
            return [
                'name' => $locale->name,
                'native' => $locale->native_name,
                'flag' => $locale->flag,
                'element_locale' => $locale->element_locale,
            ];
        })->toArray();
    }

    public static function getDefaultLocale(): string
    {
        $default = static::default()->first();
        return $default ? $default->code : 'zh_CN';
    }

    public static function getAvailableLocaleCodes(): array
    {
        return static::enabled()->pluck('code')->toArray();
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
