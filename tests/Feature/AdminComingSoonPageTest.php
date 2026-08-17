<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ComingSoonController;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminComingSoonPageTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $staff;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Store One', 'slug' => 'store-one']);
        $this->store->setting()->create(['store_name' => 'Store One', 'default_language' => 'en']);

        $this->manager = User::factory()->create(['phone' => '09111111111']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->staff = User::factory()->create(['phone' => '09222222222']);
        $this->staff->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);

        $this->customer = User::factory()->create(['phone' => '09333333333', 'role' => 'customer']);
    }

    public function test_every_sidebar_placeholder_module_has_a_registered_lang_key(): void
    {
        foreach (ComingSoonController::modules() as $module => $meta) {
            [$labelKey] = $meta;
            $this->assertNotSame(
                "messages.{$labelKey}",
                __("messages.{$labelKey}"),
                "Module [{$module}] must resolve its lang key — is 'messages.{$labelKey}' registered?"
            );
        }
    }

    public function test_manager_can_view_placeholder_page_for_registered_module(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/coming-soon/expenses");

        $response->assertStatus(200);
        $response->assertSeeText('Daily Expenses');
        $response->assertSeeText('Phase 4');
        $response->assertSeeText('Coming Soon');
        // Back link returns to the store dashboard.
        $response->assertSee("/store/{$this->store->slug}/admin/dashboard", false);
    }

    public function test_staff_can_view_placeholder_page(): void
    {
        $response = $this->actingAs($this->staff)
            ->get("/store/{$this->store->slug}/admin/coming-soon/stock-count");

        $response->assertStatus(200);
        $response->assertSeeText('Stock Count');
        $response->assertSeeText('Phase 4');
    }

    public function test_unknown_module_slug_returns_404(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/coming-soon/not-a-module");

        $response->assertStatus(404);
    }

    public function test_customer_without_store_role_is_blocked(): void
    {
        $response = $this->actingAs($this->customer)
            ->get("/store/{$this->store->slug}/admin/coming-soon/expenses");

        $response->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get("/store/{$this->store->slug}/admin/coming-soon/expenses");

        $response->assertRedirect(route('login'));
    }

    public function test_cross_store_module_access_is_blocked(): void
    {
        $otherStore = Store::create(['name' => 'Store Two', 'slug' => 'store-two']);
        $otherStore->setting()->create(['store_name' => 'Store Two', 'default_language' => 'en']);
        // Manager of store-one tries to open a placeholder on store-two.
        $response = $this->actingAs($this->manager)
            ->get("/store/{$otherStore->slug}/admin/coming-soon/expenses");

        $response->assertStatus(403);
    }

    public function test_placeholder_page_renders_in_all_supported_locales(): void
    {
        foreach (['en', 'my', 'zh_CN'] as $code) {
            $store = Store::create(['name' => "Store {$code}", 'slug' => "store-{$code}"]);
            $store->setting()->create(['store_name' => "Store {$code}", 'default_language' => $code]);
            $this->manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

            $response = $this->actingAs($this->manager)
                ->get("/store/store-{$code}/admin/coming-soon/purchases");

            $response->assertStatus(200);
            // No raw translation key leaks into the page.
            $response->assertDontSee('messages.', false);
        }
    }
}
