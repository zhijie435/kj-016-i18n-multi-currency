<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Services\LocaleService;
use App\Services\CurrencyService;
use App\Services\ExchangeRateService;

class WarmupCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 300;

    protected array $cacheTypes;
    protected ?string $locale;
    protected ?string $currency;

    public function __construct(array $cacheTypes = ['locales', 'currencies', 'exchange_rates'], ?string $locale = null, ?string $currency = null)
    {
        $this->cacheTypes = $cacheTypes;
        $this->locale = $locale;
        $this->currency = $currency;
        $this->onQueue('cache_warmup');
    }

    public function handle(
        LocaleService $localeService,
        CurrencyService $currencyService,
        ExchangeRateService $exchangeRateService
    ): void {
        Log::info('Starting cache warmup', [
            'cache_types' => $this->cacheTypes,
            'locale' => $this->locale,
            'currency' => $this->currency,
        ]);

        try {
            if (in_array('locales', $this->cacheTypes, true)) {
                $this->warmupLocaleCache($localeService);
            }

            if (in_array('currencies', $this->cacheTypes, true)) {
                $this->warmupCurrencyCache($currencyService);
            }

            if (in_array('exchange_rates', $this->cacheTypes, true)) {
                $this->warmupExchangeRateCache($exchangeRateService, $currencyService);
            }

            Log::info('Cache warmup completed successfully');
        } catch (\Exception $e) {
            Log::error('Cache warmup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function warmupLocaleCache(LocaleService $localeService): void
    {
        Log::info('Warming up locale cache');

        $localeService->getAll();
        $localeService->getEnabled();
        $localeService->getAvailableCodes();
        $localeService->getDefaultCode();

        $locales = $localeService->getEnabled();
        foreach ($locales as $locale) {
            $localeService->getByCode($locale->code);
        }

        if ($this->locale) {
            $localeService->getByCode($this->locale);
        }

        Log::info('Locale cache warmed up', ['count' => $locales->count()]);
    }

    protected function warmupCurrencyCache(CurrencyService $currencyService): void
    {
        Log::info('Warming up currency cache');

        $currencyService->getAll();
        $currencyService->getEnabled();
        $currencyService->getAvailableCodes();
        $currencyService->getDefaultInfo();

        $currencies = $currencyService->getEnabled();
        foreach ($currencies as $currency) {
            $currencyService->getByCode($currency->code);
        }

        if ($this->currency) {
            $currencyService->getByCode($this->currency);
        }

        Log::info('Currency cache warmed up', ['count' => $currencies->count()]);
    }

    protected function warmupExchangeRateCache(ExchangeRateService $exchangeRateService, CurrencyService $currencyService): void
    {
        Log::info('Warming up exchange rate cache');

        $exchangeRateService->getActive();

        $currencyCodes = $currencyService->getAvailableCodes();
        $date = now()->toDateString();

        foreach ($currencyCodes as $from) {
            foreach ($currencyCodes as $to) {
                if ($from !== $to) {
                    try {
                        $exchangeRateService->getLatest($from, $to, $date);
                    } catch (\Exception $e) {
                        Log::debug('Exchange rate not found during warmup', [
                            'from' => $from,
                            'to' => $to,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        if (!empty($currencyCodes)) {
            $exchangeRateService->getMatrix($currencyCodes, $date);
        }

        Log::info('Exchange rate cache warmed up', ['currency_count' => count($currencyCodes)]);
    }
}
