<?php

namespace App\Services;

use App\Models\CurrencyExchangeRate;
use App\Repositories\ExchangeRateRepository;
use App\Repositories\CurrencyRepository;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\DB;

class ExchangeRateService
{
    protected ExchangeRateRepository $exchangeRateRepository;
    protected CurrencyRepository $currencyRepository;

    public function __construct(
        ExchangeRateRepository $exchangeRateRepository,
        CurrencyRepository $currencyRepository
    ) {
        $this->exchangeRateRepository = $exchangeRateRepository;
        $this->currencyRepository = $currencyRepository;
    }

    public function getAll(?string $fromCode = null, ?string $toCode = null, ?string $date = null): \Illuminate\Support\Collection
    {
        return $this->exchangeRateRepository->getAll($fromCode, $toCode, $date);
    }

    public function getActive(?string $date = null): \Illuminate\Support\Collection
    {
        return $this->exchangeRateRepository->getActive($date);
    }

    public function getById(int $id): CurrencyExchangeRate
    {
        $rate = $this->exchangeRateRepository->findById($id);
        if (!$rate) {
            throw new NotFoundException('Exchange rate');
        }
        return $rate;
    }

    public function getLatest(string $fromCode, string $toCode, ?string $date = null): CurrencyExchangeRate
    {
        $rate = $this->exchangeRateRepository->getLatest($fromCode, $toCode, $date);
        if (!$rate) {
            throw new NotFoundException('Exchange rate');
        }
        return $rate;
    }

    public function convert(float $amount, string $fromCode, string $toCode, ?string $date = null): float
    {
        $result = $this->exchangeRateRepository->convert($amount, $fromCode, $toCode, $date);
        if ($result === null) {
            throw new BusinessException(
                'No exchange rate available for conversion',
                400,
                'NO_EXCHANGE_RATE',
                [
                    'from' => $fromCode,
                    'to' => $toCode,
                    'date' => $date,
                ]
            );
        }
        return $result;
    }

    public function convertWithDetail(float $amount, string $fromCode, string $toCode, ?string $date = null): array
    {
        $result = $this->exchangeRateRepository->convertWithDetail($amount, $fromCode, $toCode, $date);
        if (!($result['success'] ?? false)) {
            throw new BusinessException(
                $result['message'] ?? 'Conversion failed',
                400,
                'CONVERSION_FAILED',
                [
                    'from' => $fromCode,
                    'to' => $toCode,
                    'date' => $date,
                ]
            );
        }
        return $result;
    }

    public function getMatrix(array $currencyCodes, ?string $date = null): array
    {
        return $this->exchangeRateRepository->getMatrix($currencyCodes, $date);
    }

    public function create(array $data): CurrencyExchangeRate
    {
        return DB::transaction(function () use ($data) {
            $this->validateCurrencyPair($data['from_currency_code'], $data['to_currency_code']);
            $rate = $this->exchangeRateRepository->create($data);
            $rate->load(['fromCurrency', 'toCurrency']);
            return $rate;
        });
    }

    public function update(int $id, array $data): CurrencyExchangeRate
    {
        return DB::transaction(function () use ($id, $data) {
            $rate = $this->getById($id);

            $fromCode = $data['from_currency_code'] ?? $rate->from_currency_code;
            $toCode = $data['to_currency_code'] ?? $rate->to_currency_code;
            if ($fromCode !== $rate->from_currency_code || $toCode !== $rate->to_currency_code) {
                $this->validateCurrencyPair($fromCode, $toCode);
            }

            $this->exchangeRateRepository->update($rate, $data);
            $rate = $rate->fresh();
            $rate->load(['fromCurrency', 'toCurrency']);
            return $rate;
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $rate = $this->getById($id);
            $this->exchangeRateRepository->delete($rate);
        });
    }

    public function activate(int $id): CurrencyExchangeRate
    {
        return DB::transaction(function () use ($id) {
            $rate = $this->getById($id);
            $this->exchangeRateRepository->activate($rate);
            return $rate->fresh();
        });
    }

    public function deactivate(int $id): CurrencyExchangeRate
    {
        return DB::transaction(function () use ($id) {
            $rate = $this->getById($id);
            $this->exchangeRateRepository->deactivate($rate);
            return $rate->fresh();
        });
    }

    protected function validateCurrencyPair(string $fromCode, string $toCode): void
    {
        if ($fromCode === $toCode) {
            throw new BusinessException(
                'Source and target currencies must be different',
                400,
                'SAME_CURRENCY_PAIR'
            );
        }

        $availableCodes = $this->currencyRepository->getAvailableCodes();
        if (!empty($availableCodes)) {
            if (!in_array($fromCode, $availableCodes, true)) {
                throw new BusinessException(
                    'Source currency not available: ' . $fromCode,
                    400,
                    'INVALID_FROM_CURRENCY'
                );
            }
            if (!in_array($toCode, $availableCodes, true)) {
                throw new BusinessException(
                    'Target currency not available: ' . $toCode,
                    400,
                    'INVALID_TO_CURRENCY'
                );
            }
        }
    }
}
