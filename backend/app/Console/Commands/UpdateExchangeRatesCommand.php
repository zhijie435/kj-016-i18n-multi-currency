<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\UpdateExchangeRatesJob;
use App\Services\CurrencyService;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Config;

class UpdateExchangeRatesCommand extends Command
{
    protected $signature = 'exchange-rates:update 
                            {--source= : 汇率数据源 (manual, api)}
                            {--base= : 基准货币代码}
                            {--target=* : 目标货币代码列表}
                            {--queue : 是否使用队列异步执行}
                            {--force : 强制执行更新}';

    protected $description = '更新货币汇率';

    public function handle(
        CurrencyService $currencyService,
        ExchangeRateService $exchangeRateService
    ): int {
        $source = $this->option('source') ?? Config::get('app.exchange_rate.api_source', 'manual');
        $baseCurrency = $this->option('base') ?? Config::get('app.default_currency', 'CNY');
        $targetCurrencies = $this->option('target') ?: null;
        $useQueue = $this->option('queue');
        $force = $this->option('force');

        $this->info('Starting exchange rate update...');
        $this->line("Source: {$source}");
        $this->line("Base Currency: {$baseCurrency}");
        $this->line("Target Currencies: " . ($targetCurrencies ? implode(', ', $targetCurrencies) : 'All available'));

        if ($useQueue) {
            UpdateExchangeRatesJob::dispatch($source, $baseCurrency, $targetCurrencies);
            $this->info('Exchange rate update job dispatched to queue.');
            return 0;
        }

        try {
            UpdateExchangeRatesJob::dispatchSync($source, $baseCurrency, $targetCurrencies);

            $rates = $exchangeRateService->getActive();

            $this->info('Exchange rates updated successfully!');
            $this->table(
                ['From', 'To', 'Rate', 'Date', 'Source'],
                $rates->map(function ($rate) {
                    return [
                        $rate->from_currency_code,
                        $rate->to_currency_code,
                        number_format($rate->rate, 8),
                        $rate->effective_date,
                        $rate->source,
                    ];
                })->toArray()
            );

            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to update exchange rates: ' . $e->getMessage());
            return 1;
        }
    }
}
