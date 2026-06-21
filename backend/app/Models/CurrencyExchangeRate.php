<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class CurrencyExchangeRate extends Model
{
    protected $fillable = [
        'from_currency_code',
        'to_currency_code',
        'rate',
        'effective_date',
        'source',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'float',
        'effective_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function fromCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'from_currency_code', 'code');
    }

    public function toCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'to_currency_code', 'code');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDate($query, ?string $date = null)
    {
        $date = $date ?? now()->toDateString();
        return $query->where(function ($q) use ($date) {
            $q->where('effective_date', '<=', $date)
                ->orWhereNull('effective_date');
        });
    }

    public static function getLatestRate(string $fromCode, string $toCode, ?string $date = null): ?self
    {
        if ($fromCode === $toCode) {
            $rate = new self();
            $rate->from_currency_code = $fromCode;
            $rate->to_currency_code = $toCode;
            $rate->rate = 1.0;
            return $rate;
        }

        return static::active()
            ->where('from_currency_code', $fromCode)
            ->where('to_currency_code', $toCode)
            ->forDate($date)
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->first();
    }

    public static function convert(float $amount, string $fromCode, string $toCode, ?string $date = null): ?float
    {
        $rate = static::getLatestRate($fromCode, $toCode, $date);
        return $rate ? $amount * $rate->rate : null;
    }

    public static function getExchangeRateMatrix(array $currencyCodes, ?string $date = null): array
    {
        $matrix = [];
        foreach ($currencyCodes as $from) {
            $matrix[$from] = [];
            foreach ($currencyCodes as $to) {
                $rate = static::getLatestRate($from, $to, $date);
                $matrix[$from][$to] = $rate ? $rate->rate : ($from === $to ? 1.0 : null);
            }
        }
        return $matrix;
    }
}
