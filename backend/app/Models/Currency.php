<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimals',
        'is_enabled',
        'sort_order',
    ];

    protected $casts = [
        'decimals' => 'integer',
        'is_enabled' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function exchangeRatesFrom(): HasMany
    {
        return $this->hasMany(CurrencyExchangeRate::class, 'from_currency_code', 'code');
    }

    public function exchangeRatesTo(): HasMany
    {
        return $this->hasMany(CurrencyExchangeRate::class, 'to_currency_code', 'code');
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    public static function getEnabledCurrencies(): array
    {
        return static::enabled()->ordered()->get()->keyBy('code')->toArray();
    }

    public static function getAvailableCurrencyCodes(): array
    {
        return static::enabled()->pluck('code')->toArray();
    }

    public function getInfoAttribute(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'decimals' => $this->decimals,
        ];
    }

    public function formatAmount(float $amount): string
    {
        $symbol = $this->symbol ?? '';
        $decimals = $this->decimals ?? 2;
        $formatted = number_format($amount, $decimals, '.', ',');
        return $symbol ? "{$symbol}{$formatted}" : $formatted;
    }
}
