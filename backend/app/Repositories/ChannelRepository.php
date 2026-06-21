<?php

namespace App\Repositories;

use App\Models\Channel;
use App\Models\Locale;
use Illuminate\Support\Collection;

class ChannelRepository extends BaseRepository
{
    protected const MODEL_CLASS = 'Channel';
    protected const CACHE_TTL = 3600;
    public const NO_CHANGE = '__NO_LOCALE_CHANGE__';

    public function getAll(): Collection
    {
        return $this->remember('all', fn() => Channel::with('locale')->ordered()->get());
    }

    public function getEnabled(): Collection
    {
        return $this->remember('enabled', fn() => Channel::with('locale')->enabled()->ordered()->get());
    }

    public function findById(int $id): ?Channel
    {
        return $this->safeDatabaseCall(
            fn() => Channel::with('locale')->find($id),
            fn() => null
        );
    }

    public function findByCode(string $code): ?Channel
    {
        return $this->remember("code:{$code}", function () use ($code) {
            return $this->safeDatabaseCall(
                fn() => Channel::with('locale')->where('code', $code)->first(),
                fn() => null
            );
        });
    }

    public function getLocaleCode(string $channelCode): ?string
    {
        $channel = $this->findByCode($channelCode);
        return $channel ? ($channel->locale_code ?? null) : null;
    }

    public function getCurrencyInfo(string $channelCode): ?array
    {
        $channel = $this->findByCode($channelCode);
        return $channel ? ($channel->currency_info ?? null) : null;
    }

    public function create(array $data, ?Locale $locale = null): Channel
    {
        $this->clearCache();
        $channel = new Channel();
        $this->fillChannel($channel, $data);
        if ($locale) {
            $channel->locale()->associate($locale);
        }
        $channel->save();
        $channel->load('locale');
        return $channel;
    }

    public function update(Channel $channel, array $data, $localeChange = self::NO_CHANGE): Channel
    {
        $this->clearCache();
        $this->fillChannel($channel, $data);

        if ($localeChange !== self::NO_CHANGE) {
            if ($localeChange === null || $localeChange === '') {
                $channel->locale()->dissociate();
            } elseif ($localeChange instanceof Locale) {
                $channel->locale()->associate($localeChange);
            }
        }

        $channel->save();
        $channel->load('locale');
        return $channel;
    }

    public function associateLocale(Channel $channel, Locale $locale): Channel
    {
        $this->clearCache();
        $channel->locale()->associate($locale);
        $channel->save();
        $channel->load('locale');
        return $channel;
    }

    public function delete(Channel $channel): ?bool
    {
        $this->clearCache();
        return $channel->delete();
    }

    public function clearLocaleForLocale(int $localeId): int
    {
        $this->clearCache();
        return Channel::where('locale_id', $localeId)->update(['locale_id' => null]);
    }

    public function clearCache(): void
    {
        $this->forget('all');
        $this->forget('enabled');
        $this->clearCacheByPrefix();
    }

    protected function fillChannel(Channel $channel, array $data): void
    {
        $fillable = [
            'code', 'name', 'description', 'currency_code',
            'currency_symbol', 'currency_decimals', 'is_enabled', 'sort_order'
        ];
        foreach ($fillable as $key) {
            if (array_key_exists($key, $data)) {
                $channel->{$key} = $data[$key];
            }
        }
    }
}
