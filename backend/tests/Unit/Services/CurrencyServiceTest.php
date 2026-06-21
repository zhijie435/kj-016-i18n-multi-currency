<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Mockery;
use App\Models\Currency;
use App\Services\CurrencyService;
use App\Repositories\CurrencyRepository;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class CurrencyServiceTest extends TestCase
{
    protected $currencyRepositoryMock;
    protected $currencyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currencyRepositoryMock = Mockery::mock(CurrencyRepository::class);
        $this->currencyService = new CurrencyService($this->currencyRepositoryMock);

        Config::set('app.default_currency', 'CNY');
        Config::set('view.compiled', storage_path('framework/views'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function mockTransaction()
    {
        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });
    }

    protected function createCurrency(array $attributes = []): Currency
    {
        $currency = new Currency(array_merge([
            'code' => 'CNY',
            'name' => '人民币',
            'symbol' => '¥',
            'decimals' => 2,
            'is_enabled' => true,
            'sort_order' => 1,
        ], $attributes));

        if (isset($attributes['id'])) {
            $currency->id = $attributes['id'];
        } else {
            $currency->id = 1;
        }

        return $currency;
    }

    public function testGetAllReturnsAllCurrencies()
    {
        $currencies = new Collection([
            $this->createCurrency(['id' => 1, 'code' => 'CNY']),
            $this->createCurrency(['id' => 2, 'code' => 'USD', 'name' => '美元', 'symbol' => '$']),
        ]);

        $this->currencyRepositoryMock
            ->shouldReceive('getAll')
            ->once()
            ->andReturn($currencies);

        $result = $this->currencyService->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals('CNY', $result->first()->code);
        $this->assertEquals('USD', $result->last()->code);
    }

    public function testGetAllReturnsEmptyCollection()
    {
        $this->currencyRepositoryMock
            ->shouldReceive('getAll')
            ->once()
            ->andReturn(new Collection());

        $result = $this->currencyService->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    public function testGetEnabledReturnsEnabledCurrencies()
    {
        $currencies = new Collection([
            $this->createCurrency(['code' => 'CNY', 'is_enabled' => true]),
            $this->createCurrency(['id' => 2, 'code' => 'USD', 'name' => '美元', 'symbol' => '$', 'is_enabled' => true]),
        ]);

        $this->currencyRepositoryMock
            ->shouldReceive('getEnabled')
            ->once()
            ->andReturn($currencies);

        $result = $this->currencyService->getEnabled();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertTrue($result->every(fn ($c) => $c->is_enabled));
    }

    public function testGetEnabledReturnsEmptyCollection()
    {
        $this->currencyRepositoryMock
            ->shouldReceive('getEnabled')
            ->once()
            ->andReturn(new Collection());

        $result = $this->currencyService->getEnabled();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    public function testGetByCodeReturnsCurrency()
    {
        $currency = $this->createCurrency(['code' => 'CNY']);

        $this->currencyRepositoryMock
            ->shouldReceive('findByCode')
            ->with('CNY')
            ->once()
            ->andReturn($currency);

        $result = $this->currencyService->getByCode('CNY');

        $this->assertInstanceOf(Currency::class, $result);
        $this->assertEquals('CNY', $result->code);
    }

    public function testGetByCodeThrowsNotFoundException()
    {
        $this->currencyRepositoryMock
            ->shouldReceive('findByCode')
            ->with('INVALID')
            ->once()
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Currency not found');

        $this->currencyService->getByCode('INVALID');
    }

    public function testFindByCodeReturnsCurrency()
    {
        $currency = $this->createCurrency(['code' => 'USD']);

        $this->currencyRepositoryMock
            ->shouldReceive('findByCode')
            ->with('USD')
            ->once()
            ->andReturn($currency);

        $result = $this->currencyService->findByCode('USD');

        $this->assertInstanceOf(Currency::class, $result);
        $this->assertEquals('USD', $result->code);
    }

    public function testFindByCodeReturnsNull()
    {
        $this->currencyRepositoryMock
            ->shouldReceive('findByCode')
            ->with('INVALID')
            ->once()
            ->andReturn(null);

        $result = $this->currencyService->findByCode('INVALID');

        $this->assertNull($result);
    }

    public function testGetByIdReturnsCurrency()
    {
        $currency = $this->createCurrency(['id' => 5]);

        $this->currencyRepositoryMock
            ->shouldReceive('findById')
            ->with(5)
            ->once()
            ->andReturn($currency);

        $result = $this->currencyService->getById(5);

        $this->assertInstanceOf(Currency::class, $result);
        $this->assertEquals(5, $result->id);
    }

    public function testGetByIdThrowsNotFoundException()
    {
        $this->currencyRepositoryMock
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Currency not found');

        $this->currencyService->getById(999);
    }

    public function testGetAvailableCodesReturnsArray()
    {
        $codes = ['CNY', 'USD', 'EUR'];

        $this->currencyRepositoryMock
            ->shouldReceive('getAvailableCodes')
            ->once()
            ->andReturn($codes);

        $result = $this->currencyService->getAvailableCodes();

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals(['CNY', 'USD', 'EUR'], $result);
    }

    public function testGetAvailableCodesReturnsEmptyArray()
    {
        $this->currencyRepositoryMock
            ->shouldReceive('getAvailableCodes')
            ->once()
            ->andReturn([]);

        $result = $this->currencyService->getAvailableCodes();

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function testGetDefaultInfoReturnsArray()
    {
        $defaultInfo = [
            'code' => 'CNY',
            'name' => '人民币',
            'symbol' => '¥',
            'decimals' => 2,
        ];

        $this->currencyRepositoryMock
            ->shouldReceive('getDefaultInfo')
            ->once()
            ->andReturn($defaultInfo);

        $result = $this->currencyService->getDefaultInfo();

        $this->assertIsArray($result);
        $this->assertEquals('CNY', $result['code']);
        $this->assertEquals('¥', $result['symbol']);
    }

    public function testGetDefaultInfoWithUsdDefault()
    {
        Config::set('app.default_currency', 'USD');

        $defaultInfo = [
            'code' => 'USD',
            'name' => '美元',
            'symbol' => '$',
            'decimals' => 2,
        ];

        $this->currencyRepositoryMock
            ->shouldReceive('getDefaultInfo')
            ->once()
            ->andReturn($defaultInfo);

        $result = $this->currencyService->getDefaultInfo();

        $this->assertIsArray($result);
        $this->assertEquals('USD', $result['code']);
        $this->assertEquals('$', $result['symbol']);
    }

    public function testCreateReturnsCurrency()
    {
        $this->mockTransaction();

        $data = [
            'code' => 'JPY',
            'name' => '日元',
            'symbol' => '¥',
            'decimals' => 0,
            'is_enabled' => true,
        ];

        $currency = $this->createCurrency($data);

        $this->currencyRepositoryMock
            ->shouldReceive('create')
            ->with($data)
            ->once()
            ->andReturn($currency);

        $result = $this->currencyService->create($data);

        $this->assertInstanceOf(Currency::class, $result);
        $this->assertEquals('JPY', $result->code);
        $this->assertEquals('日元', $result->name);
    }

    public function testCreateWithMinimalData()
    {
        $this->mockTransaction();

        $data = [
            'code' => 'GBP',
            'name' => '英镑',
        ];

        $currency = $this->createCurrency(array_merge($data, [
            'symbol' => '£',
            'decimals' => 2,
            'is_enabled' => true,
        ]));

        $this->currencyRepositoryMock
            ->shouldReceive('create')
            ->with($data)
            ->once()
            ->andReturn($currency);

        $result = $this->currencyService->create($data);

        $this->assertInstanceOf(Currency::class, $result);
        $this->assertEquals('GBP', $result->code);
    }

    public function testUpdateReturnsCurrency()
    {
        $data = ['name' => '欧元更新', 'symbol' => '€'];
        $updatedCurrency = $this->createCurrency([
            'id' => 3,
            'code' => 'EUR',
            'name' => '欧元更新',
            'symbol' => '€',
        ]);

        $currencyMock = Mockery::mock(Currency::class)->makePartial();
        $currencyMock->id = 3;
        $currencyMock->code = 'EUR';
        $currencyMock->shouldReceive('fresh')->once()->andReturn($updatedCurrency);

        DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
            return $callback();
        });

        $this->currencyRepositoryMock
            ->shouldReceive('findById')
            ->with(3)
            ->once()
            ->andReturn($currencyMock);

        $this->currencyRepositoryMock
            ->shouldReceive('update')
            ->with($currencyMock, $data)
            ->once()
            ->andReturn(true);

        $result = $this->currencyService->update(3, $data);

        $this->assertInstanceOf(Currency::class, $result);
        $this->assertEquals('欧元更新', $result->name);
    }

    public function testUpdateThrowsNotFoundException()
    {
        $this->mockTransaction();

        $this->currencyRepositoryMock
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Currency not found');

        $this->currencyService->update(999, ['name' => '测试']);
    }

    public function testDeleteSuccessfully()
    {
        $this->mockTransaction();
        Config::set('app.default_currency', 'CNY');

        $currency = $this->createCurrency(['id' => 2, 'code' => 'USD']);

        $this->currencyRepositoryMock
            ->shouldReceive('findById')
            ->with(2)
            ->once()
            ->andReturn($currency);

        $this->currencyRepositoryMock
            ->shouldReceive('delete')
            ->with($currency)
            ->once()
            ->andReturn(true);

        $this->currencyService->delete(2);
        $this->assertTrue(true);
    }

    public function testDeleteDefaultCurrencyThrowsBusinessException()
    {
        $this->mockTransaction();
        Config::set('app.default_currency', 'CNY');

        $currency = $this->createCurrency(['id' => 1, 'code' => 'CNY']);

        $this->currencyRepositoryMock
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($currency);

        $this->currencyRepositoryMock
            ->shouldNotReceive('delete');

        try {
            $this->currencyService->delete(1);
            $this->fail('Expected BusinessException was not thrown');
        } catch (BusinessException $e) {
            $this->assertEquals('Cannot delete the default currency', $e->getMessage());
            $this->assertEquals(400, $e->getStatusCode());
            $this->assertEquals('CANNOT_DELETE_DEFAULT', $e->getErrorCode());
        }
    }

    public function testDeleteThrowsNotFoundException()
    {
        $this->mockTransaction();

        $this->currencyRepositoryMock
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Currency not found');

        $this->currencyService->delete(999);
    }

    public function testFormatAmountWithCny()
    {
        $currency = $this->createCurrency([
            'code' => 'CNY',
            'symbol' => '¥',
            'decimals' => 2,
        ]);

        $this->currencyRepositoryMock
            ->shouldReceive('findByCode')
            ->with('CNY')
            ->once()
            ->andReturn($currency);

        $result = $this->currencyService->formatAmount(1234.56, 'CNY');

        $this->assertEquals('¥1,234.56', $result);
    }

    public function testFormatAmountWithUsd()
    {
        $currency = $this->createCurrency([
            'code' => 'USD',
            'symbol' => '$',
            'decimals' => 2,
        ]);

        $this->currencyRepositoryMock
            ->shouldReceive('findByCode')
            ->with('USD')
            ->once()
            ->andReturn($currency);

        $result = $this->currencyService->formatAmount(999.99, 'USD');

        $this->assertEquals('$999.99', $result);
    }

    public function testFormatAmountWithZeroDecimals()
    {
        $currency = $this->createCurrency([
            'code' => 'JPY',
            'symbol' => '¥',
            'decimals' => 0,
        ]);

        $this->currencyRepositoryMock
            ->shouldReceive('findByCode')
            ->with('JPY')
            ->once()
            ->andReturn($currency);

        $result = $this->currencyService->formatAmount(1234.56, 'JPY');

        $this->assertEquals('¥1,235', $result);
    }

    public function testFormatAmountWithNoSymbol()
    {
        $currency = $this->createCurrency([
            'code' => 'XXX',
            'symbol' => '',
            'decimals' => 2,
        ]);

        $this->currencyRepositoryMock
            ->shouldReceive('findByCode')
            ->with('XXX')
            ->once()
            ->andReturn($currency);

        $result = $this->currencyService->formatAmount(100.5, 'XXX');

        $this->assertEquals('100.50', $result);
    }

    public function testFormatAmountWithUnknownCurrencyUsesDefaults()
    {
        $this->currencyRepositoryMock
            ->shouldReceive('findByCode')
            ->with('UNKNOWN')
            ->once()
            ->andReturn(null);

        $result = $this->currencyService->formatAmount(1234.56, 'UNKNOWN');

        $this->assertEquals('1,234.56', $result);
    }

    public function testFormatAmountWithNegativeValue()
    {
        $currency = $this->createCurrency([
            'code' => 'CNY',
            'symbol' => '¥',
            'decimals' => 2,
        ]);

        $this->currencyRepositoryMock
            ->shouldReceive('findByCode')
            ->with('CNY')
            ->once()
            ->andReturn($currency);

        $result = $this->currencyService->formatAmount(-500.25, 'CNY');

        $this->assertEquals('¥-500.25', $result);
    }

    public function testFormatAmountWithZeroValue()
    {
        $currency = $this->createCurrency([
            'code' => 'EUR',
            'symbol' => '€',
            'decimals' => 2,
        ]);

        $this->currencyRepositoryMock
            ->shouldReceive('findByCode')
            ->with('EUR')
            ->once()
            ->andReturn($currency);

        $result = $this->currencyService->formatAmount(0, 'EUR');

        $this->assertEquals('€0.00', $result);
    }
}
