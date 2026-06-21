<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Services\CurrencyService;
use App\Models\CurrencyExchangeRate;

class UpdateExchangeRatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $timeout = 120;

    protected ?string $source;
    protected ?string $baseCurrency;
    protected ?array $targetCurrencies;

    public function __construct(?string $source = null, ?string $baseCurrency = null, ?array $targetCurrencies = null)
    {
        $this->source = $source ?? Config::get('app.exchange_rate.api_source', 'manual');
        $this->baseCurrency = $baseCurrency ?? Config::get('app.default_currency', 'CNY');
        $this->targetCurrencies = $targetCurrencies;
        $this->onQueue('exchange_rates');
    }

    public function handle(CurrencyService $currencyService): void
    {
        $apiEnabled = Config::get('app.exchange_rate.api_enabled', false);

        if (!$apiEnabled) {
            Log::info('Exchange rate API is disabled, skipping automatic update');
            return;
        }

        $availableCurrencies = $currencyService->getAvailableCodes();
        $targets = $this->targetCurrencies ?? $availableCurrencies;

        Log::info('Starting exchange rate update', [
            'source' => $this->source,
            'base_currency' => $this->baseCurrency,
            'target_currencies' => $targets,
        ]);

        try {
            $rates = $this->fetchRates($this->baseCurrency, $targets);

            if (empty($rates)) {
                Log::warning('No exchange rates fetched');
                return;
            }

            DB::transaction(function () use ($rates) {
                $date = now()->toDateString();

                foreach ($rates as $toCode => $rateValue) {
                    if ($this->baseCurrency === $toCode) {
                        continue;
                    }

                    CurrencyExchangeRate::where('from_currency_code', $this->baseCurrency)
                        ->where('to_currency_code', $toCode)
                        ->where('is_active', true)
                        ->update(['is_active' => false]);

                    CurrencyExchangeRate::create([
                        'from_currency_code' => $this->baseCurrency,
                        'to_currency_code' => $toCode,
                        'rate' => $rateValue,
                        'effective_date' => $date,
                        'source' => $this->source,
                        'is_active' => true,
                    ]);

                    $reverseRate = 1 / $rateValue;

                    CurrencyExchangeRate::where('from_currency_code', $toCode)
                        ->where('to_currency_code', $this->baseCurrency)
                        ->where('is_active', true)
                        ->update(['is_active' => false]);

                    CurrencyExchangeRate::create([
                        'from_currency_code' => $toCode,
                        'to_currency_code' => $this->baseCurrency,
                        'rate' => $reverseRate,
                        'effective_date' => $date,
                        'source' => $this->source,
                        'is_active' => true,
                    ]);
                }
            });

            Log::info('Exchange rates updated successfully', ['count' => count($rates)]);
        } catch (\Exception $e) {
            Log::error('Failed to update exchange rates', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function fetchRates(string $base, array $targets): array
    {
        $apiUrl = Config::get('app.exchange_rate.api_url', '');
        $apiKey = Config::get('app.exchange_rate.api_key', '');

        if (empty($apiUrl)) {
            return $this->fetchMockRates($base, $targets);
        }

        try {
            $url = str_replace(
                ['{base}', '{targets}', '{key}'],
                [$base, implode(',', $targets), $apiKey],
                $apiUrl
            );

            $response = Http::timeout(30)->get($url);

            if ($response->successful()) {
                return $this->parseApiResponse($response->json(), $base, $targets);
            }

            Log::warning('Exchange rate API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Exchange rate API request exception', ['error' => $e->getMessage()]);
        }

        return $this->fetchMockRates($base, $targets);
    }

    protected function parseApiResponse(array $data, string $base, array $targets): array
    {
        $rates = [];

        if (isset($data['rates']) && is_array($data['rates'])) {
            foreach ($targets as $target) {
                if (isset($data['rates'][$target])) {
                    $rates[$target] = (float) $data['rates'][$target];
                }
            }
        } elseif (isset($data['conversion_rates']) && is_array($data['conversion_rates'])) {
            foreach ($targets as $target) {
                if (isset($data['conversion_rates'][$target])) {
                    $rates[$target] = (float) $data['conversion_rates'][$target];
                }
            }
        }

        return $rates;
    }

    protected function fetchMockRates(string $base, array $targets): array
    {
        $baseRates = [
            'CNY' => ['USD' => 0.1389, 'EUR' => 0.1275, 'BRL' => 0.6944, 'RUB' => 13.8889],
            'USD' => ['CNY' => 7.2000, 'EUR' => 0.9180, 'BRL' => 5.0000, 'RUB' => 100.0000],
            'EUR' => ['CNY' => 7.8431, 'USD' => 1.0893, 'BRL' => 5.4466, 'RUB' => 108.9320],
            'BRL' => ['CNY' => 1.4400, 'USD' => 0.2000, 'EUR' => 0.1836, 'RUB' => 20.0000],
            'RUB' => ['CNY' => 0.0720, 'USD' => 0.0100, 'EUR' => 0.0092, 'BRL' => 0.0500],
        ];

        $rates = [];
        if (isset($baseRates[$base])) {
            foreach ($targets as $target) {
                if (isset($baseRates[$base][$target])) {
                    $rates[$target] = $baseRates[$base][$target];
                }
            }
        }

        return $rates;
    }
}
