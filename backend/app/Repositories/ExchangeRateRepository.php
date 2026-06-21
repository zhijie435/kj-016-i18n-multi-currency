<?php

namespace App\Repositories;

use App\Models\Currency;
use App\Models\CurrencyExchangeRate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ExchangeRateRepository
{
    protected const CACHE_PREFIX = 'exchange_rates:';
    protected const CACHE_TTL = 3600;

    public function getAllCurrencies(): Collection
    {
        return Currency::ordered()->get();
    }

    public function getEnabledCurrencies(): Collection
    {
        return Currency::enabled()->ordered()->get();
    }

    public function getCurrencyByCode(string $code): ?Currency
    {
        return Currency::findByCode($code);
    }

    public function createCurrency(array $data): Currency
    {
        return Currency::create($data);
    }

    public function updateCurrency(Currency $currency, array $data): bool
    {
        return $currency->update($data);
    }

    public function deleteCurrency(Currency $currency): ?bool
    {
        $this->clearCache();
        return $currency->delete();
    }

    public function getAllRates(?string $fromCode = null, ?string $toCode = null, ?string $date = null): Collection
    {
        $query = CurrencyExchangeRate::with(['fromCurrency', 'toCurrency']);

        if ($fromCode) {
            $query->where('from_currency_code', $fromCode);
        }
        if ($toCode) {
            $query->where('to_currency_code', $toCode);
        }
        if ($date) {
            $query->forDate($date);
        }

        return $query->orderByDesc('effective_date')->orderByDesc('id')->get();
    }

    public function getActiveRates(?string $date = null): Collection
    {
        return CurrencyExchangeRate::active()
            ->with(['fromCurrency', 'toCurrency'])
            ->forDate($date)
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->get();
    }

    public function getLatestRate(string $fromCode, string $toCode, ?string $date = null): ?CurrencyExchangeRate
    {
        $cacheKey = self::CACHE_PREFIX . "latest:{$fromCode}:{$toCode}:" . ($date ?? 'today');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($fromCode, $toCode, $date) {
            return CurrencyExchangeRate::getLatestRate($fromCode, $toCode, $date);
        });
    }

    public function convert(float $amount, string $fromCode, string $toCode, ?string $date = null): ?float
    {
        $rate = $this->getLatestRate($fromCode, $toCode, $date);
        return $rate ? round($amount * $rate->rate, 8) : null;
    }

    public function convertWithDetail(float $amount, string $fromCode, string $toCode, ?string $date = null): array
    {
        $rate = $this->getLatestRate($fromCode, $toCode, $date);
        if (!$rate) {
            return [
                'success' => false,
                'message' => 'Exchange rate not found',
            ];
        }

        $converted = round($amount * $rate->rate, 8);
        $fromCurrency = $this->getCurrencyByCode($fromCode);
        $toCurrency = $this->getCurrencyByCode($toCode);

        $fromDecimals = $fromCurrency ? $fromCurrency->decimals : 2;
        $toDecimals = $toCurrency ? $toCurrency->decimals : 2;
        $toSymbol = $toCurrency ? $toCurrency->symbol : '';

        return [
            'success' => true,
            'amount' => $amount,
            'from_currency' => $fromCode,
            'to_currency' => $toCode,
            'rate' => $rate->rate,
            'converted_amount' => $converted,
            'formatted_from' => ($fromCurrency ? $fromCurrency->symbol : '') . number_format($amount, $fromDecimals, '.', ','),
            'formatted_to' => $toSymbol . number_format($converted, $toDecimals, '.', ','),
            'effective_date' => $rate->effective_date,
        ];
    }

    public function getExchangeRateMatrix(array $currencyCodes, ?string $date = null): array
    {
        $codes = $this->normalizeCurrencyCodes($currencyCodes);
        return CurrencyExchangeRate::getExchangeRateMatrix($codes, $date);
    }

    public function createRate(array $data): CurrencyExchangeRate
    {
        DB::beginTransaction();
        try {
            if (isset($data['is_active']) && $data['is_active']) {
                CurrencyExchangeRate::where('from_currency_code', $data['from_currency_code'])
                    ->where('to_currency_code', $data['to_currency_code'])
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }

            $rate = CurrencyExchangeRate::create($data);
            $this->clearCache();

            DB::commit();
            return $rate;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateRate(CurrencyExchangeRate $rate, array $data): bool
    {
        DB::beginTransaction();
        try {
            $result = $rate->update($data);
            $this->clearCache();

            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteRate(CurrencyExchangeRate $rate): ?bool
    {
        $this->clearCache();
        return $rate->delete();
    }

    public function activateRate(CurrencyExchangeRate $rate): bool
    {
        DB::beginTransaction();
        try {
            CurrencyExchangeRate::where('from_currency_code', $rate->from_currency_code)
                ->where('to_currency_code', $rate->to_currency_code)
                ->where('id', '!=', $rate->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $rate->is_active = true;
            $result = $rate->save();
            $this->clearCache();

            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deactivateRate(CurrencyExchangeRate $rate): bool
    {
        $rate->is_active = false;
        $result = $rate->save();
        $this->clearCache();
        return $result;
    }

    public function getDefaultCurrencyInfo(): array
    {
        $defaultCode = config('app.default_currency', 'CNY');
        $currency = $this->getCurrencyByCode($defaultCode);

        if ($currency) {
            return $currency->info;
        }

        $configCurrencies = config('app.available_currencies', []);
        return $configCurrencies[$defaultCode] ?? [
            'code' => $defaultCode,
            'name' => '',
            'symbol' => '',
            'decimals' => 2,
        ];
    }

    protected function normalizeCurrencyCodes(array $codes): array
    {
        $available = Currency::getAvailableCurrencyCodes();
        if (empty($available)) {
            $available = array_keys(config('app.available_currencies', []));
        }
        return array_intersect($codes, $available);
    }

    public function clearCache(): void
    {
        $prefix = self::CACHE_PREFIX;
        Cache::forget($prefix . '*');
    }
}
