<?php

namespace Tests\Feature\Middleware;

use Tests\TestCase;
use App\Models\Channel;
use App\Models\Locale;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SetLocaleMiddlewareTest extends TestCase
{
    use RefreshDatabase;
    protected Locale $zhCN;
    protected Locale $en;
    protected Locale $ptBR;
    protected Channel $cnChannel;
    protected Channel $usChannel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['session']->driver('array')->start();

        $this->zhCN = Locale::create([
            'code' => 'zh_CN',
            'name' => '简体中文',
            'native_name' => '简体中文',
            'flag' => '🇨🇳',
            'element_locale' => 'zh-CN',
            'is_default' => true,
            'is_enabled' => true,
            'sort_order' => 1,
        ]);

        $this->en = Locale::create([
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'flag' => '🇺🇸',
            'element_locale' => 'en',
            'is_default' => false,
            'is_enabled' => true,
            'sort_order' => 2,
        ]);

        $this->ptBR = Locale::create([
            'code' => 'pt_BR',
            'name' => 'Portuguese',
            'native_name' => 'Português',
            'flag' => '🇧🇷',
            'element_locale' => 'pt-br',
            'is_default' => false,
            'is_enabled' => true,
            'sort_order' => 3,
        ]);

        $this->cnChannel = Channel::create([
            'code' => 'cn_main',
            'name' => '中国主站',
            'locale_id' => $this->zhCN->id,
            'currency_code' => 'CNY',
            'currency_symbol' => '¥',
            'currency_decimals' => 2,
            'is_enabled' => true,
            'sort_order' => 1,
        ]);

        $this->usChannel = Channel::create([
            'code' => 'us_main',
            'name' => 'US Main',
            'locale_id' => $this->en->id,
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'currency_decimals' => 2,
            'is_enabled' => true,
            'sort_order' => 2,
        ]);
    }

    protected function apiHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'X-User-Role' => 'viewer',
        ];
    }

    public function testDefaultLocaleIsUsedWhenNoHeadersOrSession(): void
    {
        $headers = array_merge($this->apiHeaders(), [
            'Accept-Language' => 'xx-YY,xx;q=0.9',
        ]);

        $response = $this->withHeaders($headers)
            ->getJson('/api/channels');

        $response->assertStatus(200);
        $this->assertEquals('zh_CN', App::getLocale());
    }

    public function testXAppLocaleHeaderSetsLocale(): void
    {
        $headers = array_merge($this->apiHeaders(), [
            'X-App-Locale' => 'en',
        ]);

        $response = $this->withHeaders($headers)
            ->getJson('/api/channels');

        $response->assertStatus(200);
        $this->assertEquals('en', App::getLocale());
        $this->assertEquals('en', Session::get('locale'));
    }

    public function testXAppLocaleHeaderSetsPtBRLocale(): void
    {
        $headers = array_merge($this->apiHeaders(), [
            'X-App-Locale' => 'pt_BR',
        ]);

        $response = $this->withHeaders($headers)
            ->getJson('/api/channels');

        $response->assertStatus(200);
        $this->assertEquals('pt_BR', App::getLocale());
        $this->assertEquals('pt_BR', Session::get('locale'));
    }

    public function testXChannelCodeTakesPriorityOverXAppLocale(): void
    {
        $headers = array_merge($this->apiHeaders(), [
            'X-App-Locale' => 'en',
            'X-Channel-Code' => 'cn_main',
        ]);

        $response = $this->withHeaders($headers)
            ->getJson('/api/channels');

        $response->assertStatus(200);
        $this->assertEquals('zh_CN', App::getLocale());
    }

    public function testXChannelCodeUSSetsEnglishLocale(): void
    {
        $headers = array_merge($this->apiHeaders(), [
            'X-Channel-Code' => 'us_main',
        ]);

        $response = $this->withHeaders($headers)
            ->getJson('/api/channels');

        $response->assertStatus(200);
        $this->assertEquals('en', App::getLocale());
    }

    public function testInputLocaleParameterSetsLocale(): void
    {
        $response = $this->withHeaders($this->apiHeaders())
            ->getJson('/api/channels?locale=pt_BR');

        $response->assertStatus(200);
        $this->assertEquals('pt_BR', App::getLocale());
        $this->assertEquals('pt_BR', Session::get('locale'));
    }

    public function testInputLocaleHasLowerPriorityThanXAppLocale(): void
    {
        $headers = array_merge($this->apiHeaders(), [
            'X-App-Locale' => 'en',
        ]);

        $response = $this->withHeaders($headers)
            ->getJson('/api/channels?locale=pt_BR');

        $response->assertStatus(200);
        $this->assertEquals('en', App::getLocale());
    }

    public function testSessionPersistsLocale(): void
    {
        Session::put('locale', 'pt_BR');

        $response = $this->withHeaders($this->apiHeaders())
            ->getJson('/api/channels');

        $response->assertStatus(200);
        $this->assertEquals('pt_BR', App::getLocale());
    }

    public function testSessionHasLowerPriorityThanInputLocale(): void
    {
        Session::put('locale', 'pt_BR');

        $response = $this->withHeaders($this->apiHeaders())
            ->getJson('/api/channels?locale=en');

        $response->assertStatus(200);
        $this->assertEquals('en', App::getLocale());
    }

    public function testInvalidLocaleFallsBackToDefault(): void
    {
        $headers = array_merge($this->apiHeaders(), [
            'X-App-Locale' => 'invalid_locale_xyz',
            'Accept-Language' => 'xx-YY,xx;q=0.9',
        ]);

        $response = $this->withHeaders($headers)
            ->getJson('/api/channels');

        $response->assertStatus(200);
        $this->assertEquals('zh_CN', App::getLocale());
    }

    public function testInvalidInputLocaleFallsBackToSession(): void
    {
        Session::put('locale', 'en');

        $response = $this->withHeaders($this->apiHeaders())
            ->getJson('/api/channels?locale=invalid_locale');

        $response->assertStatus(200);
        $this->assertEquals('en', App::getLocale());
    }

    public function testChannelCurrencyIsSetViaXChannelCode(): void
    {
        $headers = array_merge($this->apiHeaders(), [
            'X-Channel-Code' => 'us_main',
        ]);

        $response = $this->withHeaders($headers)
            ->getJson('/api/channels');

        $response->assertStatus(200);

        $currency = Config::get('app.current_currency');
        $this->assertIsArray($currency);
        $this->assertEquals('USD', $currency['code']);
        $this->assertEquals('$', $currency['symbol']);

        $sessionCurrency = Session::get('currency');
        $this->assertIsArray($sessionCurrency);
        $this->assertEquals('USD', $sessionCurrency['code']);
    }

    public function testCNChannelCurrencyIsSet(): void
    {
        $headers = array_merge($this->apiHeaders(), [
            'X-Channel-Code' => 'cn_main',
        ]);

        $response = $this->withHeaders($headers)
            ->getJson('/api/channels');

        $response->assertStatus(200);

        $currency = Config::get('app.current_currency');
        $this->assertIsArray($currency);
        $this->assertEquals('CNY', $currency['code']);
        $this->assertEquals('¥', $currency['symbol']);
    }

    public function testSessionCurrencyIsUsedWhenNoChannelCode(): void
    {
        $mockCurrency = [
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => '€',
            'decimals' => 2,
        ];
        Session::put('currency', $mockCurrency);

        $response = $this->withHeaders($this->apiHeaders())
            ->getJson('/api/channels');

        $response->assertStatus(200);

        $currency = Config::get('app.current_currency');
        $this->assertIsArray($currency);
        $this->assertEquals('EUR', $currency['code']);
    }

    public function testBrowserPreferredLanguageIsUsed(): void
    {
        $headers = array_merge($this->apiHeaders(), [
            'Accept-Language' => 'en-US,en;q=0.9',
        ]);

        $response = $this->withHeaders($headers)
            ->getJson('/api/channels');

        $response->assertStatus(200);
        $this->assertEquals('en', App::getLocale());
    }

    public function testInvalidChannelCodeFallsBackGracefully(): void
    {
        $headers = array_merge($this->apiHeaders(), [
            'X-App-Locale' => 'pt_BR',
            'X-Channel-Code' => 'non_existent_channel',
        ]);

        $response = $this->withHeaders($headers)
            ->getJson('/api/channels');

        $response->assertStatus(200);
        $this->assertEquals('pt_BR', App::getLocale());
    }
}
