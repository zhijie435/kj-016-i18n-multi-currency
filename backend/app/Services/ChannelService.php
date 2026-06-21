<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Locale;
use App\Repositories\ChannelRepository;
use App\Repositories\LocaleRepository;
use App\Repositories\CurrencyRepository;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class ChannelService
{
    protected ChannelRepository $channelRepository;
    protected LocaleRepository $localeRepository;
    protected CurrencyRepository $currencyRepository;

    public function __construct(
        ChannelRepository $channelRepository,
        LocaleRepository $localeRepository,
        CurrencyRepository $currencyRepository
    ) {
        $this->channelRepository = $channelRepository;
        $this->localeRepository = $localeRepository;
        $this->currencyRepository = $currencyRepository;
    }

    public function getAll(): \Illuminate\Support\Collection
    {
        return $this->channelRepository->getAll();
    }

    public function getEnabled(): \Illuminate\Support\Collection
    {
        return $this->channelRepository->getEnabled();
    }

    public function getById(int $id): Channel
    {
        $channel = $this->channelRepository->findById($id);
        if (!$channel) {
            throw new NotFoundException('Channel');
        }
        return $channel;
    }

    public function getByCode(string $code): Channel
    {
        $channel = $this->channelRepository->findByCode($code);
        if (!$channel) {
            throw new NotFoundException('Channel');
        }
        return $channel;
    }

    public function getChannelLocaleCode(string $channelCode): string
    {
        $localeCode = $this->channelRepository->getLocaleCode($channelCode);
        if (!$localeCode) {
            throw new NotFoundException('Channel or locale');
        }
        return $localeCode;
    }

    public function getChannelLocale(string $channelCode): Locale
    {
        $localeCode = $this->getChannelLocaleCode($channelCode);
        $locale = $this->localeRepository->findByCode($localeCode);
        if (!$locale) {
            throw new NotFoundException('Locale');
        }
        return $locale;
    }

    public function getChannelCurrency(string $channelCode): array
    {
        $currency = $this->channelRepository->getCurrencyInfo($channelCode);
        if (!$currency) {
            return $this->currencyRepository->getDefaultInfo();
        }
        if (empty($currency['code'])) {
            return $this->currencyRepository->getDefaultInfo();
        }
        return $currency;
    }

    public function getCurrentContext(?string $channelCode = null): array
    {
        $availableLocales = $this->localeRepository->getAvailableLocales();
        $defaultCode = Config::get('app.default_currency', 'CNY');
        $availableCurrencies = $this->currencyRepository->getEnabledAsArray();

        $currentCurrency = null;
        if ($channelCode) {
            try {
                $channel = $this->channelRepository->findByCode($channelCode);
                if ($channel && $channel->currency_code) {
                    $currentCurrency = $channel->currency_info;
                }
            } catch (\Exception $e) {
                report($e);
            }
        }

        if (!$currentCurrency) {
            $currentCurrency = $availableCurrencies[$defaultCode] ?? [
                'code' => $defaultCode,
                'name' => '',
                'symbol' => '',
                'decimals' => 2,
            ];
        }

        return [
            'locales' => [
                'available' => $availableLocales,
            ],
            'currencies' => [
                'available' => $availableCurrencies,
                'current' => $currentCurrency,
            ],
        ];
    }

    public function create(array $data): Channel
    {
        return DB::transaction(function () use ($data) {
            $locale = null;
            if (isset($data['locale_code']) && $data['locale_code'] !== null && $data['locale_code'] !== '') {
                $locale = $this->localeRepository->findByCode($data['locale_code']);
                if (!$locale) {
                    throw new BusinessException(
                        'Locale not found: ' . $data['locale_code'],
                        400,
                        'LOCALE_NOT_FOUND'
                    );
                }
            }
            return $this->channelRepository->create($data, $locale);
        });
    }

    public function update(int $id, array $data): Channel
    {
        return DB::transaction(function () use ($id, $data) {
            $channel = $this->getById($id);

            $locale = null;
            $hasLocaleChange = array_key_exists('locale_code', $data);
            if ($hasLocaleChange) {
                if ($data['locale_code'] === null || $data['locale_code'] === '') {
                    $locale = null;
                } else {
                    $locale = $this->localeRepository->findByCode($data['locale_code']);
                    if (!$locale) {
                        throw new BusinessException(
                            'Locale not found: ' . $data['locale_code'],
                            400,
                            'LOCALE_NOT_FOUND'
                        );
                    }
                }
            }

            return $this->channelRepository->update(
                $channel,
                $data,
                $hasLocaleChange ? $locale : \App\Repositories\ChannelRepository::NO_CHANGE
            );
        });
    }

    public function updateLocale(int $id, string $localeCode): Channel
    {
        return DB::transaction(function () use ($id, $localeCode) {
            $channel = $this->getById($id);
            $locale = $this->localeRepository->findByCode($localeCode);
            if (!$locale) {
                throw new NotFoundException('Locale');
            }
            return $this->channelRepository->associateLocale($channel, $locale);
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $channel = $this->getById($id);
            $this->channelRepository->delete($channel);
        });
    }
}
