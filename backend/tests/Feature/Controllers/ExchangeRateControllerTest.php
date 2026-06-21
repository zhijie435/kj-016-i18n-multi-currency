<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Currency;
use App\Models\CurrencyExchangeRate;

class ExchangeRateControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createBaseCurrencies();
    }

    protected function createBaseCurrencies(): void
    {
        Currency::create([
            'code' => 'CNY',
            'name' => '人民币',
            'symbol' => '¥',
            'decimals' => 2,
            'is_enabled' => true,
            'sort_order' => 1,
        ]);
        Currency::create([
            'code' => 'USD',
            'name' => '美元',
            'symbol' => '$',
            'decimals' => 2,
            'is_enabled' => true,
            'sort_order' => 2,
        ]);
        Currency::create([
            'code' => 'EUR',
            'name' => '欧元',
            'symbol' => '€',
            'decimals' => 2,
            'is_enabled' => true,
            'sort_order' => 3,
        ]);
    }

    protected function createExchangeRate(array $attributes = []): CurrencyExchangeRate
    {
        return CurrencyExchangeRate::create(array_merge([
            'from_currency_code' => 'USD',
            'to_currency_code' => 'CNY',
            'rate' => 7.25,
            'effective_date' => null,
            'source' => 'test',
            'is_active' => true,
        ], $attributes));
    }

    protected function withRole(string $role): self
    {
        return $this->withHeader('X-User-Role', $role);
    }

    public function test_index_returns_200_with_admin()
    {
        $this->createExchangeRate();
        $this->createExchangeRate([
            'from_currency_code' => 'EUR',
            'to_currency_code' => 'CNY',
            'rate' => 7.88,
        ]);

        $response = $this->withRole('admin')->getJson('/api/exchange-rates');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_index_returns_200_with_viewer()
    {
        $this->createExchangeRate();

        $response = $this->withRole('viewer')->getJson('/api/exchange-rates');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_active_returns_200()
    {
        $this->createExchangeRate(['is_active' => true]);
        $this->createExchangeRate([
            'from_currency_code' => 'EUR',
            'to_currency_code' => 'USD',
            'rate' => 1.08,
            'is_active' => true,
        ]);
        $this->createExchangeRate([
            'from_currency_code' => 'USD',
            'to_currency_code' => 'EUR',
            'rate' => 0.92,
            'is_active' => false,
        ]);

        $response = $this->withRole('admin')->getJson('/api/exchange-rates/active');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_active_returns_empty_when_no_active()
    {
        $this->createExchangeRate(['is_active' => false]);

        $response = $this->withRole('admin')->getJson('/api/exchange-rates/active');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    public function test_show_returns_200()
    {
        $rate = $this->createExchangeRate();

        $response = $this->withRole('admin')->getJson("/api/exchange-rates/{$rate->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'from_currency_code' => 'USD',
                'to_currency_code' => 'CNY',
                'rate' => 7.25,
            ],
        ]);
    }

    public function test_show_returns_404_when_not_found()
    {
        $response = $this->withRole('admin')->getJson('/api/exchange-rates/999');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'error' => 'NOT_FOUND',
        ]);
    }

    public function test_get_rate_returns_200()
    {
        $this->createExchangeRate();

        $response = $this->withRole('admin')->getJson('/api/exchange-rates/rate?from_currency_code=USD&to_currency_code=CNY');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'from_currency_code' => 'USD',
                'to_currency_code' => 'CNY',
                'rate' => 7.25,
            ],
        ]);
    }

    public function test_get_rate_returns_422_with_invalid_params()
    {
        $response = $this->withRole('admin')->getJson('/api/exchange-rates/rate');

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => 'VALIDATION_ERROR',
        ]);
        $response->assertJsonValidationErrors(['from_currency_code', 'to_currency_code']);
    }

    public function test_convert_same_currency_returns_200()
    {
        $response = $this->withRole('admin')->getJson('/api/exchange-rates/convert?amount=100&from_currency_code=USD&to_currency_code=USD');

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals(100.0, $response->json('converted_amount'));
    }

    public function test_convert_different_currency_returns_200()
    {
        $this->createExchangeRate();

        $response = $this->withRole('admin')->getJson('/api/exchange-rates/convert?amount=100&from_currency_code=USD&to_currency_code=CNY');

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals(725.0, $response->json('converted_amount'));
    }

    public function test_convert_returns_400_when_no_rate()
    {
        $response = $this->withRole('admin')->getJson('/api/exchange-rates/convert?amount=100&from_currency_code=USD&to_currency_code=EUR');

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'error' => 'CONVERSION_FAILED',
        ]);
    }

    public function test_convert_returns_422_with_invalid_params()
    {
        $response = $this->withRole('admin')->getJson('/api/exchange-rates/convert');

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => 'VALIDATION_ERROR',
        ]);
        $response->assertJsonValidationErrors(['amount', 'from_currency_code', 'to_currency_code']);
    }

    public function test_matrix_returns_200()
    {
        $this->createExchangeRate(['from_currency_code' => 'USD', 'to_currency_code' => 'CNY', 'rate' => 7.25]);
        $this->createExchangeRate(['from_currency_code' => 'CNY', 'to_currency_code' => 'USD', 'rate' => 0.138]);

        $response = $this->withRole('admin')->postJson('/api/exchange-rates/matrix', [
            'currency_codes' => ['USD', 'CNY'],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $data = $response->json('data');
        $this->assertArrayHasKey('USD', $data);
        $this->assertArrayHasKey('CNY', $data['USD']);
        $this->assertEquals(1.0, $data['USD']['USD']);
    }

    public function test_matrix_returns_422_with_invalid_params()
    {
        $response = $this->withRole('admin')->postJson('/api/exchange-rates/matrix', []);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => 'VALIDATION_ERROR',
        ]);
        $response->assertJsonValidationErrors(['currency_codes']);
    }

    public function test_store_returns_201_with_admin()
    {
        $data = [
            'from_currency_code' => 'USD',
            'to_currency_code' => 'EUR',
            'rate' => 0.92,
            'effective_date' => now()->toDateString(),
            'source' => 'test',
            'is_active' => true,
        ];

        $response = $this->withRole('admin')->postJson('/api/exchange-rates', $data);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Exchange rate created successfully',
        ]);
        $this->assertDatabaseHas('currency_exchange_rates', [
            'from_currency_code' => 'USD',
            'to_currency_code' => 'EUR',
        ]);
    }

    public function test_store_returns_403_with_viewer()
    {
        $data = [
            'from_currency_code' => 'USD',
            'to_currency_code' => 'EUR',
            'rate' => 0.92,
        ];

        $response = $this->withRole('viewer')->postJson('/api/exchange-rates', $data);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error' => 'FORBIDDEN',
        ]);
    }

    public function test_store_returns_422_with_invalid_data()
    {
        $response = $this->withRole('admin')->postJson('/api/exchange-rates', [
            'from_currency_code' => 'USD',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => 'VALIDATION_ERROR',
        ]);
        $response->assertJsonValidationErrors(['to_currency_code', 'rate']);
    }

    public function test_update_returns_200_with_admin()
    {
        $rate = $this->createExchangeRate(['rate' => 7.25]);

        $response = $this->withRole('admin')->putJson("/api/exchange-rates/{$rate->id}", [
            'rate' => 7.30,
            'source' => 'updated',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Exchange rate updated successfully',
            'data' => [
                'rate' => 7.30,
                'source' => 'updated',
            ],
        ]);
    }

    public function test_update_returns_403_with_viewer()
    {
        $rate = $this->createExchangeRate();

        $response = $this->withRole('viewer')->putJson("/api/exchange-rates/{$rate->id}", [
            'rate' => 7.30,
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error' => 'FORBIDDEN',
        ]);
    }

    public function test_destroy_returns_200_with_admin()
    {
        $rate = $this->createExchangeRate();

        $response = $this->withRole('admin')->deleteJson("/api/exchange-rates/{$rate->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Exchange rate deleted successfully',
        ]);
        $this->assertDatabaseMissing('currency_exchange_rates', ['id' => $rate->id]);
    }

    public function test_destroy_returns_403_with_viewer()
    {
        $rate = $this->createExchangeRate();

        $response = $this->withRole('viewer')->deleteJson("/api/exchange-rates/{$rate->id}");

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error' => 'FORBIDDEN',
        ]);
    }

    public function test_activate_returns_200_with_admin()
    {
        $rate = $this->createExchangeRate(['is_active' => false]);

        $response = $this->withRole('admin')->postJson("/api/exchange-rates/{$rate->id}/activate");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Exchange rate activated successfully',
            'data' => [
                'is_active' => true,
            ],
        ]);
    }

    public function test_activate_returns_403_with_viewer()
    {
        $rate = $this->createExchangeRate(['is_active' => false]);

        $response = $this->withRole('viewer')->postJson("/api/exchange-rates/{$rate->id}/activate");

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error' => 'FORBIDDEN',
        ]);
    }

    public function test_deactivate_returns_200_with_admin()
    {
        $rate = $this->createExchangeRate(['is_active' => true]);

        $response = $this->withRole('admin')->postJson("/api/exchange-rates/{$rate->id}/deactivate");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Exchange rate deactivated successfully',
            'data' => [
                'is_active' => false,
            ],
        ]);
    }

    public function test_deactivate_returns_403_with_viewer()
    {
        $rate = $this->createExchangeRate(['is_active' => true]);

        $response = $this->withRole('viewer')->postJson("/api/exchange-rates/{$rate->id}/deactivate");

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error' => 'FORBIDDEN',
        ]);
    }
}
