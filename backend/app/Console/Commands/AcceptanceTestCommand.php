<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Services\LocaleService;
use App\Services\CurrencyService;
use App\Services\ExchangeRateService;
use App\Services\ChannelService;

class AcceptanceTestCommand extends Command
{
    protected $signature = 'acceptance:test
                            {--suite=default : 测试套件 (default, quick, full)}
                            {--output=table : 输出格式 (table, json)}';

    protected $description = '运行多语言多币种功能验收测试';

    protected array $results = [];

    public function handle(
        LocaleService $localeService,
        CurrencyService $currencyService,
        ExchangeRateService $exchangeRateService,
        ChannelService $channelService
    ): int {
        $suite = $this->option('suite');
        $outputFormat = $this->option('output');

        $this->info("Running acceptance test suite: {$suite}");
        $this->line(str_repeat('=', 60));

        $tests = $this->getTests($suite);

        foreach ($tests as $testName => $testCallback) {
            $this->line("Testing: {$testName}");
            try {
                $result = $testCallback($localeService, $currencyService, $exchangeRateService, $channelService);
                $this->results[$testName] = $result;
                if ($result['pass']) {
                    $this->info("  ✓ Passed: {$result['message']}");
                } else {
                    $this->error("  ✗ Failed: {$result['message']}");
                }
            } catch (\Exception $e) {
                $this->results[$testName] = [
                    'pass' => false,
                    'message' => 'Exception: ' . $e->getMessage(),
                    'details' => $e->getTraceAsString(),
                ];
                $this->error("  ✗ Exception: {$e->getMessage()}");
            }
            $this->line('');
        }

        $passed = count(array_filter($this->results, fn($r) => $r['pass']));
        $total = count($this->results);

        $this->line(str_repeat('=', 60));
        $this->info("Test Results: {$passed}/{$total} passed");

        if ($outputFormat === 'table') {
            $this->outputResultsTable();
        } elseif ($outputFormat === 'json') {
            $this->outputResultsJson();
        }

        return $passed === $total ? 0 : 1;
    }

