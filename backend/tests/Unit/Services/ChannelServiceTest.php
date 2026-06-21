<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ChannelService;
use App\Repositories\ChannelRepository;
use App\Repositories\LocaleRepository;
use App\Repositories\CurrencyRepository;
use App\Models\Channel;
use App\Models\Locale;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Mockery;

class ChannelServiceTest extends TestCase
{
    protected $channelRepository;
    protected $localeRepository;
    protected $currencyRepository;
    protected $channelService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->channelRepository = Mockery::mock(ChannelRepository::class);
        $this->localeRepository = Mockery::mock(LocaleRepository::class);
        $this->currencyRepository = Mockery::mock(CurrencyRepository::class);

        $this->channelService = new ChannelService(
            $this->channelRepository,
            $this->localeRepository,
            $this->currencyRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function makeChannel(array $attributes = []): Channel
    {
        $channel = new Channel();
        foreach ($attributes as $key => $value) {
            $channel->{$key} = $value;
        }
        if (!isset($attributes['exists'])) {
            $channel->exists = true;
        }
        return $channel;
    }

    protected function makeLocale(array $attributes = []): Locale
    {
        $locale = new Locale();
        foreach ($attributes as $key => $value) {
            $locale->{$key} = $value;
        }
        if (!isset($attributes['exists'])) {
            $locale->exists = true;
        }
        return $locale;
    }

    public function testGetAll(): void
    {
        $channels = new Collection([
            $this->makeChannel(['id' => 1, 'code' => 'CH1', 'name' => 'Channel 1']),
            $this->makeChannel(['id' => 2, 'code' => 'CH2', 'name' => 'Channel 2']),
        ]);

        $this->channelRepository
            ->shouldReceive('getAll')
            ->once()
            ->andReturn($channels);

        $result = $this->channelService->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals('CH1', $result[0]->code);
    }

    public function testGetEnabled(): void
    {
        $channels = new Collection([
            $this->makeChannel(['id' => 1, 'code' => 'CH1', 'name' => 'Channel 1', 'is_enabled' => true]),
        ]);

        $this->channelRepository
            ->shouldReceive('getEnabled')
            ->once()
            ->andReturn($channels);

        $result = $this->channelService->getEnabled();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertTrue($result[0]->is_enabled);
    }

    public function testGetById(): void
    {
        $channel = $this->makeChannel(['id' => 1, 'code' => 'CH1', 'name' => 'Channel 1']);

        $this->channelRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($channel);

        $result = $this->channelService->getById(1);

        $this->assertInstanceOf(Channel::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('CH1', $result->code);
    }

    public function testGetByIdThrowsNotFoundException(): void
    {
        $this->channelRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Channel not found');

        $this->channelService->getById(999);
    }

    public function testGetByCode(): void
    {
        $channel = $this->makeChannel(['id' => 1, 'code' => 'CH1', 'name' => 'Channel 1']);

        $this->channelRepository
            ->shouldReceive('findByCode')
            ->once()
            ->with('CH1')
            ->andReturn($channel);

        $result = $this->channelService->getByCode('CH1');

        $this->assertInstanceOf(Channel::class, $result);
        $this->assertEquals('CH1', $result->code);
    }

    public function testGetByCodeThrowsNotFoundException(): void
    {
        $this->channelRepository
            ->shouldReceive('findByCode')
            ->once()
            ->with('INVALID')
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Channel not found');

        $this->channelService->getByCode('INVALID');
    }

    public function testGetChannelLocaleCode(): void
    {
        $this->channelRepository
            ->shouldReceive('getLocaleCode')
            ->once()
            ->with('CH1')
            ->andReturn('zh_CN');

        $result = $this->channelService->getChannelLocaleCode('CH1');

        $this->assertEquals('zh_CN', $result);
    }

    public function testGetChannelLocaleCodeThrowsNotFoundExceptionWhenChannelNotFound(): void
    {
        $this->channelRepository
            ->shouldReceive('getLocaleCode')
            ->once()
            ->with('INVALID')
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Channel or locale not found');

        $this->channelService->getChannelLocaleCode('INVALID');
    }

    public function testGetChannelLocaleCodeThrowsNotFoundExceptionWhenLocaleCodeEmpty(): void
    {
        $this->channelRepository
            ->shouldReceive('getLocaleCode')
            ->once()
            ->with('CH_NO_LOCALE')
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Channel or locale not found');

        $this->channelService->getChannelLocaleCode('CH_NO_LOCALE');
    }

    public function testGetChannelLocale(): void
    {
        $locale = $this->makeLocale(['id' => 1, 'code' => 'zh_CN', 'name' => 'Chinese']);

        $this->channelRepository
            ->shouldReceive('getLocaleCode')
            ->once()
            ->with('CH1')
            ->andReturn('zh_CN');

        $this->localeRepository
            ->shouldReceive('findByCode')
            ->once()
            ->with('zh_CN')
            ->andReturn($locale);

        $result = $this->channelService->getChannelLocale('CH1');

        $this->assertInstanceOf(Locale::class, $result);
        $this->assertEquals('zh_CN', $result->code);
    }

    public function testGetChannelLocaleThrowsNotFoundExceptionWhenChannelOrLocaleNotFound(): void
    {
        $this->channelRepository
            ->shouldReceive('getLocaleCode')
            ->once()
            ->with('INVALID')
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Channel or locale not found');

        $this->channelService->getChannelLocale('INVALID');
    }

    public function testGetChannelLocaleThrowsNotFoundExceptionWhenLocaleNotFound(): void
    {
        $this->channelRepository
            ->shouldReceive('getLocaleCode')
            ->once()
            ->with('CH1')
            ->andReturn('invalid_locale');

        $this->localeRepository
            ->shouldReceive('findByCode')
            ->once()
            ->with('invalid_locale')
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Locale not found');

        $this->channelService->getChannelLocale('CH1');
    }

    public function testGetChannelCurrencyReturnsChannelCurrency(): void
    {
        $currencyInfo = [
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimals' => 2,
        ];

        $this->channelRepository
            ->shouldReceive('getCurrencyInfo')
            ->once()
            ->with('CH1')
            ->andReturn($currencyInfo);

        $result = $this->channelService->getChannelCurrency('CH1');

        $this->assertEquals($currencyInfo, $result);
    }

    public function testGetChannelCurrencyFallsBackToDefaultWhenCurrencyIsNull(): void
    {
        $defaultCurrency = [
            'code' => 'CNY',
            'name' => 'Chinese Yuan',
            'symbol' => '¥',
            'decimals' => 2,
        ];

        $this->channelRepository
            ->shouldReceive('getCurrencyInfo')
            ->once()
            ->with('CH1')
            ->andReturn(null);

        $this->currencyRepository
            ->shouldReceive('getDefaultInfo')
            ->once()
            ->andReturn($defaultCurrency);

        $result = $this->channelService->getChannelCurrency('CH1');

        $this->assertEquals($defaultCurrency, $result);
    }

    public function testGetChannelCurrencyFallsBackToDefaultWhenCodeIsEmpty(): void
    {
        $defaultCurrency = [
            'code' => 'CNY',
            'name' => 'Chinese Yuan',
            'symbol' => '¥',
            'decimals' => 2,
        ];

        $emptyCodeCurrency = [
            'code' => '',
            'name' => '',
            'symbol' => '',
            'decimals' => 2,
        ];

        $this->channelRepository
            ->shouldReceive('getCurrencyInfo')
            ->once()
            ->with('CH1')
            ->andReturn($emptyCodeCurrency);

        $this->currencyRepository
            ->shouldReceive('getDefaultInfo')
            ->once()
            ->andReturn($defaultCurrency);

        $result = $this->channelService->getChannelCurrency('CH1');

        $this->assertEquals($defaultCurrency, $result);
    }

    public function testGetCurrentContextWithoutChannelCode(): void
    {
        $availableLocales = [
            'zh_CN' => ['name' => 'Chinese', 'native' => '中文'],
            'en' => ['name' => 'English', 'native' => 'English'],
        ];

        $availableCurrencies = [
            'CNY' => ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥', 'decimals' => 2],
            'USD' => ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimals' => 2],
        ];

        Config::set('app.default_currency', 'CNY');

        $this->localeRepository
            ->shouldReceive('getAvailableLocales')
            ->once()
            ->andReturn($availableLocales);

        $this->currencyRepository
            ->shouldReceive('getEnabledAsArray')
            ->once()
            ->andReturn($availableCurrencies);

        $result = $this->channelService->getCurrentContext();

        $this->assertArrayHasKey('locales', $result);
        $this->assertArrayHasKey('available', $result['locales']);
        $this->assertEquals($availableLocales, $result['locales']['available']);

        $this->assertArrayHasKey('currencies', $result);
        $this->assertArrayHasKey('available', $result['currencies']);
        $this->assertArrayHasKey('current', $result['currencies']);
        $this->assertEquals($availableCurrencies, $result['currencies']['available']);
        $this->assertEquals($availableCurrencies['CNY'], $result['currencies']['current']);
    }

    public function testGetCurrentContextWithChannelCode(): void
    {
        $availableLocales = [
            'zh_CN' => ['name' => 'Chinese', 'native' => '中文'],
        ];

        $availableCurrencies = [
            'CNY' => ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥', 'decimals' => 2],
            'USD' => ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimals' => 2],
        ];

        $channel = $this->makeChannel([
            'id' => 1,
            'code' => 'CH1',
            'name' => 'Channel 1',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'currency_decimals' => 2,
        ]);

        Config::set('app.default_currency', 'CNY');

        $this->localeRepository
            ->shouldReceive('getAvailableLocales')
            ->once()
            ->andReturn($availableLocales);

        $this->currencyRepository
            ->shouldReceive('getEnabledAsArray')
            ->once()
            ->andReturn($availableCurrencies);

        $this->channelRepository
            ->shouldReceive('findByCode')
            ->once()
            ->with('CH1')
            ->andReturn($channel);

        $result = $this->channelService->getCurrentContext('CH1');

        $this->assertEquals($availableLocales, $result['locales']['available']);
        $this->assertEquals($availableCurrencies, $result['currencies']['available']);
        $this->assertEquals('USD', $result['currencies']['current']['code']);
    }

    public function testGetCurrentContextWithChannelCodeFallsBackWhenCurrencyCodeEmpty(): void
    {
        $availableLocales = [
            'zh_CN' => ['name' => 'Chinese', 'native' => '中文'],
        ];

        $availableCurrencies = [
            'CNY' => ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥', 'decimals' => 2],
        ];

        $channel = $this->makeChannel([
            'id' => 1,
            'code' => 'CH1',
            'name' => 'Channel 1',
            'currency_code' => null,
        ]);

        Config::set('app.default_currency', 'CNY');

        $this->localeRepository
            ->shouldReceive('getAvailableLocales')
            ->once()
            ->andReturn($availableLocales);

        $this->currencyRepository
            ->shouldReceive('getEnabledAsArray')
            ->once()
            ->andReturn($availableCurrencies);

        $this->channelRepository
            ->shouldReceive('findByCode')
            ->once()
            ->with('CH1')
            ->andReturn($channel);

        $result = $this->channelService->getCurrentContext('CH1');

        $this->assertEquals($availableCurrencies['CNY'], $result['currencies']['current']);
    }

    public function testGetCurrentContextUsesFallbackCurrencyWhenDefaultNotInAvailable(): void
    {
        $availableLocales = [];
        $availableCurrencies = [];

        Config::set('app.default_currency', 'EUR');

        $this->localeRepository
            ->shouldReceive('getAvailableLocales')
            ->once()
            ->andReturn($availableLocales);

        $this->currencyRepository
            ->shouldReceive('getEnabledAsArray')
            ->once()
            ->andReturn($availableCurrencies);

        $result = $this->channelService->getCurrentContext();

        $this->assertEquals('EUR', $result['currencies']['current']['code']);
        $this->assertEquals('', $result['currencies']['current']['name']);
        $this->assertEquals(2, $result['currencies']['current']['decimals']);
    }

    public function testCreate(): void
    {
        $data = [
            'code' => 'NEW_CH',
            'name' => 'New Channel',
            'locale_code' => 'zh_CN',
        ];

        $locale = $this->makeLocale(['id' => 1, 'code' => 'zh_CN', 'name' => 'Chinese']);
        $channel = $this->makeChannel(['id' => 1, 'code' => 'NEW_CH', 'name' => 'New Channel']);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) use ($locale, $channel, $data) {
                $this->localeRepository
                    ->shouldReceive('findByCode')
                    ->once()
                    ->with('zh_CN')
                    ->andReturn($locale);

                $this->channelRepository
                    ->shouldReceive('create')
                    ->once()
                    ->with($data, $locale)
                    ->andReturn($channel);

                return $callback();
            });

        $result = $this->channelService->create($data);

        $this->assertInstanceOf(Channel::class, $result);
        $this->assertEquals('NEW_CH', $result->code);
    }

