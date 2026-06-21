<?php

namespace App\Repositories;

use App\Models\Locale;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class LocaleRepository extends BaseRepository
{
    protected const MODEL_CLASS = 'Locale';
    protected const CACHE_TTL = 7200;

    public function getAll(): Collection
    {
        return $this->safeDatabaseCall(
            fn() => Locale::ordered()->get(),
            fn() => $this->getAllFromConfig()
        );
    }

    public function getEnabled(): Collection
    {
        return $this->remember('enabled', function () {
            return $this->safeDatabaseCall(
                fn() => Locale::enabled()->ordered()->get(),
                fn() => $this->getEnabledFromConfig()
            );
        });
    }

    public function getAvailableLocales(): array
    {
        return $this->remember('available', function () {
            return $this->safeDatabaseCall(
                fn() => Locale::getAvailableLocales(),
                fn() => Config::get('app.available_locales', [])
            );
        });
    }

    public function getAvailableCodes(): array
    {
        return $this->remember('codes', function () {
            return $this->safeDatabaseCall(
                fn() => Locale::getAvailableLocaleCodes(),
                fn() => array_keys(Config::get('app.available_locales', []))
            );
        });
    }

    public function getDefaultCode(): string
    {
        return $this->remember('default_code', function () {
            return $this->safeDatabaseCall(
                fn() => Locale::getDefaultLocale(),
                fn() => Config::get('app.locale', 'zh_CN')
            );
        });
    }

    public function findByCode(string $code): ?Locale
    {
        return $this->remember("code:{$code}", function () use ($code) {
            return $this->safeDatabaseCall(
                fn() => Locale::findByCode($code),
                fn() => $this->findByCodeFromConfig($code)
            );
        });
    }

    public function findById(int $id): ?Locale
    {
        return $this->safeDatabaseCall(
            fn() => Locale::find($id),
            fn() => null
        );
    }

    public function create(array $data): Locale
    {
        $this->clearCache();
        return Locale::create($data);
    }

    public function update(Locale $locale, array $data): bool
    {
        $this->clearCache();
        return $locale->update($data);
    }

    public function delete(Locale $locale): ?bool
    {
        $this->clearCache();
        return $locale->delete();
    }

    public function unsetDefaultExcept(?int $exceptId = null): int
    {
        $this->clearCache();
        $query = Locale::query();
        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }
        return $query->update(['is_default' => false]);
    }

    public function clearCache(): void
    {
        $this->forget('enabled');
        $this->forget('available');
        $this->forget('codes');
        $this->forget('default_code');
        $this->clearCacheByPrefix();
    }

    protected function getAllFromConfig(): Collection
    {
        $configLocales = Config::get('app.available_locales', []);
        $locales = [];
        $sort = 0;
        foreach ($configLocales as $code => $item) {
            $locales[] = new Locale([
                'code' => $code,
                'name' => $item['name'] ?? '',
                'native_name' => $item['native'] ?? '',
                'is_enabled' => true,
                'sort_order' => $sort++,
            ]);
        }
        return collect($locales);
    }

    protected function getEnabledFromConfig(): Collection
    {
        return $this->getAllFromConfig();
    }

    protected function findByCodeFromConfig(string $code): ?Locale
    {
        $configLocales = Config::get('app.available_locales', []);
        if (!isset($configLocales[$code])) {
            return null;
        }
        return new Locale([
            'code' => $code,
            'name' => $configLocales[$code]['name'] ?? '',
            'native_name' => $configLocales[$code]['native'] ?? '',
            'is_enabled' => true,
        ]);
    }
}
