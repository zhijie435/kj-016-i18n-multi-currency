<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Mockery;
use App\Services\ExchangeRateService;
use App\Repositories\ExchangeRateRepository;
use App\Repositories\CurrencyRepository;
use App\Models\CurrencyExchangeRate;
use App\Models\Currency;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExchangeRateServiceTest extends TestCase
{
    protected $exchangeRateRepository;
    protected $currencyRepository;
    protected $exchangeRateService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exchangeRateRepository = Mockery::mock(ExchangeRateRepository::class);
        $this->currencyRepository = Mockery::mock(CurrencyRepository::class);

        $this->exchangeRateService = new ExchangeRateService(
            $this->exchangeRateRepository,
            $this->currencyRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function mockCurrency(array $attributes = []): Currency
    {
        $defaults = [
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimals' => 2,
            'is_enabled' => true,
        ];

        return new Currency(array_merge($defaults, $attributes));
    }

    protected function mockExchangeRate(array $attributes = []): CurrencyExchangeRate
    {
        $defaults = [
            'id' => 1,
            'from_currency_code' => 'USD',
            'to_currency_code' => 'CNY',
            'rate' => 7.25,
            'effective_date' => '2024-01-01',
            'source' => 'test',
            'is_active' => true,
        ];

        $data = array_merge($defaults, $attributes);

        $rate = Mockery::mock(CurrencyExchangeRate::class)->makePartial();
        $rate->shouldReceive('getAttribute')->with('id')->andReturn($data['id']);
        $rate->shouldReceive('getAttribute')->with('from_currency_code')->andReturn($data['from_currency_code']);
        $rate->shouldReceive('getAttribute')->with('to_currency_code')->andReturn($data['to_currency_code']);
        $rate->shouldReceive('getAttribute')->with('rate')->andReturn($data['rate']);
        $rate->shouldReceive('getAttribute')->with('effective_date')->andReturn($data['effective_date']);
        $rate->shouldReceive('getAttribute')->with('source')->andReturn($data['source']);
        $rate->shouldReceive('getAttribute')->with('is_active')->andReturn($data['is_active']);

        foreach ($data as $key => $value) {
            $rate->$key = $value;
        }

        $fromCurrency = $this->mockCurrency(['code' => $data['from_currency_code']]);
        $toCurrency = $this->mockCurrency(['code' => $data['to_currency_code']]);

        $rate->shouldReceive('load')->andReturnSelf();
        $rate->shouldReceive('fresh')->andReturnSelf();
        $rate->shouldReceive('setRelation')->andReturnSelf();
        $rate->shouldReceive('getRelation')->with('fromCurrency')->andReturn($fromCurrency);
        $rate->shouldReceive('getRelation')->with('toCurrency')->andReturn($toCurrency);

        $rate->setRelation('fromCurrency', $fromCurrency);
        $rate->setRelation('toCurrency', $toCurrency);

        return $rate;
    }

    public function testGetAllReturnsCollection()
    {
        $rates = new Collection([$this->mockExchangeRate()]);

        $this->exchangeRateRepository
            ->shouldReceive('getAll')
            ->with(null, null, null)
            ->once()
            ->andReturn($rates);

        $result = $this->exchangeRateService->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
    }

    public function testGetAllWithFilters()
    {
        $rates = new Collection([$this->mockExchangeRate()]);

        $this->exchangeRateRepository
            ->shouldReceive('getAll')
            ->with('USD', 'CNY', '2024-01-01')
            ->once()
            ->andReturn($rates);

        $result = $this->exchangeRateService->getAll('USD', 'CNY', '2024-01-01');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
    }

    public function testGetActiveReturnsCollection()
    {
        $rates = new Collection([$this->mockExchangeRate()]);

        $this->exchangeRateRepository
            ->shouldReceive('getActive')
            ->with(null)
            ->once()
            ->andReturn($rates);

        $result = $this->exchangeRateService->getActive();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
    }

    public function testGetActiveWithDate()
    {
        $rates = new Collection([$this->mockExchangeRate()]);

        $this->exchangeRateRepository
            ->shouldReceive('getActive')
            ->with('2024-01-01')
            ->once()
            ->andReturn($rates);

        $result = $this->exchangeRateService->getActive('2024-01-01');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
    }

    public function testGetByIdReturnsRate()
    {
        $rate = $this->mockExchangeRate();

        $this->exchangeRateRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($rate);

        $result = $this->exchangeRateService->getById(1);

        $this->assertInstanceOf(CurrencyExchangeRate::class, $result);
        $this->assertEquals(1, $result->id);
    }

    public function testGetByIdThrowsNotFoundException()
    {
        $this->exchangeRateRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Exchange rate not found');

        $this->exchangeRateService->getById(999);
    }

    public function testGetLatestReturnsRate()
    {
        $rate = $this->mockExchangeRate();

        $this->exchangeRateRepository
            ->shouldReceive('getLatest')
            ->with('USD', 'CNY', null)
            ->once()
            ->andReturn($rate);

        $result = $this->exchangeRateService->getLatest('USD', 'CNY');

        $this->assertInstanceOf(CurrencyExchangeRate::class, $result);
        $this->assertEquals('USD', $result->from_currency_code);
        $this->assertEquals('CNY', $result->to_currency_code);
    }

    public function testGetLatestWithDate()
    {
        $rate = $this->mockExchangeRate();

        $this->exchangeRateRepository
            ->shouldReceive('getLatest')
            ->with('USD', 'CNY', '2024-01-01')
            ->once()
            ->andReturn($rate);

        $result = $this->exchangeRateService->getLatest('USD', 'CNY', '2024-01-01');

        $this->assertInstanceOf(CurrencyExchangeRate::class, $result);
    }

    public function testGetLatestThrowsNotFoundException()
    {
        $this->exchangeRateRepository
            ->shouldReceive('getLatest')
            ->with('USD', 'EUR', null)
            ->once()
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Exchange rate not found');

        $this->exchangeRateService->getLatest('USD', 'EUR');
    }

    public function testConvertReturnsFloat()
    {
        $this->exchangeRateRepository
            ->shouldReceive('convert')
            ->with(100.0, 'USD', 'CNY', null)
            ->once()
            ->andReturn(725.0);

        $result = $this->exchangeRateService->convert(100.0, 'USD', 'CNY');

        $this->assertEquals(725.0, $result);
    }

    public function testConvertWithDate()
    {
        $this->exchangeRateRepository
            ->shouldReceive('convert')
            ->with(100.0, 'USD', 'CNY', '2024-01-01')
            ->once()
            ->andReturn(725.0);

        $result = $this->exchangeRateService->convert(100.0, 'USD', 'CNY', '2024-01-01');

        $this->assertEquals(725.0, $result);
    }

    public function testConvertThrowsBusinessExceptionWhenNoRate()
    {
        $this->exchangeRateRepository
            ->shouldReceive('convert')
            ->with(100.0, 'USD', 'EUR', null)
            ->once()
            ->andReturn(null);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('No exchange rate available for conversion');

        try {
            $this->exchangeRateService->convert(100.0, 'USD', 'EUR');
        } catch (BusinessException $e) {
            $this->assertEquals(400, $e->getStatusCode());
            $this->assertEquals('NO_EXCHANGE_RATE', $e->getErrorCode());
            $this->assertEquals([
                'from' => 'USD',
                'to' => 'EUR',
                'date' => null,
            ], $e->getContext());
            throw $e;
        }
    }

    public function testConvertWithDetailReturnsArray()
    {
        $detail = [
            'success' => true,
            'amount' => 100.0,
            'from_currency' => 'USD',
            'to_currency' => 'CNY',
            'rate' => 7.25,
            'converted_amount' => 725.0,
            'formatted_from' => '$100.00',
            'formatted_to' => '¥725.00',
            'effective_date' => '2024-01-01',
        ];

        $this->exchangeRateRepository
            ->shouldReceive('convertWithDetail')
            ->with(100.0, 'USD', 'CNY', null)
            ->once()
            ->andReturn($detail);

        $result = $this->exchangeRateService->convertWithDetail(100.0, 'USD', 'CNY');

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals(725.0, $result['converted_amount']);
    }

    public function testConvertWithDetailWithDate()
    {
        $detail = [
            'success' => true,
            'amount' => 100.0,
            'from_currency' => 'USD',
            'to_currency' => 'CNY',
            'rate' => 7.25,
            'converted_amount' => 725.0,
            'formatted_from' => '$100.00',
            'formatted_to' => '¥725.00',
            'effective_date' => '2024-01-01',
        ];

        $this->exchangeRateRepository
            ->shouldReceive('convertWithDetail')
            ->with(100.0, 'USD', 'CNY', '2024-01-01')
            ->once()
            ->andReturn($detail);

        $result = $this->exchangeRateService->convertWithDetail(100.0, 'USD', 'CNY', '2024-01-01');

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }

    public function testConvertWithDetailThrowsBusinessExceptionOnFailure()
    {
        $detail = [
            'success' => false,
            'message' => 'Exchange rate not found',
        ];

        $this->exchangeRateRepository
            ->shouldReceive('convertWithDetail')
            ->with(100.0, 'USD', 'EUR', null)
            ->once()
            ->andReturn($detail);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Exchange rate not found');

        try {
            $this->exchangeRateService->convertWithDetail(100.0, 'USD', 'EUR');
        } catch (BusinessException $e) {
            $this->assertEquals(400, $e->getStatusCode());
            $this->assertEquals('CONVERSION_FAILED', $e->getErrorCode());
            $this->assertEquals([
                'from' => 'USD',
                'to' => 'EUR',
                'date' => null,
            ], $e->getContext());
            throw $e;
        }
    }

    public function testConvertWithDetailThrowsBusinessExceptionWithDefaultMessage()
    {
        $detail = [
            'success' => false,
        ];

        $this->exchangeRateRepository
            ->shouldReceive('convertWithDetail')
            ->with(100.0, 'USD', 'EUR', null)
            ->once()
            ->andReturn($detail);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Conversion failed');

        $this->exchangeRateService->convertWithDetail(100.0, 'USD', 'EUR');
    }

    public function testGetMatrixReturnsArray()
    {
        $codes = ['USD', 'CNY', 'EUR'];
        $matrix = [
            'USD' => ['USD' => 1.0, 'CNY' => 7.25, 'EUR' => 0.92],
            'CNY' => ['USD' => 0.138, 'CNY' => 1.0, 'EUR' => 0.127],
            'EUR' => ['USD' => 1.087, 'CNY' => 7.88, 'EUR' => 1.0],
        ];

        $this->exchangeRateRepository
            ->shouldReceive('getMatrix')
            ->with($codes, null)
            ->once()
            ->andReturn($matrix);

        $result = $this->exchangeRateService->getMatrix($codes);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('USD', $result);
        $this->assertArrayHasKey('CNY', $result['USD']);
        $this->assertEquals(7.25, $result['USD']['CNY']);
    }

    public function testGetMatrixWithDate()
    {
        $codes = ['USD', 'CNY'];
        $matrix = [
            'USD' => ['USD' => 1.0, 'CNY' => 7.25],
            'CNY' => ['USD' => 0.138, 'CNY' => 1.0],
        ];

        $this->exchangeRateRepository
            ->shouldReceive('getMatrix')
            ->with($codes, '2024-01-01')
            ->once()
            ->andReturn($matrix);

        $result = $this->exchangeRateService->getMatrix($codes, '2024-01-01');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function testCreateReturnsRate()
    {
        $data = [
            'from_currency_code' => 'USD',
            'to_currency_code' => 'CNY',
            'rate' => 7.25,
            'effective_date' => '2024-01-01',
            'source' => 'test',
            'is_active' => true,
        ];

        $rate = $this->mockExchangeRate($data);

        $this->currencyRepository
            ->shouldReceive('getAvailableCodes')
            ->once()
            ->andReturn(['USD', 'CNY', 'EUR']);

        $this->exchangeRateRepository
            ->shouldReceive('create')
            ->with($data)
            ->once()
            ->andReturn($rate);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $result = $this->exchangeRateService->create($data);

        $this->assertInstanceOf(CurrencyExchangeRate::class, $result);
        $this->assertEquals('USD', $result->from_currency_code);
        $this->assertEquals('CNY', $result->to_currency_code);
    }

    public function testCreateThrowsSameCurrencyPairException()
    {
        $data = [
            'from_currency_code' => 'USD',
            'to_currency_code' => 'USD',
            'rate' => 1.0,
            'effective_date' => '2024-01-01',
            'source' => 'test',
            'is_active' => true,
        ];

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Source and target currencies must be different');

        try {
            $this->exchangeRateService->create($data);
        } catch (BusinessException $e) {
            $this->assertEquals(400, $e->getStatusCode());
            $this->assertEquals('SAME_CURRENCY_PAIR', $e->getErrorCode());
            throw $e;
        }
    }

    public function testCreateThrowsInvalidFromCurrencyException()
    {
        $data = [
            'from_currency_code' => 'INVALID',
            'to_currency_code' => 'CNY',
            'rate' => 7.25,
            'effective_date' => '2024-01-01',
            'source' => 'test',
            'is_active' => true,
        ];

        $this->currencyRepository
            ->shouldReceive('getAvailableCodes')
            ->once()
            ->andReturn(['USD', 'CNY', 'EUR']);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Source currency not available: INVALID');

        try {
            $this->exchangeRateService->create($data);
        } catch (BusinessException $e) {
            $this->assertEquals(400, $e->getStatusCode());
            $this->assertEquals('INVALID_FROM_CURRENCY', $e->getErrorCode());
            throw $e;
        }
    }

    public function testCreateThrowsInvalidToCurrencyException()
    {
        $data = [
            'from_currency_code' => 'USD',
            'to_currency_code' => 'INVALID',
            'rate' => 7.25,
            'effective_date' => '2024-01-01',
            'source' => 'test',
            'is_active' => true,
        ];

        $this->currencyRepository
            ->shouldReceive('getAvailableCodes')
            ->once()
            ->andReturn(['USD', 'CNY', 'EUR']);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Target currency not available: INVALID');

        try {
            $this->exchangeRateService->create($data);
        } catch (BusinessException $e) {
            $this->assertEquals(400, $e->getStatusCode());
            $this->assertEquals('INVALID_TO_CURRENCY', $e->getErrorCode());
            throw $e;
        }
    }

    public function testCreateSkipsValidationWhenAvailableCodesEmpty()
    {
        $data = [
            'from_currency_code' => 'USD',
            'to_currency_code' => 'CNY',
            'rate' => 7.25,
            'effective_date' => '2024-01-01',
            'source' => 'test',
            'is_active' => true,
        ];

        $rate = $this->mockExchangeRate($data);

        $this->currencyRepository
            ->shouldReceive('getAvailableCodes')
            ->once()
            ->andReturn([]);

        $this->exchangeRateRepository
            ->shouldReceive('create')
            ->with($data)
            ->once()
            ->andReturn($rate);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $result = $this->exchangeRateService->create($data);

        $this->assertInstanceOf(CurrencyExchangeRate::class, $result);
    }

    public function testUpdateReturnsRate()
    {
        $existingRate = $this->mockExchangeRate();
        $updateData = ['rate' => 7.30];

        $this->exchangeRateRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($existingRate);

        $this->exchangeRateRepository
            ->shouldReceive('update')
            ->with($existingRate, $updateData)
            ->once()
            ->andReturn(true);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $result = $this->exchangeRateService->update(1, $updateData);

        $this->assertInstanceOf(CurrencyExchangeRate::class, $result);
    }

    public function testUpdateWithCurrencyPairChangeValidates()
    {
        $existingRate = $this->mockExchangeRate([
            'id' => 1,
            'from_currency_code' => 'USD',
            'to_currency_code' => 'CNY',
        ]);

        $updateData = [
            'from_currency_code' => 'EUR',
            'to_currency_code' => 'CNY',
            'rate' => 7.88,
        ];

        $this->exchangeRateRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($existingRate);

        $this->currencyRepository
            ->shouldReceive('getAvailableCodes')
            ->once()
            ->andReturn(['USD', 'CNY', 'EUR']);

        $this->exchangeRateRepository
            ->shouldReceive('update')
            ->with($existingRate, $updateData)
            ->once()
            ->andReturn(true);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $result = $this->exchangeRateService->update(1, $updateData);

        $this->assertInstanceOf(CurrencyExchangeRate::class, $result);
    }

    public function testUpdateThrowsNotFoundException()
    {
        $this->exchangeRateRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Exchange rate not found');

        $this->exchangeRateService->update(999, ['rate' => 7.30]);
    }

    public function testUpdateWithSameCurrencyPairThrowsException()
    {
        $existingRate = $this->mockExchangeRate([
            'id' => 1,
            'from_currency_code' => 'USD',
            'to_currency_code' => 'CNY',
        ]);

        $updateData = [
            'from_currency_code' => 'USD',
            'to_currency_code' => 'USD',
        ];

        $this->exchangeRateRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($existingRate);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Source and target currencies must be different');

        try {
            $this->exchangeRateService->update(1, $updateData);
        } catch (BusinessException $e) {
            $this->assertEquals('SAME_CURRENCY_PAIR', $e->getErrorCode());
            throw $e;
        }
    }

    public function testDeleteExecutesSuccessfully()
    {
        $rate = $this->mockExchangeRate();

        $this->exchangeRateRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($rate);

        $this->exchangeRateRepository
            ->shouldReceive('delete')
            ->with($rate)
            ->once()
            ->andReturn(true);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->exchangeRateService->delete(1);

        $this->assertTrue(true);
    }

    public function testDeleteThrowsNotFoundException()
    {
        $this->exchangeRateRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Exchange rate not found');

        $this->exchangeRateService->delete(999);
    }

    public function testActivateReturnsRate()
    {
        $rate = $this->mockExchangeRate(['is_active' => false]);

        $this->exchangeRateRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($rate);

        $this->exchangeRateRepository
            ->shouldReceive('activate')
            ->with($rate)
            ->once()
            ->andReturn(true);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $result = $this->exchangeRateService->activate(1);

        $this->assertInstanceOf(CurrencyExchangeRate::class, $result);
    }

    public function testActivateThrowsNotFoundException()
    {
        $this->exchangeRateRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Exchange rate not found');

        $this->exchangeRateService->activate(999);
    }

    public function testDeactivateReturnsRate()
    {
        $rate = $this->mockExchangeRate(['is_active' => true]);

        $this->exchangeRateRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($rate);

        $this->exchangeRateRepository
            ->shouldReceive('deactivate')
            ->with($rate)
            ->once()
            ->andReturn(true);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $result = $this->exchangeRateService->deactivate(1);

        $this->assertInstanceOf(CurrencyExchangeRate::class, $result);
    }

    public function testDeactivateThrowsNotFoundException()
    {
        $this->exchangeRateRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Exchange rate not found');

        $this->exchangeRateService->deactivate(999);
    }
}
