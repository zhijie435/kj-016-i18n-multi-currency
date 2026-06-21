<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncChannelsJob;
use App\Services\ChannelService;

class SyncChannelsCommand extends Command
{
    protected $signature = 'channels:sync
                            {--code=* : 指定要同步的渠道代码}
                            {--queue : 是否使用队列异步执行}
                            {--force : 强制更新已存在的渠道}';

    protected $description = '同步渠道配置到数据库';

    public function handle(ChannelService $channelService): int
    {
        $channelCodes = $this->option('code') ?: null;
        $useQueue = $this->option('queue');
        $force = $this->option('force');

        $this->info('Starting channel sync...');
        $this->line("Channels: " . ($channelCodes ? implode(', ', $channelCodes) : 'All default'));
        $this->line("Force update: " . ($force ? 'Yes' : 'No'));

        if ($useQueue) {
            SyncChannelsJob::dispatch($channelCodes, $force);
            $this->info('Channel sync job dispatched to queue.');
            return 0;
        }

        try {
            SyncChannelsJob::dispatchSync($channelCodes, $force);

            $channels = $channelService->getAll();

            $this->info('Channels synced successfully!');
            $this->table(
                ['Code', 'Name', 'Locale', 'Currency', 'Enabled'],
                $channels->map(function ($channel) {
                    return [
                        $channel->code,
                        $channel->name,
                        $channel->locale_id ? ($channel->locale->code ?? 'N/A') : 'N/A',
                        $channel->currency_code,
                        $channel->is_enabled ? 'Yes' : 'No',
                    ];
                })->toArray()
            );

            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to sync channels: ' . $e->getMessage());
            return 1;
        }
    }
}
