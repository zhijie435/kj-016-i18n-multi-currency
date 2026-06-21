<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncLocalesJob;
use App\Services\LocaleService;

class SyncLocalesCommand extends Command
{
    protected $signature = 'locales:sync
                            {--code=* : 指定要同步的语言代码}
                            {--queue : 是否使用队列异步执行}
                            {--force : 强制更新已存在的语言}';

    protected $description = '同步语言包配置到数据库';

    public function handle(LocaleService $localeService): int
    {
        $localeCodes = $this->option('code') ?: null;
        $useQueue = $this->option('queue');
        $force = $this->option('force');

        $this->info('Starting locale sync...');
        $this->line("Locales: " . ($localeCodes ? implode(', ', $localeCodes) : 'All configured'));
        $this->line("Force update: " . ($force ? 'Yes' : 'No'));

        if ($useQueue) {
            SyncLocalesJob::dispatch($localeCodes, $force);
            $this->info('Locale sync job dispatched to queue.');
            return 0;
        }

        try {
            SyncLocalesJob::dispatchSync($localeCodes, $force);

            $locales = $localeService->getAll();

            $this->info('Locales synced successfully!');
            $this->table(
                ['Code', 'Name', 'Native Name', 'Flag', 'Enabled', 'Default'],
                $locales->map(function ($locale) {
                    return [
                        $locale->code,
                        $locale->name,
                        $locale->native_name,
                        $locale->flag,
                        $locale->is_enabled ? 'Yes' : 'No',
                        $locale->is_default ? 'Yes' : 'No',
                    ];
                })->toArray()
            );

            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to sync locales: ' . $e->getMessage());
            return 1;
        }
    }
}
