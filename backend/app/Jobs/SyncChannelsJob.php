<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ChannelService;
use App\Services\LocaleService;
use App\Services\CurrencyService;

class SyncChannelsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    protected ?array $channelCodes;
    protected bool $forceUpdate;

    public function __construct(?array $channelCodes = null, bool $forceUpdate = false)
    {
        $this->channelCodes = $channelCodes;
        $this->forceUpdate = $forceUpdate;
        $this->onQueue('default');
    }

    public function handle(
        ChannelService $channelService,
        LocaleService $localeService,
        CurrencyService $currencyService
    ): void {
        Log::info('Starting channel sync', [
            'channel_codes' => $this->channelCodes,
            'force_update' => $this->forceUpdate,
        ]);

        try {
            $defaultChannels = $this->getDefaultChannels($localeService, $currencyService);
            $channelsToSync = $this->channelCodes ?? array_keys($defaultChannels);

            DB::transaction(function () use ($channelService, $channelsToSync, $defaultChannels) {
                foreach ($channelsToSync as $code) {
                    if (!isset($defaultChannels[$code])) {
                        continue;
                    }

                    $config = $defaultChannels[$code];

                    $existing = $channelService->findByCode($code);

                    if ($existing && !$this->forceUpdate) {
                        continue;
                    }

                    if ($existing) {
                        $channelService->update($existing->id, $config);
                        Log::info('Updated channel', ['code' => $code]);
                    } else {
                        $channelService->create($config);
                        Log::info('Created channel', ['code' => $code]);
                    }
                }
            });

            Log::info('Channel sync completed successfully');
        } catch (\Exception $e) {
            Log::error('Failed to sync channels', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function getDefaultChannels(LocaleService $localeService, CurrencyService $currencyService): array
    {
        $zhCN = $localeService->findByCode('zh_CN');
        $en = $localeService->findByCode('en');
        $ptBR = $localeService->findByCode('pt_BR');
        $ru = $localeService->findByCode('ru');

        return [
            'cn_main' => [
                'code' => 'cn_main',
                'name' => '中国主站',
                'description' => '中国大陆地区主站渠道',
                'locale_id' => $zhCN->id ?? null,
                'currency_code' => 'CNY',
                'currency_symbol' => '¥',
                'currency_decimals' => 2,
                'is_enabled' => true,
                'sort_order' => 1,
            ],
            'us_main' => [
                'code' => 'us_main',
                'name' => 'US Main',
                'description' => 'United States main channel',
                'locale_id' => $en->id ?? null,
                'currency_code' => 'USD',
                'currency_symbol' => '$',
                'currency_decimals' => 2,
                'is_enabled' => true,
                'sort_order' => 2,
            ],
            'eu_main' => [
                'code' => 'eu_main',
                'name' => 'EU Main',
                'description' => 'European Union main channel',
                'locale_id' => $en->id ?? null,
                'currency_code' => 'EUR',
                'currency_symbol' => '€',
                'currency_decimals' => 2,
                'is_enabled' => true,
                'sort_order' => 3,
            ],
            'br_main' => [
                'code' => 'br_main',
                'name' => 'Brasil Principal',
                'description' => 'Canal principal do Brasil',
                'locale_id' => $ptBR->id ?? null,
                'currency_code' => 'BRL',
                'currency_symbol' => 'R$',
                'currency_decimals' => 2,
                'is_enabled' => true,
                'sort_order' => 4,
            ],
            'ru_main' => [
                'code' => 'ru_main',
                'name' => 'Россия Основной',
                'description' => 'Основной канал России',
                'locale_id' => $ru->id ?? null,
                'currency_code' => 'RUB',
                'currency_symbol' => '₽',
                'currency_decimals' => 2,
                'is_enabled' => true,
                'sort_order' => 5,
            ],
        ];
    }
}
