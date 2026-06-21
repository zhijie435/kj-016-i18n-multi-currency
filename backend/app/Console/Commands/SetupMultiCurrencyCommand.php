<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

class SetupMultiCurrencyCommand extends Command
{
    protected $signature = 'setup:multi-currency
                            {--migrate : 运行数据库迁移}
                            {--seed : 运行数据库种子}
                            {--sync : 同步配置到数据库}
                            {--warmup : 预热缓存}
                            {--acceptance : 运行验收测试}
                            {--all : 执行所有安装步骤}';

    protected $description = '一键配置多语言多币种环境';

    public function handle(): int
    {
        $all = $this->option('all');
        $migrate = $all || $this->option('migrate');
        $seed = $all || $this->option('seed');
        $sync = $all || $this->option('sync');
        $warmup = $all || $this->option('warmup');
        $acceptance = $all || $this->option('acceptance');

        if (!$migrate && !$seed && !$sync && !$warmup && !$acceptance) {
            $this->warn('No options specified. Use --all to run all steps, or specify individual options.');
            $this->line('');
            $this->line('Available options:');
            $this->line('  --migrate     Run database migrations');
            $this->line('  --seed        Run database seeders');
            $this->line('  --sync        Sync config to database');
            $this->line('  --warmup      Warmup caches');
            $this->line('  --acceptance  Run acceptance tests');
            $this->line('  --all         Run all steps');
            return 0;
        }

        $this->info('Setting up Multi-Language & Multi-Currency environment...');
        $this->line(str_repeat('=', 60));

        $exitCode = 0;

        if ($migrate) {
            $exitCode |= $this->runStep('Running migrations', function () {
                return Artisan::call('migrate', ['--force' => true]);
            });
        }

        if ($seed) {
            $exitCode |= $this->runStep('Running seeders', function () {
                return Artisan::call('db:seed', ['--force' => true]);
            });
        }

        if ($sync) {
            $exitCode |= $this->runStep('Syncing locales', function () {
                return Artisan::call('locales:sync', ['--force' => true]);
            });

            $exitCode |= $this->runStep('Syncing channels', function () {
                return Artisan::call('channels:sync', ['--force' => true]);
            });

            $exitCode |= $this->runStep('Updating exchange rates', function () {
                return Artisan::call('exchange-rates:update');
            });
        }

        if ($warmup) {
            $exitCode |= $this->runStep('Warming up cache', function () {
                return Artisan::call('cache:warmup');
            });
        }

        if ($acceptance) {
            $exitCode |= $this->runStep('Running acceptance tests', function () {
                return Artisan::call('acceptance:test', ['--suite' => 'default']);
            });
        }

        $this->line(str_repeat('=', 60));

        if ($exitCode === 0) {
            $this->info('✓ Multi-Language & Multi-Currency setup completed successfully!');
        } else {
            $this->error('✗ Setup completed with errors. Please review the output above.');
        }

        return $exitCode;
    }

    protected function runStep(string $name, callable $callback): int
    {
        $this->line("→ {$name}...");

        try {
            $result = $callback();

            if ($result === 0) {
                $this->info("  ✓ {$name} completed");
                $output = Artisan::output();
                if (trim($output)) {
                    $lines = array_filter(explode("\n", $output));
                    foreach (array_slice($lines, 0, 5) as $line) {
                        $this->line("    {$line}");
                    }
                    if (count($lines) > 5) {
                        $this->line("    ... and " . (count($lines) - 5) . " more lines");
                    }
                }
                return 0;
            } else {
                $this->error("  ✗ {$name} failed (exit code: {$result})");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("  ✗ {$name} failed: {$e->getMessage()}");
            return 1;
        }
    }
}
