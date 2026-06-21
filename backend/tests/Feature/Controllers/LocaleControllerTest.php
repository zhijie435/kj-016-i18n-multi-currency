<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\Locale;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LocaleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLocales();
        $this->seedCurrencies();
    }

    protected function seedLocales(): void
    {
        Locale::create([
            'code' => 'zh_CN',
            'name' => '简体中文',
            'native_name' => '简体中文',
            'flag' => '🇨🇳',
            'element_locale' => 'zh-CN',
            'is_default' => true,
            'is_enabled' => true,
            'sort_order' => 1,
        ]);

        Locale::create([
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'flag' => '🇺🇸',
            'element_locale' => 'en',
            'is_default' => false,
            'is_enabled' => true,
            'sort_order' => 2,
        ]);

        Locale::create([
            'code' => 'pt_BR',
            'name' => 'Portuguese',
            'native_name' => 'Português',
            'flag' => '🇧🇷',
            'element_locale' => 'pt-br',
            'is_default' => false,
            'is_enabled' => true,
            'sort_order' => 3,
        ]);
    }

    protected function seedCurrencies(): void
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

    protected function adminHeaders(): array
    {
        return ['X-User-Role' => 'admin'];
    }

    protected function viewerHeaders(): array
    {
        return ['X-User-Role' => 'viewer'];
    }

    public function test_index_returns_current_available_and_currency_with_admin_role()
    {
        $response = $this->withHeaders($this->adminHeaders())->getJson('/api/locale');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'current',
            'available',
            'currency' => [
                'current',
                'available',
            ],
        ]);
        $current = $response->json('current');
        $available = $response->json('available');
        $this->assertIsString($current);
        $this->assertIsArray($available);
        $this->assertNotEmpty($available);
    }

    public function test_index_accessible_with_viewer_role()
    {
        $response = $this->withHeaders($this->viewerHeaders())->getJson('/api/locale');

        $response->assertStatus(200);
        $response->assertJsonStructure(['current', 'available', 'currency']);
    }

    public function test_show_returns_messages_for_valid_locale()
    {
        $response = $this->withHeaders($this->adminHeaders())->getJson('/api/locale/en');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'locale',
            'messages' => [
                'auth',
                'pagination',
                'passwords',
                'validation',
                'common',
                'menu',
                'packages',
            ],
        ]);
        $response->assertJsonPath('locale', 'en');
    }

    public function test_show_throws_business_exception_for_invalid_locale()
    {
        $response = $this->withHeaders($this->adminHeaders())->getJson('/api/locale/invalid_code');

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'error' => 'UNSUPPORTED_LOCALE',
            'message' => 'Unsupported locale',
            'status_code' => 400,
        ]);
    }

    public function test_update_locale_preference_success()
    {
        $response = $this->withHeaders($this->adminHeaders())->postJson('/api/locale', [
            'locale' => 'en',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'locale' => 'en',
            'message' => 'Locale updated successfully',
        ]);
        $this->assertEquals('en', session('locale'));
    }

    public function test_update_locale_preference_validation_fails_without_locale()
    {
        $response = $this->withHeaders($this->adminHeaders())->postJson('/api/locale', []);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => 'VALIDATION_ERROR',
            'status_code' => 422,
        ]);
    }

    public function test_update_locale_preference_validation_fails_with_invalid_locale_type()
    {
        $response = $this->withHeaders($this->adminHeaders())->postJson('/api/locale', [
            'locale' => 123,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['status_code' => 422]);
    }

    public function test_all_returns_all_locales()
    {
        $response = $this->withHeaders($this->adminHeaders())->getJson('/api/locales/all');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    public function test_store_creates_locale_with_admin_role()
    {
        $response = $this->withHeaders($this->adminHeaders())->postJson('/api/locales', [
            'code' => 'fr',
            'name' => 'French',
            'native_name' => 'Français',
            'flag' => '🇫🇷',
            'element_locale' => 'fr',
            'is_default' => false,
            'is_enabled' => true,
            'sort_order' => 5,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Locale created successfully',
        ]);
        $response->assertJsonStructure(['data' => ['id', 'code', 'name', 'native_name']]);
        $this->assertDatabaseHas('locales', ['code' => 'fr']);
    }

    public function test_store_validation_fails_with_missing_required_fields()
    {
        $response = $this->withHeaders($this->adminHeaders())->postJson('/api/locales', []);

        $response->assertStatus(422);
        $response->assertJson(['error' => 'VALIDATION_ERROR']);
    }

    public function test_store_validation_fails_with_duplicate_code()
    {
        $response = $this->withHeaders($this->adminHeaders())->postJson('/api/locales', [
            'code' => 'zh_CN',
            'name' => 'Test',
            'native_name' => 'Test',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['error' => 'VALIDATION_ERROR']);
    }

    public function test_store_returns_403_with_viewer_role()
    {
        $response = $this->withHeaders($this->viewerHeaders())->postJson('/api/locales', [
            'code' => 'fr',
            'name' => 'French',
            'native_name' => 'Français',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error' => 'FORBIDDEN',
            'status_code' => 403,
        ]);
    }

    public function test_update_locale_success()
    {
        $locale = Locale::where('code', 'pt_BR')->first();

        $response = $this->withHeaders($this->adminHeaders())->putJson('/api/locales/' . $locale->id, [
            'name' => 'Portuguese Updated',
            'native_name' => 'Português Atualizado',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Locale updated successfully',
        ]);
        $this->assertDatabaseHas('locales', [
            'id' => $locale->id,
            'name' => 'Portuguese Updated',
        ]);
    }

    public function test_update_locale_returns_404_for_nonexistent_id()
    {
        $response = $this->withHeaders($this->adminHeaders())->putJson('/api/locales/99999', [
            'name' => 'Non Existent',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'error' => 'NOT_FOUND',
            'message' => 'Locale not found',
            'status_code' => 404,
        ]);
    }

    public function test_update_locale_returns_403_with_viewer_role()
    {
        $locale = Locale::where('code', 'en')->first();

        $response = $this->withHeaders($this->viewerHeaders())->putJson('/api/locales/' . $locale->id, [
            'name' => 'Should Not Work',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error' => 'FORBIDDEN',
            'status_code' => 403,
        ]);
    }

    public function test_destroy_deletes_locale_successfully()
    {
        $locale = Locale::where('code', 'pt_BR')->first();

        $response = $this->withHeaders($this->adminHeaders())->deleteJson('/api/locales/' . $locale->id);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Locale deleted successfully',
        ]);
        $this->assertDatabaseMissing('locales', ['id' => $locale->id]);
    }

    public function test_destroy_returns_404_for_nonexistent_id()
    {
        $response = $this->withHeaders($this->adminHeaders())->deleteJson('/api/locales/99999');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'error' => 'NOT_FOUND',
            'status_code' => 404,
        ]);
    }

    public function test_destroy_returns_403_with_viewer_role()
    {
        $locale = Locale::where('code', 'pt_BR')->first();

        $response = $this->withHeaders($this->viewerHeaders())->deleteJson('/api/locales/' . $locale->id);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error' => 'FORBIDDEN',
            'status_code' => 403,
        ]);
    }

    public function test_destroy_returns_400_when_deleting_default_locale()
    {
        $defaultLocale = Locale::where('is_default', true)->first();

        $response = $this->withHeaders($this->adminHeaders())->deleteJson('/api/locales/' . $defaultLocale->id);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'error' => 'CANNOT_DELETE_DEFAULT',
            'message' => 'Cannot delete the default locale',
            'status_code' => 400,
        ]);
    }

    public function test_show_returns_messages_with_zh_cn_locale()
    {
        $response = $this->withHeaders($this->adminHeaders())->getJson('/api/locale/zh_CN');

        $response->assertStatus(200);
        $response->assertJsonPath('locale', 'zh_CN');
        $response->assertJsonStructure(['messages' => ['auth', 'common', 'menu']]);
    }

    public function test_store_creates_default_locale_unsets_previous_default()
    {
        $response = $this->withHeaders($this->adminHeaders())->postJson('/api/locales', [
            'code' => 'fr',
            'name' => 'French',
            'native_name' => 'Français',
            'is_default' => true,
            'is_enabled' => true,
            'sort_order' => 5,
        ]);

        $response->assertStatus(201);
        $oldDefault = Locale::where('code', 'zh_CN')->first();
        $this->assertFalse($oldDefault->is_default);
        $newDefault = Locale::where('code', 'fr')->first();
        $this->assertTrue($newDefault->is_default);
    }
}
