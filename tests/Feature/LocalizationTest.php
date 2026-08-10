<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_post_supported_locale_and_it_is_stored_in_session(): void
    {
        $this->from('/login')
            ->post('/locale', ['locale' => 'zh_CN'])
            ->assertRedirect('/login')
            ->assertSessionHas('locale', 'zh_CN');
    }

    public function test_authenticated_user_can_switch_locale(): void
    {
        $user = User::factory()->create(['phone' => '09123450001']);

        $this->actingAs($user)
            ->from('/account')
            ->post('/locale', ['locale' => 'en'])
            ->assertRedirect('/account')
            ->assertSessionHas('locale', 'en');
    }

    public function test_unsupported_locale_is_rejected(): void
    {
        $this->from('/login')
            ->post('/locale', ['locale' => 'zh'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('locale');
    }

    public function test_unsupported_locale_does_not_overwrite_existing_valid_session_locale(): void
    {
        $this->withSession(['locale' => 'en'])
            ->from('/login')
            ->post('/locale', ['locale' => 'zh-CN'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('locale')
            ->assertSessionHas('locale', 'en');
    }

    public function test_session_locale_takes_precedence_over_store_default(): void
    {
        $store = $this->createStoreWithDefaultLocale('store-a', 'zh_CN');

        $this->withSession(['locale' => 'en'])
            ->get('/?store_slug='.$store->slug)
            ->assertOk()
            ->assertSee('Home')
            ->assertDontSee('首页');
    }

    public function test_store_default_is_used_when_session_locale_is_absent(): void
    {
        $store = $this->createStoreWithDefaultLocale('store-a', 'zh_CN');

        $this->get('/?store_slug='.$store->slug)
            ->assertOk()
            ->assertSee('首页');
    }

    public function test_app_default_is_used_when_no_session_or_store_default_exists(): void
    {
        config(['app.locale' => 'en']);

        $this->get('/login')
            ->assertOk()
            ->assertSee('Login to your account');
    }

    public function test_storefront_header_renders_language_switcher(): void
    {
        $store = $this->createStoreWithDefaultLocale('store-a', 'en');

        $this->get('/?store_slug='.$store->slug)
            ->assertOk()
            ->assertSee('action="'.url('/locale').'"', false)
            ->assertSee('Choose language')
            ->assertSee('简体中文');
    }

    public function test_admin_header_renders_language_switcher_for_store_manager(): void
    {
        [$store, $manager] = $this->createManagedStore('store-a', 'en');

        $this->actingAs($manager)
            ->get("/store/{$store->slug}/admin/dashboard")
            ->assertOk()
            ->assertSee('Choose language')
            ->assertSee('简体中文');
    }

    public function test_zh_cn_renders_known_translated_label(): void
    {
        $this->withSession(['locale' => 'zh_CN'])
            ->get('/login')
            ->assertOk()
            ->assertSee('登录账户');
    }

    public function test_locale_selection_survives_navigation_to_another_page(): void
    {
        $this->post('/locale', ['locale' => 'zh_CN'])
            ->assertSessionHas('locale', 'zh_CN');

        $this->get('/login')
            ->assertOk()
            ->assertSee('登录账户');

        $this->get('/register')
            ->assertOk()
            ->assertSee('创建新账户');
    }

    public function test_locale_update_route_is_post_only_and_uses_web_middleware(): void
    {
        $route = Route::getRoutes()->getByName('locale.update');

        $this->assertNotNull($route);
        $this->assertSame(['POST'], array_values(array_diff($route->methods(), ['HEAD'])));
        $this->assertContains('web', $route->gatherMiddleware());
    }

    public function test_message_translation_files_have_identical_key_sets(): void
    {
        $locales = ['my', 'en', 'zh_CN'];
        $keySets = [];

        foreach ($locales as $locale) {
            $messages = require lang_path("{$locale}/messages.php");
            $keys = array_keys($messages);
            sort($keys);
            $keySets[$locale] = $keys;
        }

        $this->assertSame($keySets['en'], $keySets['my']);
        $this->assertSame($keySets['en'], $keySets['zh_CN']);
    }

    public function test_store_setting_validation_accepts_supported_locales(): void
    {
        [$store, $manager] = $this->createManagedStore('store-a', 'my');

        $this->actingAs($manager)
            ->post("/store/{$store->slug}/admin/settings", [
                'store_name' => 'Store A',
                'default_language' => 'zh_CN',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('storefront_settings', [
            'store_id' => $store->id,
            'default_language' => 'zh_CN',
        ]);
    }

    public function test_store_setting_validation_rejects_arbitrary_locale_values(): void
    {
        [$store, $manager] = $this->createManagedStore('store-a', 'en');

        $this->actingAs($manager)
            ->from("/store/{$store->slug}/admin/settings")
            ->post("/store/{$store->slug}/admin/settings", [
                'store_name' => 'Store A',
                'default_language' => 'zh',
            ])
            ->assertRedirect("/store/{$store->slug}/admin/settings")
            ->assertSessionHasErrors('default_language');

        $this->assertDatabaseMissing('storefront_settings', [
            'store_id' => $store->id,
            'default_language' => 'zh',
        ]);
    }

    private function createStoreWithDefaultLocale(string $slug, string $locale): Store
    {
        $store = Store::create(['name' => 'Store A', 'slug' => $slug]);
        $store->setting()->create([
            'store_name' => 'Store A',
            'default_language' => $locale,
        ]);

        return $store;
    }

    private function createManagedStore(string $slug, string $locale): array
    {
        $store = $this->createStoreWithDefaultLocale($slug, $locale);
        $manager = User::factory()->create([
            'phone' => '0912345'.str_pad((string) $store->id, 4, '0', STR_PAD_LEFT),
            'role' => 'customer',
        ]);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        return [$store, $manager];
    }
}
