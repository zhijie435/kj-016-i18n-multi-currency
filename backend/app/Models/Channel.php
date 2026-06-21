<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Channel extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'locale_id',
        'is_enabled',
        'sort_order',
    ];

    protected $casts = [
        'locale_id' => 'integer',
        'is_enabled' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function locale(): BelongsTo
    {
        return $this->belongsTo(Locale::class);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    public function getLocaleCodeAttribute(): ?string
    {
        return $this->locale ? $this->locale->code : null;
    }

    public function setLocaleByCode(string $localeCode): bool
    {
        $locale = Locale::findByCode($localeCode);
        if ($locale) {
            $this->locale()->associate($locale);
            return true;
        }
        return false;
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    public static function getChannelLocaleCode(string $channelCode): ?string
    {
        $channel = static::with('locale')->where('code', $channelCode)->first();
        return $channel ? $channel->locale_code : null;
    }
}
