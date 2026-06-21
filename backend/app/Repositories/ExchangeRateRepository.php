<?php

namespace App\Repositories;

use App\Models\CurrencyExchangeRate;
use App\Models\Currency;
use Illuminate\Support\Collection;

class ExchangeRateRepository extends BaseRepository
{
    protected const MODEL_CLASS = 'ExchangeRate';
    protected const CACHE_TTL = 1800;

    protected CurrencyRepository $currencyRepository;

    public function __construct(CurrencyRepository $currencyRepository)
    {
        $this->currencyRepository = $currencyRepository;
    }

    public function getAll(?string $fromCode = null, ?string $toCode = null, ?string $date = null): Collection
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

    public function getActive(?string $date = null): Collection
    {
        return CurrencyExchangeRate::active()
            ->with(['fromCurrency', 'toCurrency'])
            ->forDate($date)
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->get();
    }

    public function findById(int $id): ?CurrencyExchangeRate
    {
        return CurrencyExchangeRate::with(['fromCurrency', 'toCurrency'])->find($id);
    }

    public function getLatest(string $fromCode, string $toCode, ?string $date = null): ?CurrencyExchangeRate
    {
        $suffix = "latest:{$fromCode}:{$toCode}:" . ($date ?? 'today');
        return $this->remember($suffix, function () use ($fromCode, $toCode, $date) {
            return CurrencyExchangeRate::getLatestRate($fromCode, $toCode, $date);
        });
    }

    public function convert(float $amount, string $fromCode, string $toCode, ?string $date = null): ?float
    {
        $rate = $this->getLatest($fromCode, $toCode, $date);
        return $rate ? round($amount * $rate->rate, 8) : null;
    }

    public function convertWithDetail(float $amount, string $fromCode, string $toCode, ?string $date = null): array
    {
        $rate = $this->getLatest($fromCode, $toCode, $date);
        if (!$rate) {
            return [
                'success' => false,
                'message' => 'Exchange rate not found',
            ];
        }

        $converted = round($amount * $rate->rate, 8);
        $fromCurrency = $this->currencyRepository->findByCode($fromCode);
        $toCurrency = $this->currencyRepository->findByCode($toCode);

        $fromDecimals = $fromCurrency ? $fromCurrency->decimals : 2;
        $toDecimals = $toCurrency ? $toCurrency->decimals : 2;
        $fromSymbol = $fromCurrency ? $fromCurrency->symbol : '';
        $toSymbol = $toCurrency ? $toCurrency->symbol : '';

        return [
            'success' => true,
            'amount' => $amount,
            'from_currency' => $fromCode,
            'to_currency' => $toCode,
            'rate' => $rate->rate,
            'converted_amount' => $converted,
            'formatted_from' => $fromSymbol . number_format($amount, $fromDecimals, '.', ','),
            'formatted_to' => $toSymbol . number_format($converted, $toDecimals, '.', ','),
            'effective_date' => $rate->effective_date,
        ];
    }

    public function getMatrix(array $currencyCodes, ?string $date = null): array
    {
        $codes = $this->normalizeCodes($currencyCodes);
        return CurrencyExchangeRate::getExchangeRateMatrix($codes, $date);
    }

    public function create(array $data): CurrencyExchangeRate
    {
        if (isset($data['is_active']) && $data['is_active']) {
            CurrencyExchangeRate::where('from_currency_code', $data['from_currency_code'])
                ->where('to_currency_code', $data['to_currency_code'])
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $rate = CurrencyExchangeRate::create($data);
        $this->clearCache();
        return $rate;
    }

    public function update(CurrencyExchangeRate $rate, array $data): bool
    {
        $result = $rate->update($data);
        $this->clearCache();
        return $result;
    }

    public function delete(CurrencyExchangeRate $rate): ?bool
    {
        $this->clearCache();
        return $rate->delete();
    }

    public function activate(CurrencyExchangeRate $rate): bool
    {
        CurrencyExchangeRate::where('from_currency_code', $rate->from_currency_code)
            ->where('to_currency_code', $rate->to_currency_code)
            ->where('id', '!=', $rate->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $rate->is_active = true;
        $result = $rate->save();
        $this->clearCache();
        return $result;
    }

    public function deactivate(CurrencyExchangeRate $rate): bool
    {
        $rate->is_active = false;
        $result = $rate->save();
        $this->clearCache();
        return $result;
    }

    public function clearCache(): void
    {
        $this->clearCacheByPrefix();
    }

    protected function normalizeCodes(array $codes): array
    {
        $available = $this->currencyRepository->getAvailableCodes();
        if (empty($available)) {
            return $codes;
        }
        return array_values(array_intersect($codes, $available));
    }
}
