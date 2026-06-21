<?php

namespace App\Services;

use App\Models\Currency;
use App\Repositories\CurrencyRepository;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class CurrencyService
{
    protected CurrencyRepository $currencyRepository;

    public function __construct(CurrencyRepository $currencyRepository)
    {
        $this->currencyRepository = $currencyRepository;
    }

    public function getAll(): \Illuminate\Support\Collection
    {
        return $this->currencyRepository->getAll();
    }

    public function getEnabled(): \Illuminate\Support\Collection
    {
        return $this->currencyRepository->getEnabled();
    }

    public function getByCode(string $code): Currency
    {
        $currency = $this->currencyRepository->findByCode($code);
        if (!$currency) {
            throw new NotFoundException('Currency');
        }
        return $currency;
    }

    public function findByCode(string $code): ?Currency
    {
        return $this->currencyRepository->findByCode($code);
    }

    public function getById(int $id): Currency
    {
        $currency = $this->currencyRepository->findById($id);
        if (!$currency) {
            throw new NotFoundException('Currency');
        }
        return $currency;
    }

    public function getAvailableCodes(): array
    {
        return $this->currencyRepository->getAvailableCodes();
    }

    public function getDefaultInfo(): array
    {
        return $this->currencyRepository->getDefaultInfo();
    }

    public function create(array $data): Currency
    {
        return DB::transaction(function () use ($data) {
            return $this->currencyRepository->create($data);
        });
    }

    public function update(int $id, array $data): Currency
    {
        return DB::transaction(function () use ($id, $data) {
            $currency = $this->getById($id);
            $this->currencyRepository->update($currency, $data);
            return $currency->fresh();
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $currency = $this->getById($id);
            $defaultCode = Config::get('app.default_currency', 'CNY');

            if ($currency->code === $defaultCode) {
                throw new BusinessException(
                    'Cannot delete the default currency',
                    400,
                    'CANNOT_DELETE_DEFAULT'
                );
            }

            $this->currencyRepository->delete($currency);
        });
    }

    public function formatAmount(float $amount, string $currencyCode): string
    {
        $currency = $this->findByCode($currencyCode);
        $symbol = $currency ? ($currency->symbol ?? '') : '';
        $decimals = $currency ? ($currency->decimals ?? 2) : 2;
        $formatted = number_format($amount, $decimals, '.', ',');
        return $symbol ? "{$symbol}{$formatted}" : $formatted;
    }
}
