<?php

namespace App\Services;

use App\Models\Locale;
use App\Repositories\LocaleRepository;
use App\Repositories\ChannelRepository;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\DB;

class LocaleService
{
    protected LocaleRepository $localeRepository;
    protected ChannelRepository $channelRepository;

    public function __construct(LocaleRepository $localeRepository, ChannelRepository $channelRepository)
    {
        $this->localeRepository = $localeRepository;
        $this->channelRepository = $channelRepository;
    }

    public function getAll(): \Illuminate\Support\Collection
    {
        return $this->localeRepository->getAll();
    }

    public function getEnabled(): \Illuminate\Support\Collection
    {
        return $this->localeRepository->getEnabled();
    }

    public function getAvailableLocales(): array
    {
        return $this->localeRepository->getAvailableLocales();
    }

    public function getAvailableCodes(): array
    {
        return $this->localeRepository->getAvailableCodes();
    }

    public function getDefaultCode(): string
    {
        return $this->localeRepository->getDefaultCode();
    }

    public function findByCode(string $code): ?Locale
    {
        return $this->localeRepository->findByCode($code);
    }

    public function getById(int $id): Locale
    {
        $locale = $this->localeRepository->findById($id);
        if (!$locale) {
            throw new NotFoundException('Locale');
        }
        return $locale;
    }

    public function validateCode(string $code): string
    {
        $availableCodes = $this->getAvailableCodes();
        if (!in_array($code, $availableCodes, true)) {
            throw new BusinessException(
                'Unsupported locale',
                400,
                'UNSUPPORTED_LOCALE',
                ['available' => $availableCodes]
            );
        }
        return $code;
    }

    public function create(array $data): Locale
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['is_default']) && $data['is_default']) {
                $this->localeRepository->unsetDefaultExcept();
            }
            return $this->localeRepository->create($data);
        });
    }

    public function update(int $id, array $data): Locale
    {
        return DB::transaction(function () use ($id, $data) {
            $locale = $this->getById($id);

            if (isset($data['is_default']) && $data['is_default']) {
                $this->localeRepository->unsetDefaultExcept($locale->id);
            }

            $this->localeRepository->update($locale, $data);
            return $locale->fresh();
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $locale = $this->getById($id);

            if ($locale->is_default) {
                throw new BusinessException(
                    'Cannot delete the default locale',
                    400,
                    'CANNOT_DELETE_DEFAULT'
                );
            }

            $this->channelRepository->clearLocaleForLocale($locale->id);
            $this->localeRepository->delete($locale);
        });
    }
}
