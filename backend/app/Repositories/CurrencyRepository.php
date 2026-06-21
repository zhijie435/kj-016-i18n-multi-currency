<?php

namespace App\Repositories;

use App\Models\Currency;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class CurrencyRepository extends BaseRepository
{
    protected const MODEL_CLASS = 'Currency';
    protected const CACHE_TTL = 3600;

    public function getAll(): Collection
    {
        return $this->remember('all', function () {
            return $this->safeDatabaseCall(
                fn() => Currency::ordered()->get(),
                fn() => $this->getAllFromConfig()
            );
        });
    }

    public function getEnabled(): Collection
    {
        return $this->remember('enabled', function () {
            return $this->safeDatabaseCall(
                fn() => Currency::enabled()->ordered()->get(),
                fn() => $this->getEnabledFromConfig()
            );
        });
    }

    public function findByCode(string $code): ?Currency
    {
        return $this->remember("code:{$code}", function () use ($code) {
            return $this->safeDatabaseCall(
                fn() => Currency::findByCode($code),
                fn() => $this->findByCodeFromConfig($code)
            );
        });
    }

    public function findById(int $id): ?Currency
    {
        return $this->safeDatabaseCall(
            fn() => Currency::find($id),
            fn() => null
        );
    }

    public function getEnabledAsArray(): array
    {
        return $this->remember('enabled_array', function () {
            return $this->safeDatabaseCall(
                fn() => Currency::getEnabledCurrencies(),
                fn() => Config::get('app.available_currencies', [])
            );
        });
    }

    public function getAvailableCodes(): array
    {
        return $this->remember('codes', function () {
            return $this->safeDatabaseCall(
                fn() => Currency::getAvailableCurrencyCodes(),
                fn() => array_keys(Config::get('app.available_currencies', []))
            );
        });
    }

    public function getDefaultInfo(): array
    {
        return $this->remember('default_info', function () {
            $defaultCode = Config::get('app.default_currency', 'CNY');
            $currency = $this->findByCode($defaultCode);
            if ($currency) {
                return $currency->info;
            }
            $configCurrencies = Config::get('app.available_currencies', []);
            return $configCurrencies[$defaultCode] ?? [
                'code' => $defaultCode,
                'name' => '',
                'symbol' => '',
                'decimals' => 2,
            ];
        });
    }

    public function create(array $data): Currency
    {
        $this->clearCache();
        return Currency::create($data);
    }

    public function update(Currency $currency, array $data): bool
    {
        $this->clearCache();
        return $currency->update($data);
    }

    public function delete(Currency $currency): ?bool
    {
        $this->clearCache();
        return $currency->delete();
    }

    public function clearCache(): void
    {
        $this->forget('all');
        $this->forget('enabled');
        $this->forget('enabled_array');
        $this->forget('codes');
        $this->forget('default_info');
        $this->clearCacheByPrefix();
    }

    protected function getAllFromConfig(): Collection
    {
        $configCurrencies = Config::get('app.available_currencies', []);
        $currencies = [];
        $sort = 0;
        foreach ($configCurrencies as $code => $item) {
            $currencies[] = new Currency([
                'code' => $code,
                'name' => $item['name'] ?? '',
                'symbol' => $item['symbol'] ?? '',
                'decimals' => $item['decimals'] ?? 2,
                'is_enabled' => true,
                'sort_order' => $sort++,
            ]);
        }
        return collect($currencies);
    }

    protected function getEnabledFromConfig(): Collection
    {
        return $this->getAllFromConfig();
    }

    protected function findByCodeFromConfig(string $code): ?Currency
    {
        $configCurrencies = Config::get('app.available_currencies', []);
        if (!isset($configCurrencies[$code])) {
            return null;
        }
        return new Currency([
            'code' => $code,
            'name' => $configCurrencies[$code]['name'] ?? '',
            'symbol' => $configCurrencies[$code]['symbol'] ?? '',
            'decimals' => $configCurrencies[$code]['decimals'] ?? 2,
            'is_enabled' => true,
        ]);
    }
}
