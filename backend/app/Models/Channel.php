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
        'currency_code',
        'currency_symbol',
        'currency_decimals',
        'is_enabled',
        'sort_order',
    ];

    protected $casts = [
        'locale_id' => 'integer',
        'currency_decimals' => 'integer',
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

    public function getCurrencyNameAttribute(): ?string
    {
        $currencies = config('app.available_currencies', []);
        return $this->currency_code && isset($currencies[$this->currency_code])
            ? $currencies[$this->currency_code]['name']
            : null;
    }

    public function getCurrencyInfoAttribute(): array
    {
        return [
            'code' => $this->currency_code,
            'symbol' => $this->currency_symbol,
            'name' => $this->currency_name,
            'decimals' => $this->currency_decimals ?? 2,
        ];
    }

    public function formatAmount(float $amount): string
    {
        $symbol = $this->currency_symbol ?? '';
        $decimals = $this->currency_decimals ?? 2;
        $formatted = number_format($amount, $decimals, '.', ',');
        return $symbol ? "{$symbol}{$formatted}" : $formatted;
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

    public static function getChannelCurrency(string $channelCode): ?array
    {
        $channel = static::where('code', $channelCode)->first();
        return $channel ? $channel->currency_info : null;
    }
}
