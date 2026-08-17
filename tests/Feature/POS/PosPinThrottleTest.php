<?php

namespace Tests\Feature\POS;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Store;
use App\Models\StorefrontSetting;
use App\Models\User;
use App\POS\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosPinThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $inventory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventory = app(InventoryService::class);
    }

    private function makeStore(): Store
    {
        $store = Store::create(['name' => 'Pin Store', 'slug' => 'pin-store', 'is_active' => true]);
        StorefrontSetting::updateOrCreate(
            ['store_id' => $store->id],
            ['store_name' => $store->name, 'pos_override_pin_threshold' => 10],
        );

        return $store;
    }

    private function staff(Store $store): User
    {
        $user = User::create([
            'name' => 'Cashier ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        return $user;
    }

    private function manager(Store $store, string $pin = '1234'): User
    {
        $user = User::create([
            'name' => 'Manager ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
            'pos_pin' => $pin,
        ]);
        $user->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        return $user;
    }

    /** Add one 10000-kyat product to the acting user's cart (line 0). */
    private function addLineToCart(Store $store): Product
    {
        $product = Product::create([
            'store_id' => $store->id,
            'sku' => strtoupper(Str::random(8)),
            'name' => 'Phone ' . Str::random(3),
            'slug' => 'phone-' . Str::random(3),
            'retail_price' => 10000,
            'wholesale_price' => 9000,
            'stock_status' => 'in_stock',
        ]);
        $this->inventory->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => '10',
            'unit_cost' => 8000,
            'source_type' => 'opening_balance',
            'client_transaction_id' => 'seed:' . Str::uuid(),
            'occurred_at' => now(),
        ]);

        $this->postJson("/store/{$store->slug}/pos/cart", [
            'product_id' => $product->id,
            'quantity' => '1',
        ])->assertOk();

        return $product;
    }

    private function priceWithPin(Store $store, ?string $pin): \Illuminate\Testing\TestResponse
    {
        return $this->postJson("/store/{$store->slug}/pos/cart/0/price", [
            'unit_price' => '5000', // 50% discount — way over the 10% threshold
            'manager_pin' => $pin,
        ]);
    }

    public function test_five_wrong_pins_lock_the_pin_prompt_even_with_correct_pin(): void
    {
        $store = $this->makeStore();
        $this->actingAs($this->staff($store));
        $this->manager($store, '1234');
        $this->addLineToCart($store);

        // Attempts 1-4 report a wrong PIN; the 5th trips the lockout and says
        // so immediately. A further attempt — even with the CORRECT pin — is
        // refused until the window expires (no fresh pin_required prompt).
        for ($i = 1; $i <= 4; $i++) {
            $this->priceWithPin($store, '9999')
                ->assertStatus(422)
                ->assertJsonPath('error', __('messages.pos_price_pin_invalid'))
                ->assertJsonPath('pin_required', true);
        }
        $this->priceWithPin($store, '9999')
            ->assertStatus(422)
            ->assertJsonMissingPath('pin_required')
            ->assertJsonPath('error', __('messages.pos_price_pin_locked', ['minutes' => 15]));

        $this->priceWithPin($store, '1234')
            ->assertStatus(422)
            ->assertJsonMissingPath('pin_required')
            ->assertJsonPath('error', __('messages.pos_price_pin_locked', ['minutes' => 15]));

        // The line still has no approver — the override was never applied.
        $state = $this->getJson("/store/{$store->slug}/pos/cart-state")->json('cart');
        $this->assertNull($state['lines'][0]['original_unit_price'] ?? null);
    }

    public function test_correct_pin_clears_the_attempt_counter(): void
    {
        $store = $this->makeStore();
        $this->actingAs($this->staff($store));
        $this->manager($store, '1234');
        $this->addLineToCart($store);

        // 4 wrong attempts (still below the lockout limit)…
        for ($i = 1; $i <= 4; $i++) {
            $this->priceWithPin($store, '9999')->assertStatus(422);
        }

        // …then the correct PIN succeeds and resets the counter.
        $this->priceWithPin($store, '1234')->assertOk();

        // The counter was cleared: 4 more wrong attempts stay "invalid" (not
        // locked), and only the 5th after the reset locks.
        for ($i = 1; $i <= 4; $i++) {
            $this->priceWithPin($store, '9999')
                ->assertStatus(422)
                ->assertJsonPath('error', __('messages.pos_price_pin_invalid'));
        }
        $this->priceWithPin($store, '9999')
            ->assertStatus(422)
            ->assertJsonPath('error', __('messages.pos_price_pin_locked', ['minutes' => 15]));
    }

    public function test_lockout_is_per_user(): void
    {
        $store = $this->makeStore();
        $this->manager($store, '1234');

        // Cashier A burns through the lockout.
        $cashierA = $this->staff($store);
        $this->actingAs($cashierA);
        $this->addLineToCart($store);
        for ($i = 1; $i <= 5; $i++) {
            $this->priceWithPin($store, '9999')->assertStatus(422);
        }

        // Cashier B is unaffected — the counter is per acting user.
        $cashierB = $this->staff($store);
        $this->actingAs($cashierB);
        $this->addLineToCart($store);
        $this->priceWithPin($store, '1234')->assertOk();
    }

    public function test_failed_pin_attempts_are_audit_logged(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $this->actingAs($cashier);
        $this->manager($store, '1234');
        $this->addLineToCart($store);

        $this->priceWithPin($store, '9999')->assertStatus(422);
        $this->priceWithPin($store, '8888')->assertStatus(422);

        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $store->id,
            'actor_id' => $cashier->id,
            'action' => 'pos_pin_failed',
        ]);
        $this->assertSame(2, AuditLog::where('store_id', $store->id)
            ->where('actor_id', $cashier->id)
            ->where('action', 'pos_pin_failed')
            ->count());
    }
}
