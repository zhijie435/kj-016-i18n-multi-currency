<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\WarmupCacheJob;
use App\Services\LocaleService;
use App\Services\CurrencyService;
use App\Services\ExchangeRateService;

class WarmupCacheCommand extends Command
{
    protected $signature = 'cache:warmup
                            {--type=* : 缓存类型 (locales, currencies, exchange_rates)}
                            {--locale= : 预热指定语言缓存}
                            {--currency= : 预热指定货币缓存}
                            {--queue : 是否使用队列异步执行}';

    protected $description = '预热多语言多币种相关缓存';

    public function handle(
        LocaleService $localeService,
        CurrencyService $currencyService,
        ExchangeRateService $exchangeRateService
    ): int {
        $cacheTypes = $this->option('type') ?: ['locales', 'currencies', 'exchange_rates'];
        $locale = $this->option('locale');
        $currency = $this->option('currency');
        $useQueue = $this->option('queue');

        $this->info('Starting cache warmup...');
        $this->line("Cache types: " . implode(', ', $cacheTypes));
        if ($locale) {
            $this->line("Locale: {$locale}");
        }
        if ($currency) {
            $this->line("Currency: {$currency}");
        }

        if ($useQueue) {
            WarmupCacheJob::dispatch($cacheTypes, $locale, $currency);
            $this->info('Cache warmup job dispatched to queue.');
            return 0;
        }

        try {
            WarmupCacheJob::dispatchSync($cacheTypes, $locale, $currency);

            $this->info('Cache warmup completed successfully!');

            $this->line("\nCache Summary:");

            if (in_array('locales', $cacheTypes, true)) {
                $locales = $localeService->getEnabled();
                $this->line("- Locales: {$locales->count()} cached");
            }

            if (in_array('currencies', $cacheTypes, true)) {
                $currencies = $currencyService->getEnabled();
                $this->line("- Currencies: {$currencies->count()} cached");
            }

            if (in_array('exchange_rates', $cacheTypes, true)) {
                $rates = $exchangeRateService->getActive();
                $this->line("- Exchange Rates: {$rates->count()} cached");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Cache warmup failed: ' . $e->getMessage());
            return 1;
        }
    }
}
