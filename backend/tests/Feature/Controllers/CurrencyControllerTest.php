<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Currency;

class CurrencyControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function createCurrency(array $attributes = []): Currency
    {
        return Currency::create(array_merge([
            'code' => 'USD',
            'name' => '美元',
            'symbol' => '$',
            'decimals' => 2,
            'is_enabled' => true,
            'sort_order' => 1,
        ], $attributes));
    }

    protected function withRole(string $role): self
    {
        return $this->withHeader('X-User-Role', $role);
    }

    public function test_index_returns_200_with_admin()
    {
        $this->createCurrency(['code' => 'CNY', 'name' => '人民币']);
        $this->createCurrency(['code' => 'USD', 'name' => '美元']);

        $response = $this->withRole('admin')->getJson('/api/currencies');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_index_returns_200_with_viewer()
    {
        $this->createCurrency(['code' => 'CNY', 'name' => '人民币']);

        $response = $this->withRole('viewer')->getJson('/api/currencies');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_enabled_returns_200()
    {
        $this->createCurrency(['code' => 'CNY', 'name' => '人民币', 'is_enabled' => true]);
        $this->createCurrency(['code' => 'USD', 'name' => '美元', 'is_enabled' => true]);
        $this->createCurrency(['code' => 'JPY', 'name' => '日元', 'is_enabled' => false]);

        $response = $this->withRole('admin')->getJson('/api/currencies/enabled');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    public function test_enabled_returns_empty_when_no_enabled()
    {
        $this->createCurrency(['code' => 'JPY', 'name' => '日元', 'is_enabled' => false]);

        $response = $this->withRole('admin')->getJson('/api/currencies/enabled');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    public function test_show_returns_200()
    {
        $currency = $this->createCurrency(['code' => 'USD', 'name' => '美元']);

        $response = $this->withRole('admin')->getJson('/api/currencies/USD');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'code' => 'USD',
                'name' => '美元',
            ],
        ]);
    }

    public function test_show_returns_404_when_not_found()
    {
        $response = $this->withRole('admin')->getJson('/api/currencies/INVALID');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'error' => 'NOT_FOUND',
        ]);
    }

    public function test_store_returns_201_with_admin()
    {
        $data = [
            'code' => 'JPY',
            'name' => '日元',
            'symbol' => '¥',
            'decimals' => 0,
            'is_enabled' => true,
        ];

        $response = $this->withRole('admin')->postJson('/api/currencies', $data);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Currency created successfully',
            'data' => [
                'code' => 'JPY',
                'name' => '日元',
            ],
        ]);
        $this->assertDatabaseHas('currencies', ['code' => 'JPY']);
    }

    public function test_store_returns_403_with_viewer()
    {
        $data = [
            'code' => 'JPY',
            'name' => '日元',
        ];

        $response = $this->withRole('viewer')->postJson('/api/currencies', $data);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error' => 'FORBIDDEN',
        ]);
    }

    public function test_store_returns_422_with_invalid_data()
    {
        $response = $this->withRole('admin')->postJson('/api/currencies', [
            'name' => '测试',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => 'VALIDATION_ERROR',
        ]);
        $response->assertJsonValidationErrors(['code']);
    }

    public function test_update_returns_200_with_admin()
    {
        $currency = $this->createCurrency(['code' => 'USD', 'name' => '美元']);

        $response = $this->withRole('admin')->putJson("/api/currencies/{$currency->id}", [
            'name' => '美国美元',
            'decimals' => 4,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Currency updated successfully',
            'data' => [
                'name' => '美国美元',
                'decimals' => 4,
            ],
        ]);
    }

    public function test_update_returns_403_with_viewer()
    {
        $currency = $this->createCurrency();

        $response = $this->withRole('viewer')->putJson("/api/currencies/{$currency->id}", [
            'name' => '测试',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error' => 'FORBIDDEN',
        ]);
    }

    public function test_destroy_returns_200_with_admin()
    {
        $currency = $this->createCurrency(['code' => 'JPY', 'name' => '日元']);

        $response = $this->withRole('admin')->deleteJson("/api/currencies/{$currency->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Currency deleted successfully',
        ]);
        $this->assertDatabaseMissing('currencies', ['id' => $currency->id]);
    }

    public function test_destroy_returns_400_when_deleting_default_currency()
    {
        $defaultCurrency = $this->createCurrency(['code' => 'CNY', 'name' => '人民币']);

        $response = $this->withRole('admin')->deleteJson("/api/currencies/{$defaultCurrency->id}");

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'error' => 'CANNOT_DELETE_DEFAULT',
        ]);
    }

    public function test_destroy_returns_403_with_viewer()
    {
        $currency = $this->createCurrency();

        $response = $this->withRole('viewer')->deleteJson("/api/currencies/{$currency->id}");

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error' => 'FORBIDDEN',
        ]);
    }
}
