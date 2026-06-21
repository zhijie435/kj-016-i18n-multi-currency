<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Services\LocaleService;

class SyncLocalesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    protected ?array $localeCodes;
    protected bool $forceUpdate;

    public function __construct(?array $localeCodes = null, bool $forceUpdate = false)
    {
        $this->localeCodes = $localeCodes;
        $this->forceUpdate = $forceUpdate;
        $this->onQueue('locale_sync');
    }

    public function handle(LocaleService $localeService): void
    {
        Log::info('Starting locale sync', [
            'locale_codes' => $this->localeCodes,
            'force_update' => $this->forceUpdate,
        ]);

        try {
            $configLocales = Config::get('app.available_locales', []);
            $localesToSync = $this->localeCodes ?? array_keys($configLocales);

            DB::transaction(function () use ($localeService, $localesToSync, $configLocales) {
                foreach ($localesToSync as $code) {
                    if (!isset($configLocales[$code])) {
                        continue;
                    }

                    $config = $configLocales[$code];

                    $existing = $localeService->findByCode($code);

                    if ($existing && !$this->forceUpdate) {
                        continue;
                    }

                    $data = [
                        'code' => $code,
                        'name' => $config['name'] ?? $code,
                        'native_name' => $config['native'] ?? $code,
                        'flag' => $config['flag'] ?? null,
                        'element_locale' => $config['element_locale'] ?? $code,
                        'is_enabled' => true,
                        'is_default' => $code === Config::get('app.locale', 'zh_CN'),
                        'sort_order' => array_search($code, array_keys($configLocales)) + 1,
                    ];

                    if ($existing) {
                        $localeService->update($existing->id, $data);
                        Log::info('Updated locale', ['code' => $code]);
                    } else {
                        $localeService->create($data);
                        Log::info('Created locale', ['code' => $code]);
                    }
                }
            });

            Log::info('Locale sync completed successfully');
        } catch (\Exception $e) {
            Log::error('Failed to sync locales', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
