<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Mockery;
use App\Services\LocaleService;
use App\Repositories\LocaleRepository;
use App\Repositories\ChannelRepository;
use App\Models\Locale;
use App\Models\Channel;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LocaleServiceTest extends TestCase
{
    protected $localeRepository;
    protected $channelRepository;
    protected $localeService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->localeRepository = Mockery::mock(LocaleRepository::class);
        $this->channelRepository = Mockery::mock(ChannelRepository::class);
        $this->localeService = new LocaleService($this->localeRepository, $this->channelRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function makeLocale(array $attributes = []): Locale
    {
        $defaults = [
            'id' => 1,
            'code' => 'zh_CN',
            'name' => 'Chinese',
            'native_name' => '中文',
            'is_default' => false,
            'is_enabled' => true,
            'sort_order' => 0,
        ];
        $locale = new Locale();
        $locale->forceFill(array_merge($defaults, $attributes));
        return $locale;
    }

    protected function makeMockLocale(array $attributes = [])
    {
        $defaults = [
            'id' => 1,
            'code' => 'zh_CN',
            'name' => 'Chinese',
            'native_name' => '中文',
            'is_default' => false,
            'is_enabled' => true,
            'sort_order' => 0,
        ];
        $merged = array_merge($defaults, $attributes);
        $locale = Mockery::mock(Locale::class)->makePartial();
        $locale->forceFill($merged);
        $locale->shouldReceive('toArray')->andReturn($merged);
        return $locale;
    }

    public function test_get_all_returns_all_locales()
    {
        $locales = new Collection([
            $this->makeLocale(['id' => 1, 'code' => 'zh_CN']),
            $this->makeLocale(['id' => 2, 'code' => 'en']),
        ]);

        $this->localeRepository
            ->shouldReceive('getAll')
            ->once()
            ->andReturn($locales);

        $result = $this->localeService->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals('zh_CN', $result->first()->code);
    }

    public function test_get_all_returns_empty_collection()
    {
        $this->localeRepository
            ->shouldReceive('getAll')
            ->once()
            ->andReturn(new Collection());

        $result = $this->localeService->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEmpty($result);
    }

    public function test_get_enabled_returns_enabled_locales()
    {
        $locales = new Collection([
            $this->makeLocale(['id' => 1, 'code' => 'zh_CN', 'is_enabled' => true]),
        ]);

        $this->localeRepository
            ->shouldReceive('getEnabled')
            ->once()
            ->andReturn($locales);

        $result = $this->localeService->getEnabled();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is_enabled);
    }

    public function test_get_enabled_returns_empty_collection()
    {
        $this->localeRepository
            ->shouldReceive('getEnabled')
            ->once()
            ->andReturn(new Collection());

        $result = $this->localeService->getEnabled();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEmpty($result);
    }

    public function test_get_available_locales_returns_array()
    {
        $expected = [
            'zh_CN' => ['name' => 'Chinese', 'native' => '中文'],
            'en' => ['name' => 'English', 'native' => 'English'],
        ];

        $this->localeRepository
            ->shouldReceive('getAvailableLocales')
            ->once()
            ->andReturn($expected);

        $result = $this->localeService->getAvailableLocales();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals($expected, $result);
    }

    public function test_get_available_locales_returns_empty_array()
    {
        $this->localeRepository
            ->shouldReceive('getAvailableLocales')
            ->once()
            ->andReturn([]);

        $result = $this->localeService->getAvailableLocales();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_get_available_codes_returns_array()
    {
        $expected = ['zh_CN', 'en', 'pt_BR'];

        $this->localeRepository
            ->shouldReceive('getAvailableCodes')
            ->once()
            ->andReturn($expected);

        $result = $this->localeService->getAvailableCodes();

        $this->assertIsArray($result);
        $this->assertEquals($expected, $result);
    }

    public function test_get_available_codes_returns_empty_array()
    {
        $this->localeRepository
            ->shouldReceive('getAvailableCodes')
            ->once()
            ->andReturn([]);

        $result = $this->localeService->getAvailableCodes();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_get_default_code_returns_string()
    {
        $this->localeRepository
            ->shouldReceive('getDefaultCode')
            ->once()
            ->andReturn('zh_CN');

        $result = $this->localeService->getDefaultCode();

        $this->assertIsString($result);
        $this->assertEquals('zh_CN', $result);
    }

    public function test_get_default_code_returns_other_locale()
    {
        $this->localeRepository
            ->shouldReceive('getDefaultCode')
            ->once()
            ->andReturn('en');

        $result = $this->localeService->getDefaultCode();

        $this->assertEquals('en', $result);
    }

    public function test_find_by_code_returns_locale()
    {
        $locale = $this->makeLocale(['code' => 'zh_CN']);

        $this->localeRepository
            ->shouldReceive('findByCode')
            ->once()
            ->with('zh_CN')
            ->andReturn($locale);

        $result = $this->localeService->findByCode('zh_CN');

        $this->assertInstanceOf(Locale::class, $result);
        $this->assertEquals('zh_CN', $result->code);
    }

    public function test_find_by_code_returns_null_for_invalid_code()
    {
        $this->localeRepository
            ->shouldReceive('findByCode')
            ->once()
            ->with('invalid_code')
            ->andReturn(null);

        $result = $this->localeService->findByCode('invalid_code');

        $this->assertNull($result);
    }

    public function test_get_by_id_returns_locale()
    {
        $locale = $this->makeLocale(['id' => 1]);

        $this->localeRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($locale);

        $result = $this->localeService->getById(1);

        $this->assertInstanceOf(Locale::class, $result);
        $this->assertEquals(1, $result->id);
    }

    public function test_get_by_id_throws_not_found_exception()
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Locale not found');

        $this->localeRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->localeService->getById(999);
    }

    public function test_get_by_id_throws_exception_with_zero_id()
    {
        $this->expectException(NotFoundException::class);

        $this->localeRepository
            ->shouldReceive('findById')
            ->once()
            ->with(0)
            ->andReturn(null);

        $this->localeService->getById(0);
    }

    public function test_validate_code_returns_code_for_valid_code()
    {
        $this->localeRepository
            ->shouldReceive('getAvailableCodes')
            ->once()
            ->andReturn(['zh_CN', 'en']);

        $result = $this->localeService->validateCode('zh_CN');

        $this->assertEquals('zh_CN', $result);
    }

    public function test_validate_code_throws_business_exception_for_invalid_code()
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Unsupported locale');

        $this->localeRepository
            ->shouldReceive('getAvailableCodes')
            ->once()
            ->andReturn(['zh_CN', 'en']);

        $this->localeService->validateCode('invalid');
    }

    public function test_validate_code_throws_exception_with_correct_error_code()
    {
        $this->localeRepository
            ->shouldReceive('getAvailableCodes')
            ->once()
            ->andReturn(['zh_CN', 'en']);

        try {
            $this->localeService->validateCode('xx');
            $this->fail('Expected BusinessException was not thrown');
        } catch (BusinessException $e) {
            $this->assertEquals('UNSUPPORTED_LOCALE', $e->getErrorCode());
            $this->assertEquals(400, $e->getStatusCode());
        }
    }

    public function test_create_locale_without_default()
    {
        $data = [
            'code' => 'fr',
            'name' => 'French',
            'native_name' => 'Français',
            'is_enabled' => true,
        ];
        $createdLocale = $this->makeLocale($data + ['id' => 3]);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) use ($createdLocale) {
                return $callback();
            });

        $this->localeRepository
            ->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn($createdLocale);

        $result = $this->localeService->create($data);

        $this->assertInstanceOf(Locale::class, $result);
        $this->assertEquals('fr', $result->code);
    }

    public function test_create_locale_as_default_calls_unset_default_except()
    {
        $data = [
            'code' => 'fr',
            'name' => 'French',
            'is_default' => true,
        ];
        $createdLocale = $this->makeLocale($data + ['id' => 3]);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) use ($createdLocale) {
                return $callback();
            });

        $this->localeRepository
            ->shouldReceive('unsetDefaultExcept')
            ->once()
            ->withNoArgs();

        $this->localeRepository
            ->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn($createdLocale);

        $result = $this->localeService->create($data);

        $this->assertInstanceOf(Locale::class, $result);
        $this->assertTrue($result->is_default);
    }

    public function test_create_locale_with_is_default_false_does_not_call_unset()
    {
        $data = [
            'code' => 'fr',
            'name' => 'French',
            'is_default' => false,
        ];
        $createdLocale = $this->makeLocale($data + ['id' => 3]);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) use ($createdLocale) {
                return $callback();
            });

        $this->localeRepository
            ->shouldReceive('unsetDefaultExcept')
            ->never();

        $this->localeRepository
            ->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn($createdLocale);

        $result = $this->localeService->create($data);

        $this->assertInstanceOf(Locale::class, $result);
    }

    public function test_update_locale_without_default_change()
    {
        $locale = $this->makeMockLocale(['id' => 1, 'code' => 'zh_CN']);
        $data = ['name' => 'Updated Chinese'];
        $updatedLocale = $this->makeLocale(array_merge($locale->toArray(), $data));

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->localeRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($locale);

        $this->localeRepository
            ->shouldReceive('unsetDefaultExcept')
            ->never();

        $this->localeRepository
            ->shouldReceive('update')
            ->once()
            ->with($locale, $data)
            ->andReturn(true);

        $locale->shouldReceive('fresh')
            ->once()
            ->andReturn($updatedLocale);

        $result = $this->localeService->update(1, $data);

        $this->assertInstanceOf(Locale::class, $result);
        $this->assertEquals('Updated Chinese', $result->name);
    }

    public function test_update_locale_as_default_calls_unset_default_except()
    {
        $locale = $this->makeMockLocale(['id' => 1, 'code' => 'zh_CN', 'is_default' => false]);
        $data = ['is_default' => true];
        $updatedLocale = $this->makeLocale(array_merge($locale->toArray(), ['is_default' => true]));

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->localeRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($locale);

        $this->localeRepository
            ->shouldReceive('unsetDefaultExcept')
            ->once()
            ->with(1);

        $this->localeRepository
            ->shouldReceive('update')
            ->once()
            ->with($locale, $data)
            ->andReturn(true);

        $locale->shouldReceive('fresh')
            ->once()
            ->andReturn($updatedLocale);

        $result = $this->localeService->update(1, $data);

        $this->assertTrue($result->is_default);
    }

    public function test_update_locale_throws_not_found_exception()
    {
        $this->expectException(NotFoundException::class);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->localeRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->localeService->update(999, ['name' => 'Test']);
    }

    public function test_delete_locale_successfully()
    {
        $locale = $this->makeMockLocale(['id' => 1, 'code' => 'en', 'is_default' => false]);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->localeRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($locale);

        $this->channelRepository
            ->shouldReceive('clearLocaleForLocale')
            ->once()
            ->with(1)
            ->andReturn(0);

        $this->localeRepository
            ->shouldReceive('delete')
            ->once()
            ->with($locale)
            ->andReturn(true);

        $this->localeService->delete(1);

        $this->assertTrue(true);
    }

    public function test_delete_default_locale_throws_exception()
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Cannot delete the default locale');

        $locale = $this->makeMockLocale(['id' => 1, 'code' => 'zh_CN', 'is_default' => true]);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->localeRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($locale);

        $this->localeRepository
            ->shouldReceive('delete')
            ->never();

        $this->localeService->delete(1);
    }

    public function test_delete_locale_throws_not_found_exception()
    {
        $this->expectException(NotFoundException::class);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->localeRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->localeService->delete(999);
    }

    public function test_delete_default_locale_exception_has_correct_error_code()
    {
        $locale = $this->makeMockLocale(['id' => 1, 'is_default' => true]);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->localeRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($locale);

        try {
            $this->localeService->delete(1);
            $this->fail('Expected BusinessException was not thrown');
        } catch (BusinessException $e) {
            $this->assertEquals('CANNOT_DELETE_DEFAULT', $e->getErrorCode());
            $this->assertEquals(400, $e->getStatusCode());
        }
    }
}