    public function testCreateWithoutLocale(): void
    {
        $data = [
            'code' => 'NEW_CH',
            'name' => 'New Channel',
        ];

        $channel = $this->makeChannel(['id' => 1, 'code' => 'NEW_CH', 'name' => 'New Channel']);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) use ($channel, $data) {
                $this->channelRepository
                    ->shouldReceive('create')
                    ->once()
                    ->with($data, null)
                    ->andReturn($channel);

                return $callback();
            });

        $result = $this->channelService->create($data);

        $this->assertInstanceOf(Channel::class, $result);
    }

    public function testCreateWithEmptyLocaleCode(): void
    {
        $data = [
            'code' => 'NEW_CH',
            'name' => 'New Channel',
            'locale_code' => '',
        ];

        $channel = $this->makeChannel(['id' => 1, 'code' => 'NEW_CH', 'name' => 'New Channel']);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) use ($channel, $data) {
                $this->channelRepository
                    ->shouldReceive('create')
                    ->once()
                    ->with($data, null)
                    ->andReturn($channel);

                return $callback();
            });

        $result = $this->channelService->create($data);

        $this->assertInstanceOf(Channel::class, $result);
    }

    public function testCreateWithNullLocaleCode(): void
    {
        $data = [
            'code' => 'NEW_CH',
            'name' => 'New Channel',
            'locale_code' => null,
        ];

        $channel = $this->makeChannel(['id' => 1, 'code' => 'NEW_CH', 'name' => 'New Channel']);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) use ($channel, $data) {
                $this->channelRepository
                    ->shouldReceive('create')
                    ->once()
                    ->with($data, null)
                    ->andReturn($channel);

                return $callback();
            });

        $result = $this->channelService->create($data);

        $this->assertInstanceOf(Channel::class, $result);
    }

    public function testCreateThrowsBusinessExceptionWhenLocaleNotFound(): void
    {
        $data = [
            'code' => 'NEW_CH',
            'name' => 'New Channel',
            'locale_code' => 'invalid_locale',
        ];

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                $this->localeRepository
                    ->shouldReceive('findByCode')
                    ->once()
                    ->with('invalid_locale')
                    ->andReturn(null);

                return $callback();
            });

        try {
            $this->channelService->create($data);
            $this->fail('Expected BusinessException was not thrown');
        } catch (BusinessException $e) {
            $this->assertEquals('Locale not found: invalid_locale', $e->getMessage());
            $this->assertEquals(400, $e->getStatusCode());
            $this->assertEquals('LOCALE_NOT_FOUND', $e->getErrorCode());
        }
    }

    public function testUpdate(): void
    {
        $id = 1;
        $data = [
            'name' => 'Updated Channel',
        ];

        $channel = $this->makeChannel(['id' => 1, 'code' => 'CH1', 'name' => 'Channel 1']);
        $updatedChannel = $this->makeChannel(['id' => 1, 'code' => 'CH1', 'name' => 'Updated Channel']);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) use ($id, $data, $channel, $updatedChannel) {
                $this->channelRepository
                    ->shouldReceive('findById')
                    ->once()
                    ->with($id)
                    ->andReturn($channel);

                $this->channelRepository
                    ->shouldReceive('update')
                    ->once()
                    ->with($channel, $data, ChannelRepository::NO_CHANGE)
                    ->andReturn($updatedChannel);

                return $callback();
            });

        $result = $this->channelService->update($id, $data);

        $this->assertInstanceOf(Channel::class, $result);
        $this->assertEquals('Updated Channel', $result->name);
    }

    public function testUpdateThrowsNotFoundExceptionWhenChannelNotFound(): void
    {
        $id = 999;
        $data = ['name' => 'Updated'];

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) use ($id) {
                $this->channelRepository
                    ->shouldReceive('findById')
                    ->once()
                    ->with($id)
                    ->andReturn(null);

                return $callback();
            });

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Channel not found');

        $this->channelService->update($id, $data);
    }

    public function testUpdateWithLocaleChange(): void
    {
        $id = 1;
        $data = [
            'name' => 'Updated Channel',
            'locale_code' => 'en',
        ];

        $channel = $this->makeChannel(['id' => 1, 'code' => 'CH1', 'name' => 'Channel 1']);
        $locale = $this->makeLocale(['id' => 2, 'code' => 'en', 'name' => 'English']);
        $updatedChannel = $this->makeChannel(['id' => 1, 'code' => 'CH1', 'name' => 'Updated Channel']);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) use ($id, $data, $channel, $locale, $updatedChannel) {
                $this->channelRepository
                    ->shouldReceive('findById')
                    ->once()
                    ->with($id)
                    ->andReturn($channel);

                $this->localeRepository
                    ->shouldReceive('findByCode')
                    ->once()
                    ->with('en')
                    ->andReturn($locale);

                $this->channelRepository
                    ->shouldReceive('update')
                    ->once()
                    ->with($channel, $data, $locale)
                    ->andReturn($updatedChannel);

                return $callback();
            });

        $result = $this->channelService->update($id, $data);

        $this->assertInstanceOf(Channel::class, $result);
    }

    public function testUpdateWithEmptyLocaleCodeDissociatesLocale(): void
    {
        $id = 1;
        $data = [
            'name' => 'Updated Channel',
            'locale_code' => '',
        ];

        $channel = $this->makeChannel(['id' => 1, 'code' => 'CH1', 'name' => 'Channel 1']);
        $updatedChannel = $this->makeChannel(['id' => 1, 'code' => 'CH1', 'name' => 'Updated Channel']);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) use ($id, $data, $channel, $updatedChannel) {
                $this->channelRepository
                    ->shouldReceive('findById')
                    ->once()
                    ->with($id)
                    ->andReturn($channel);

                $this->channelRepository
                    ->shouldReceive('update')
                    ->once()
                    ->with($channel, $data, null)
                    ->andReturn($updatedChannel);

                return $callback();
            });

        $result = $this->channelService->update($id, $data);

        $this->assertInstanceOf(Channel::class, $result);
    }

    public function testUpdateWithNullLocaleCodeDissociatesLocale(): void
    {
        $id = 1;
        $data = [
            'name' => 'Updated Channel',
            'locale_code' => null,
        ];

        $channel = $this->makeChannel(['id' => 1, 'code' => 'CH1', 'name' => 'Channel 1']);
        $updatedChannel = $this->makeChannel(['id' => 1, 'code' => 'CH1', 'name' => 'Updated Channel']);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) use ($id, $data, $channel, $updatedChannel) {
                $this->channelRepository
                    ->shouldReceive('findById')
                    ->once()
                    ->with($id)
                    ->andReturn($channel);

                $this->channelRepository
                    ->shouldReceive('update')
                    ->once()
                    ->with($channel, $data, null)
                    ->andReturn($updatedChannel);

                return $callback();
            });

        $result = $this->channelService->update($id, $data);

        $this->assertInstanceOf(Channel::class, $result);
    }

    public function testUpdateThrowsBusinessExceptionWhenLocaleNotFound(): void
    {
        $id = 1;
        $data = [
            'name' => 'Updated Channel',
            'locale_code' => 'invalid_locale',
        ];

        $channel = $this->makeChannel(['id' => 1, 'code' => 'CH1', 'name' => 'Channel 1']);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) use ($id, $data, $channel) {
                $this->channelRepository
                    ->shouldReceive('findById')
                    ->once()
                    ->with($id)
                    ->andReturn($channel);

                $this->localeRepository
                    ->shouldReceive('findByCode')
                    ->once()
                    ->with('invalid_locale')
                    ->andReturn(null);

                return $callback();
            });

        try {
            $this->channelService->update($id, $data);
            $this->fail('Expected BusinessException was not thrown');
        } catch (BusinessException $e) {
            $this->assertEquals('Locale not found: invalid_locale', $e->getMessage());
            $this->assertEquals(400, $e->getStatusCode());
            $this->assertEquals('LOCALE_NOT_FOUND', $e->getErrorCode());
        }
    }

    public function testUpdateLocale(): void
    {
        $id = 1;
        $localeCode = 'en';

        $channel = $this->makeChannel(['id' => 1, 'code' => 'CH1', 'name' => 'Channel 1']);
        $locale = $this->makeLocale(['id' => 2, 'code' => 'en', 'name' => 'English']);
        $updatedChannel = $this->makeChannel(['id' => 1, 'code' => 'CH1', 'name' => 'Channel 1']);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) use ($id, $localeCode, $channel, $locale, $updatedChannel) {
                $this->channelRepository
                    ->shouldReceive('findById')
                    ->once()
                    ->with($id)
                    ->andReturn($channel);

                $this->localeRepository
                    ->shouldReceive('findByCode')
                    ->once()
                    ->with($localeCode)
                    ->andReturn($locale);

                $this->channelRepository
                    ->shouldReceive('associateLocale')
                    ->once()
                    ->with($channel, $locale)
                    ->andReturn($updatedChannel);

                return $callback();
            });

        $result = $this->channelService->updateLocale($id, $localeCode);

        $this->assertInstanceOf(Channel::class, $result);
    }

    public function testUpdateLocaleThrowsNotFoundExceptionWhenChannelNotFound(): void
    {
        $id = 999;
        $localeCode = 'en';

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) use ($id) {
                $this->channelRepository
                    ->shouldReceive('findById')
                    ->once()
                    ->with($id)
                    ->andReturn(null);

                return $callback();
            });

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Channel not found');

        $this->channelService->updateLocale($id, $localeCode);
    }

    public function testUpdateLocaleThrowsNotFoundExceptionWhenLocaleNotFound(): void
    {
        $id = 1;
        $localeCode = 'invalid_locale';

        $channel = $this->makeChannel(['id' => 1, 'code' => 'CH1', 'name' => 'Channel 1']);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) use ($id, $localeCode, $channel) {
                $this->channelRepository
                    ->shouldReceive('findById')
                    ->once()
                    ->with($id)
                    ->andReturn($channel);

                $this->localeRepository
                    ->shouldReceive('findByCode')
                    ->once()
                    ->with($localeCode)
                    ->andReturn(null);

                return $callback();
            });

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Locale not found');

        $this->channelService->updateLocale($id, $localeCode);
    }

    public function testDelete(): void
    {
        $id = 1;
        $channel = $this->makeChannel(['id' => 1, 'code' => 'CH1', 'name' => 'Channel 1']);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) use ($id, $channel) {
                $this->channelRepository
                    ->shouldReceive('findById')
                    ->once()
                    ->with($id)
                    ->andReturn($channel);

                $this->channelRepository
                    ->shouldReceive('delete')
                    ->once()
                    ->with($channel);

                return $callback();
            });

        $this->channelService->delete($id);

        $this->assertTrue(true);
    }

    public function testDeleteThrowsNotFoundExceptionWhenChannelNotFound(): void
    {
        $id = 999;

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) use ($id) {
                $this->channelRepository
                    ->shouldReceive('findById')
                    ->once()
                    ->with($id)
                    ->andReturn(null);

                return $callback();
            });

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Channel not found');

        $this->channelService->delete($id);
    }
}
