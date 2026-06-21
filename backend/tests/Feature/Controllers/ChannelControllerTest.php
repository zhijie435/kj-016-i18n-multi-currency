<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\Channel;
use App\Models\Locale;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChannelControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Locale $zhCN;
    protected Locale $en;
    protected Channel $channel;

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

        $this->channel = Channel::create([
            'code' => 'cn_main',
            'name' => '中国主站',
            'description' => '中国大陆地区主站渠道',
            'locale_id' => $this->zhCN->id,
            'currency_code' => 'CNY',
            'currency_symbol' => '¥',
            'currency_decimals' => 2,
            'is_enabled' => true,
            'sort_order' => 1,
        ]);
    }

    protected function viewerHeaders(): array
    {
        return [
            'X-User-Role' => 'viewer',
            'Accept' => 'application/json',
        ];
    }

    protected function adminHeaders(): array
    {
        return [
            'X-User-Role' => 'admin',
            'Accept' => 'application/json',
        ];
    }

    public function testIndexAsViewer(): void
    {
        $response = $this->withHeaders($this->viewerHeaders())
            ->getJson('/api/channels');

        $response->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonCount(1, 'data');
    }

    public function testEnabledAsViewer(): void
    {
        Channel::create([
            'code' => 'disabled_ch',
            'name' => 'Disabled Channel',
            'is_enabled' => false,
            'sort_order' => 99,
        ]);

        $response = $this->withHeaders($this->viewerHeaders())
            ->getJson('/api/channels/enabled');

        $response->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonCount(1, 'data');

        $this->assertTrue($response->json('data.0.is_enabled'));
    }

    public function testShowAsViewer(): void
    {
        $response = $this->withHeaders($this->viewerHeaders())
            ->getJson('/api/channels/' . $this->channel->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->channel->id)
            ->assertJsonPath('data.code', 'cn_main')
            ->assertJsonPath('data.name', '中国主站');
    }

    public function testShowNotFound(): void
    {
        $response = $this->withHeaders($this->viewerHeaders())
            ->getJson('/api/channels/9999');

        $response->assertStatus(404)
            ->assertJsonPath('error', 'NOT_FOUND');
    }

    public function testGetChannelLocaleAsViewer(): void
    {
        $response = $this->withHeaders($this->viewerHeaders())
            ->getJson('/api/channels/cn_main/locale');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'zh_CN');
    }

    public function testGetChannelLocaleNotFound(): void
    {
        $response = $this->withHeaders($this->viewerHeaders())
            ->getJson('/api/channels/invalid_code/locale');

        $response->assertStatus(404);
    }

    public function testGetChannelCurrencyAsViewer(): void
    {
        $response = $this->withHeaders($this->viewerHeaders())
            ->getJson('/api/channels/cn_main/currency');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'CNY')
            ->assertJsonPath('data.symbol', '¥');
    }

    public function testGetChannelCurrencyNotFoundFallsBack(): void
    {
        $channelWithoutCurrency = Channel::create([
            'code' => 'no_currency_ch',
            'name' => 'No Currency Channel',
            'is_enabled' => true,
            'sort_order' => 10,
        ]);

        $response = $this->withHeaders($this->viewerHeaders())
            ->getJson('/api/channels/no_currency_ch/currency');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function testStoreAsAdmin(): void
    {
        $data = [
            'code' => 'new_channel',
            'name' => 'New Channel',
            'description' => 'Test description',
            'locale_code' => 'en',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'currency_decimals' => 2,
            'is_enabled' => true,
            'sort_order' => 10,
        ];

        $response = $this->withHeaders($this->adminHeaders())
            ->postJson('/api/channels', $data);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'new_channel')
            ->assertJsonPath('data.name', 'New Channel');

        $this->assertDatabaseHas('channels', ['code' => 'new_channel']);
    }

    public function testStoreAsViewerForbidden(): void
    {
        $data = [
            'code' => 'forbidden_ch',
            'name' => 'Forbidden Channel',
        ];

        $response = $this->withHeaders($this->viewerHeaders())
            ->postJson('/api/channels', $data);

        $response->assertStatus(403)
            ->assertJsonPath('error', 'FORBIDDEN');
    }

    public function testStoreValidationError(): void
    {
        $data = [
            'name' => 'Missing Code',
        ];

        $response = $this->withHeaders($this->adminHeaders())
            ->postJson('/api/channels', $data);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['code']);
    }

    public function testUpdateAsAdmin(): void
    {
        $data = [
            'name' => 'Updated Channel Name',
            'description' => 'Updated description',
        ];

        $response = $this->withHeaders($this->adminHeaders())
            ->putJson('/api/channels/' . $this->channel->id, $data);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Channel Name');

        $this->assertDatabaseHas('channels', [
            'id' => $this->channel->id,
            'name' => 'Updated Channel Name',
        ]);
    }

    public function testUpdateAsViewerForbidden(): void
    {
        $data = ['name' => 'Should Fail'];

        $response = $this->withHeaders($this->viewerHeaders())
            ->putJson('/api/channels/' . $this->channel->id, $data);

        $response->assertStatus(403)
            ->assertJsonPath('error', 'FORBIDDEN');
    }

    public function testUpdateNotFound(): void
    {
        $data = ['name' => 'Not Found'];

        $response = $this->withHeaders($this->adminHeaders())
            ->putJson('/api/channels/9999', $data);

        $response->assertStatus(404)
            ->assertJsonPath('error', 'NOT_FOUND');
    }

    public function testUpdateLocaleAsAdmin(): void
    {
        $data = [
            'locale_code' => 'en',
        ];

        $response = $this->withHeaders($this->adminHeaders())
            ->putJson('/api/channels/' . $this->channel->id . '/locale', $data);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Channel locale updated successfully');

        $this->channel->refresh();
        $this->assertEquals($this->en->id, $this->channel->locale_id);
    }

    public function testUpdateLocaleAsViewerForbidden(): void
    {
        $data = ['locale_code' => 'en'];

        $response = $this->withHeaders($this->viewerHeaders())
            ->putJson('/api/channels/' . $this->channel->id . '/locale', $data);

        $response->assertStatus(403)
            ->assertJsonPath('error', 'FORBIDDEN');
    }

    public function testUpdateLocaleNotFound(): void
    {
        $data = ['locale_code' => 'en'];

        $response = $this->withHeaders($this->adminHeaders())
            ->putJson('/api/channels/9999/locale', $data);

        $response->assertStatus(404);
    }

    public function testUpdateLocaleValidationError(): void
    {
        $data = [];

        $response = $this->withHeaders($this->adminHeaders())
            ->putJson('/api/channels/' . $this->channel->id . '/locale', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['locale_code']);
    }

    public function testDestroyAsAdmin(): void
    {
        $response = $this->withHeaders($this->adminHeaders())
            ->deleteJson('/api/channels/' . $this->channel->id);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Channel deleted successfully');

        $this->assertDatabaseMissing('channels', ['id' => $this->channel->id]);
    }

    public function testDestroyAsViewerForbidden(): void
    {
        $response = $this->withHeaders($this->viewerHeaders())
            ->deleteJson('/api/channels/' . $this->channel->id);

        $response->assertStatus(403)
            ->assertJsonPath('error', 'FORBIDDEN');
    }

    public function testDestroyNotFound(): void
    {
        $response = $this->withHeaders($this->adminHeaders())
            ->deleteJson('/api/channels/9999');

        $response->assertStatus(404)
            ->assertJsonPath('error', 'NOT_FOUND');
    }
}
