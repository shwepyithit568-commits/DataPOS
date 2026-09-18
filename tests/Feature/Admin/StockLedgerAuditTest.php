<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Services\InventoryService;
use App\POS\Services\StockLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * One page answers "who moved this stock, and when?".
 *
 * The ledger screen already gathered every movement type (sales, purchases,
 * receipts, adjustments, reservations) with an export and a per-product bin
 * card; this pins the two things an audit needs from it — the person is a
 * labelled column, and the list can be filtered down to one person.
 */
class StockLedgerAuditTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private User $owner;

    private User $keeper;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Ledger Shop', 'slug' => 'ledger-shop', 'is_active' => true]);

        $this->owner = $this->staff('Ledger Owner', '09740000101', 'store_owner');
        $this->keeper = $this->staff('Ledger Keeper', '09740000102', 'staff');

        $this->product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Ledger Widget',
            'slug' => 'ledger-widget-' . Str::random(4),
            'sku' => 'LED-' . Str::random(4),
            'retail_price' => 5000,
            'wholesale_price' => 4000,
        ]);
    }

    private function staff(string $name, string $phone, string $role): User
    {
        $user = User::create([
            'name' => $name,
            'phone' => $phone,
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($this->store->id, ['role' => $role, 'status' => 'active']);

        return $user;
    }

    private function move(User $actor, string $type, string $delta): void
    {
        app(InventoryService::class)->postMovement([
            'store_id' => $this->store->id,
            'product_id' => $this->product->id,
            'movement_type' => $type,
            'quantity_delta' => $delta,
            'source_type' => str_replace('_', '', $type),
            'client_transaction_id' => $type . ':' . Str::uuid(),
            'occurred_at' => now(),
            'posted_by' => $actor->id,
        ]);
    }

    public function test_every_movement_type_lands_on_the_one_screen_with_a_person(): void
    {
        $this->move($this->owner, 'opening_balance', '10');
        $this->move($this->keeper, 'purchase_received', '5');
        $this->move($this->keeper, 'pos_sale', '-2');
        $this->move($this->owner, 'adjustment_out', '-1');

        $response = $this->actingAs($this->owner)->get("/store/{$this->store->slug}/admin/stock-ledger");

        $response->assertOk();
        // Sales, purchases and adjustments all appear together…
        foreach (['opening_balance', 'purchase_received', 'pos_sale', 'adjustment_out'] as $type) {
            $response->assertSee(__('messages.movement_type_' . $type), false);
        }
        // …each with the person who posted it.
        $response->assertSee('Ledger Keeper', false);
        $response->assertSee('Ledger Owner', false);
    }

    public function test_the_list_can_be_filtered_down_to_one_person(): void
    {
        // A second product only the owner touches, so the filtered screen can
        // be judged by what it leaves out (the staff dropdown lists both names).
        $ownerOnly = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Owner Only Widget',
            'slug' => 'owner-only-' . Str::random(4),
            'sku' => 'OWN-' . Str::random(4),
            'retail_price' => 1000,
            'wholesale_price' => 900,
        ]);
        app(InventoryService::class)->postMovement([
            'store_id' => $this->store->id,
            'product_id' => $ownerOnly->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => '10',
            'source_type' => 'opening_balance',
            'client_transaction_id' => 'owner-only:' . Str::uuid(),
            'occurred_at' => now(),
            'posted_by' => $this->owner->id,
        ]);
        $this->move($this->keeper, 'purchase_received', '5');
        $this->move($this->keeper, 'pos_sale', '-2');

        $service = app(StockLedgerService::class);

        $keeperRows = $service->listMovements($this->store, ['user_id' => $this->keeper->id], 50);
        $ownerRows = $service->listMovements($this->store, ['user_id' => $this->owner->id], 50);
        $allRows = $service->listMovements($this->store, [], 50);

        $this->assertCount(2, $keeperRows->items());
        $this->assertCount(1, $ownerRows->items());
        $this->assertCount(3, $allRows->items());
        $this->assertTrue(collect($keeperRows->items())->every(fn ($m) => (int) $m->posted_by === $this->keeper->id));

        // …and the screen offers that choice.
        $response = $this->actingAs($this->owner)->get("/store/{$this->store->slug}/admin/stock-ledger");
        $response->assertSee('name="user_id"', false);

        $filtered = $this->actingAs($this->owner)
            ->get("/store/{$this->store->slug}/admin/stock-ledger?user_id={$this->keeper->id}");
        $filtered->assertOk()
            ->assertSee('Ledger Keeper', false)
            ->assertSee($this->product->name, false)
            ->assertDontSee($ownerOnly->name, false);
    }

    public function test_the_export_carries_the_posters_name(): void
    {
        $this->move($this->keeper, 'purchase_received', '5');

        $response = $this->actingAs($this->owner)
            ->get("/store/{$this->store->slug}/admin/stock-ledger/export?format=csv");

        $response->assertOk();
        $body = $response->streamedContent() ?: (string) $response->getContent();

        $this->assertStringContainsString(__('messages.stock_ledger_posted_by'), $body);
        $this->assertStringContainsString('Ledger Keeper', $body);
    }

    public function test_the_page_is_store_scoped(): void
    {
        $other = Store::create(['name' => 'Other Shop', 'slug' => 'other-ledger-shop', 'is_active' => true]);
        $foreign = Product::create([
            'store_id' => $other->id,
            'name' => 'Foreign Widget',
            'slug' => 'foreign-' . Str::random(4),
            'sku' => 'FRG-' . Str::random(4),
            'retail_price' => 1000,
            'wholesale_price' => 900,
        ]);
        app(InventoryService::class)->postMovement([
            'store_id' => $other->id,
            'product_id' => $foreign->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => '3',
            'source_type' => 'opening_balance',
            'client_transaction_id' => 'foreign:' . Str::uuid(),
            'occurred_at' => now(),
        ]);
        $this->move($this->owner, 'opening_balance', '10');

        $response = $this->actingAs($this->owner)->get("/store/{$this->store->slug}/admin/stock-ledger");

        $response->assertOk();
        $response->assertSee($this->product->name, false);
        $response->assertDontSee($foreign->name, false);
    }
}