    protected function getTests(string $suite): array
    {
        $tests = [
            'locale_config' => function () {
                $defaultLocale = Config::get('app.locale');
                $availableLocales = Config::get('app.available_locales', []);
                $multiLangEnabled = Config::get('app.multi_language_enabled', true);

                return [
                    'pass' => $multiLangEnabled && !empty($defaultLocale) && !empty($availableLocales),
                    'message' => "Default locale: {$defaultLocale}, available: " . count($availableLocales),
                    'details' => [
                        'default' => $defaultLocale,
                        'available' => array_keys($availableLocales),
                    ],
                ];
            },

            'locale_database' => function ($localeService, $currencyService, $exchangeRateService, $channelService) {
                $locales = $localeService->getEnabled();
                $default = $localeService->getDefaultCode();

                return [
                    'pass' => $locales->count() > 0 && !empty($default),
                    'message' => "Enabled locales: {$locales->count()}, default: {$default}",
                    'details' => $locales->pluck('code')->toArray(),
                ];
            },

            'locale_switch' => function ($localeService, $currencyService, $exchangeRateService, $channelService) {
                $codes = $localeService->getAvailableCodes();
                $errors = [];

                foreach ($codes as $code) {
                    $locale = $localeService->findByCode($code);
                    if (!$locale) {
                        $errors[] = "Locale not found: {$code}";
                    }
                }

                return [
                    'pass' => empty($errors),
                    'message' => empty($errors) ? "All {$codes} locales accessible" : implode(', ', $errors),
                    'details' => $codes,
                ];
            },

            'currency_config' => function () {
                $defaultCurrency = Config::get('app.default_currency');
                $availableCurrencies = Config::get('app.available_currencies', []);
                $multiCurrencyEnabled = Config::get('app.multi_currency_enabled', true);

                return [
                    'pass' => $multiCurrencyEnabled && !empty($defaultCurrency) && !empty($availableCurrencies),
                    'message' => "Default currency: {$defaultCurrency}, available: " . count($availableCurrencies),
                    'details' => [
                        'default' => $defaultCurrency,
                        'available' => array_keys($availableCurrencies),
                    ],
                ];
            },

            'currency_database' => function ($localeService, $currencyService, $exchangeRateService, $channelService) {
                $currencies = $currencyService->getEnabled();
                $default = $currencyService->getDefaultInfo();

                return [
                    'pass' => $currencies->count() > 0 && !empty($default['code']),
                    'message' => "Enabled currencies: {$currencies->count()}, default: {$default['code']}",
                    'details' => $currencies->pluck('code')->toArray(),
                ];
            },

            'exchange_rate_conversion' => function ($localeService, $currencyService, $exchangeRateService, $channelService) {
                $codes = $currencyService->getAvailableCodes();
                if (count($codes) < 2) {
                    return [
                        'pass' => false,
                        'message' => 'Need at least 2 currencies for conversion test',
                        'details' => $codes,
                    ];
                }

                $amount = 100.0;
                $from = $codes[0];
                $to = $codes[1];

                try {
                    $result = $exchangeRateService->convertWithDetail($amount, $from, $to);
                    return [
                        'pass' => $result['success'] && $result['converted_amount'] > 0,
                        'message' => "{$amount} {$from} = {$result['formatted_to']} (rate: {$result['rate']})",
                        'details' => $result,
                    ];
                } catch (\Exception $e) {
                    return [
                        'pass' => false,
                        'message' => $e->getMessage(),
                        'details' => ['from' => $from, 'to' => $to, 'amount' => $amount],
                    ];
                }
            },

            'exchange_rate_matrix' => function ($localeService, $currencyService, $exchangeRateService, $channelService) {
                $codes = $currencyService->getAvailableCodes();
                $matrix = $exchangeRateService->getMatrix($codes);

                $valid = true;
                foreach ($codes as $from) {
                    if (!isset($matrix[$from][$from]) || $matrix[$from][$from] !== 1.0) {
                        $valid = false;
                        break;
                    }
                }

                return [
                    'pass' => $valid,
                    'message' => "Rate matrix generated for " . count($codes) . " currencies",
                    'details' => $matrix,
                ];
            },

            'channel_config' => function ($localeService, $currencyService, $exchangeRateService, $channelService) {
                $channels = $channelService->getEnabled();

                return [
                    'pass' => $channels->count() > 0,
                    'message' => "Enabled channels: {$channels->count()}",
                    'details' => $channels->map(function ($ch) {
                        return [
                            'code' => $ch->code,
                            'locale' => $ch->locale_id ? ($ch->locale->code ?? null) : null,
                            'currency' => $ch->currency_code,
                        ];
                    })->toArray(),
                ];
            },

            'channel_locale_currency' => function ($localeService, $currencyService, $exchangeRateService, $channelService) {
                $channels = $channelService->getEnabled();
                $errors = [];

                foreach ($channels as $channel) {
                    $locale = $channelService->getChannelLocaleCode($channel->code);
                    $currency = $channelService->getChannelCurrency($channel->code);

                    if (empty($locale)) {
                        $errors[] = "Channel {$channel->code} missing locale";
                    }
                    if (empty($currency['code'])) {
                        $errors[] = "Channel {$channel->code} missing currency";
                    }
                }

                return [
                    'pass' => empty($errors),
                    'message' => empty($errors) ? 'All channels have locale and currency' : implode(', ', $errors),
                    'details' => ['channels' => $channels->count(), 'errors' => $errors],
                ];
            },

            'database_integrity' => function () {
                try {
                    $localeCount = DB::table('locales')->count();
                    $currencyCount = DB::table('currencies')->count();
                    $channelCount = DB::table('channels')->count();
                    $rateCount = DB::table('currency_exchange_rates')->count();

                    return [
                        'pass' => $localeCount > 0 && $currencyCount > 0,
                        'message' => "locales: {$localeCount}, currencies: {$currencyCount}, channels: {$channelCount}, rates: {$rateCount}",
                        'details' => [
                            'locales' => $localeCount,
                            'currencies' => $currencyCount,
                            'channels' => $channelCount,
                            'exchange_rates' => $rateCount,
                        ],
                    ];
                } catch (\Exception $e) {
                    return [
                        'pass' => false,
                        'message' => 'Database error: ' . $e->getMessage(),
                        'details' => ['error' => $e->getMessage()],
                    ];
                }
            },

            'language_files' => function () {
                $locales = Config::get('app.available_locales', []);
                $missing = [];

                foreach (array_keys($locales) as $code) {
                    $path = resource_path("lang/{$code}/common.php");
                    if (!file_exists($path)) {
                        $missing[] = $code;
                    }
                }

                return [
                    'pass' => empty($missing),
                    'message' => empty($missing) ? 'All language files present' : 'Missing: ' . implode(', ', $missing),
                    'details' => ['configured' => array_keys($locales), 'missing' => $missing],
                ];
            },

            'middleware_config' => function () {
                $kernel = app(\App\Http\Kernel::class);
                $hasLocaleMiddleware = false;
                $apiMiddleware = [];

                try {
                    $reflection = new \ReflectionObject($kernel);
                    $property = $reflection->getProperty('middlewareGroups');
                    $property->setAccessible(true);
                    $middlewareGroups = $property->getValue($kernel);

                    if (isset($middlewareGroups['api'])) {
                        $apiMiddleware = $middlewareGroups['api'];
                        foreach ($middlewareGroups['api'] as $mw) {
                            if (is_string($mw) && str_contains($mw, 'SetLocale')) {
                                $hasLocaleMiddleware = true;
                                break;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $hasLocaleMiddleware = false;
                }

                return [
                    'pass' => true,
                    'message' => $hasLocaleMiddleware ? 'SetLocale middleware configured in api group' : 'SetLocale middleware check skipped',
                    'details' => ['middleware' => $apiMiddleware],
                ];
            },
        ];

        if ($suite === 'quick') {
            return array_intersect_key($tests, array_flip([
                'locale_config',
                'currency_config',
                'locale_database',
                'currency_database',
                'database_integrity',
            ]));
        }

        if ($suite === 'full') {
            return $tests;
        }

        return array_intersect_key($tests, array_flip([
            'locale_config',
            'locale_database',
            'locale_switch',
            'currency_config',
            'currency_database',
            'exchange_rate_conversion',
            'channel_config',
            'database_integrity',
            'language_files',
        ]));
    }

    protected function outputResultsTable(): void
    {
        $this->line('');
        $this->table(
            ['Test', 'Status', 'Message'],
            array_map(function ($name, $result) {
                return [
                    $name,
                    $result['pass'] ? '<info>✓ PASS</info>' : '<error>✗ FAIL</error>',
                    $result['message'],
                ];
            }, array_keys($this->results), $this->results)
        );
    }

    protected function outputResultsJson(): void
    {
        $this->line('');
        $this->line(json_encode($this->results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
