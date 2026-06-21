<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Config;
use App\Jobs\UpdateExchangeRatesJob;
use App\Jobs\WarmupCacheJob;
use App\Jobs\SyncLocalesJob;
use App\Jobs\SyncChannelsJob;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        if (Config::get('app.exchange_rate.auto_update', false)) {
            $interval = Config::get('app.exchange_rate.update_interval', 3600);
            $schedule->job(new UpdateExchangeRatesJob())
                ->everyMinute()
                ->when(function () use ($interval) {
                    static $lastRun = null;
                    $now = time();
                    if ($lastRun === null || ($now - $lastRun) >= $interval) {
                        $lastRun = $now;
                        return true;
                    }
                    return false;
                })
                ->name('exchange-rates-auto-update')
                ->withoutOverlapping(60);
        }

        $schedule->job(new WarmupCacheJob(['locales', 'currencies']))
            ->dailyAt('02:00')
            ->name('daily-cache-warmup')
            ->withoutOverlapping(10);

        $schedule->job(new WarmupCacheJob(['exchange_rates']))
            ->everySixHours()
            ->name('exchange-rate-cache-warmup')
            ->withoutOverlapping(10);

        $schedule->job(new SyncLocalesJob())
            ->weekly()
            ->mondays()
            ->at('01:00')
            ->name('weekly-locale-sync')
            ->withoutOverlapping(30);

        $schedule->job(new SyncChannelsJob())
            ->weekly()
            ->mondays()
            ->at('01:30')
            ->name('weekly-channel-sync')
            ->withoutOverlapping(30);

        $schedule->command('cache:clear')
            ->weekly()
            ->sundays()
            ->at('00:00')
            ->name('weekly-cache-clear');

        $schedule->command('queue:prune-batches')
            ->daily()
            ->name('queue-prune-batches');

        $schedule->command('queue:prune-failed')
            ->weekly()
            ->name('queue-prune-failed');
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
