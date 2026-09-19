<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\BuyBack;
use App\POS\Models\Warehouse;
use App\POS\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BuyBackModuleTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventory = app(InventoryService::class);
    }

    private function makeStore(string $slug = 'buyback-shop'): Store
    {
        return Store::create(['name' => 'BuyBack Shop', 'slug' => $slug, 'is_active' => true]);
    }

    private function staff(Store $store, string $role = 'staff'): User
    {
        $user = User::create([
            'name' => 'Cashier ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => $role, 'status' => 'active']);

        return $user;
    }

    private function makeProduct(Store $store, int $price = 50000): Product
    {
        $name = 'Used Phone ' . Str::random(3);

        return Product::create([
            'store_id' => $store->id,
            'sku' => strtoupper(Str::random(8)),
            'name' => $name,
            'slug' => Str::slug($name . '-' . Str::random(3)),
            'retail_price' => $price,
            'wholesale_price' => $price - 5000,
        ]);
    }

    public function test_manager_and_staff_can_view_buybacks_index(): void
    {
        $store = $this->makeStore();
        $manager = $this->staff($store, 'store_manager');
        $staff = $this->staff($store, 'staff');

        $resManager = $this->actingAs($manager)->get("/store/{$store->slug}/pos/buy-back");
        $resManager->assertOk();
        $resManager->assertSee(__('messages.sidebar_buy_back'));

        $resStaff = $this->actingAs($staff)->get("/store/{$store->slug}/pos/buy-back");
        $resStaff->assertOk();
    }

    public function test_manager_can_view_buyback_create_page(): void
    {
        $store = $this->makeStore();
        $manager = $this->staff($store, 'store_manager');
        $this->makeProduct($store, 60000);

        $res = $this->actingAs($manager)->get("/store/{$store->slug}/pos/buy-back/create");
        $res->assertOk();
        $res->assertSee('buybackForm');
    }

    public function test_buyback_creation_and_show(): void
    {
        $store = $this->makeStore();
        $manager = $this->staff($store, 'store_manager');
        $product = $this->makeProduct($store, 60000);

        $payload = [
            'reason' => 'Trade in for iPhone',
            'notes' => 'Minor scratch on back',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 45000,
                ],
            ],
        ];

        $postRes = $this->actingAs($manager)->post("/store/{$store->slug}/pos/buy-back", $payload);
        $buyback = BuyBack::where('store_id', $store->id)->first();
        $this->assertNotNull($buyback);
        $this->assertEquals(45000.0, (float) $buyback->total_value);
        $this->assertEquals('pending', $buyback->status);

        $postRes->assertRedirect("/store/{$store->slug}/pos/buy-back/{$buyback->id}");

        $showRes = $this->actingAs($manager)->get("/store/{$store->slug}/pos/buy-back/{$buyback->id}");
        $showRes->assertOk();
        $showRes->assertSee($buyback->buyback_number);
        $showRes->assertSee('45,000');
    }

    public function test_complete_buyback_restores_inventory(): void
    {
        $store = $this->makeStore();
        $manager = $this->staff($store, 'store_manager');
        $product = $this->makeProduct($store, 60000);
        $warehouse = Warehouse::create(['store_id' => $store->id, 'name' => 'Main', 'is_default' => true]);

        $buyback = BuyBack::create([
            'store_id' => $store->id,
            'buyback_number' => BuyBack::generateNumber($store->id),
            'total_value' => 40000,
            'refund_amount' => 40000,
            'status' => 'pending',
            'created_by' => $manager->id,
        ]);
        $buyback->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 20000,
        ]);

        $completeRes = $this->actingAs($manager)->post("/store/{$store->slug}/pos/buy-back/{$buyback->id}/complete");
        $completeRes->assertSessionHas('success');

        $buyback->refresh();
        $movement = \App\POS\Models\InventoryMovement::where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->first();
        $this->assertNotNull($movement);
        $this->assertEquals(2.0, (float) $movement->quantity_delta);
    }

    public function test_cancel_buyback(): void
    {
        $store = $this->makeStore();
        $manager = $this->staff($store, 'store_manager');

        $buyback = BuyBack::create([
            'store_id' => $store->id,
            'buyback_number' => BuyBack::generateNumber($store->id),
            'total_value' => 30000,
            'refund_amount' => 30000,
            'status' => 'pending',
            'created_by' => $manager->id,
        ]);

        $cancelRes = $this->actingAs($manager)->post("/store/{$store->slug}/pos/buy-back/{$buyback->id}/cancel");
        $cancelRes->assertSessionHas('success');

        $buyback->refresh();
        $this->assertEquals('cancelled', $buyback->status);
    }

    public function test_buyback_export_csv_and_xlsx(): void
    {
        $store = $this->makeStore();
        $manager = $this->staff($store, 'store_manager');
        $product = $this->makeProduct($store, 50000);

        $buyback = BuyBack::create([
            'store_id' => $store->id,
            'buyback_number' => BuyBack::generateNumber($store->id),
            'total_value' => 35000,
            'refund_amount' => 35000,
            'status' => 'completed',
            'created_by' => $manager->id,
        ]);
        $buyback->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 35000,
        ]);

        // CSV export
        $csvRes = $this->actingAs($manager)->get("/store/{$store->slug}/pos/buy-back/export?format=csv");
        $csvRes->assertOk();
        $this->assertStringContainsString('.csv', (string) $csvRes->headers->get('content-disposition'));

        // XLSX export
        $xlsxRes = $this->actingAs($manager)->get("/store/{$store->slug}/pos/buy-back/export?format=xlsx");
        $xlsxRes->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $xlsxRes->headers->get('content-disposition'));
    }

    public function test_cross_store_buyback_access_is_blocked(): void
    {
        $storeA = $this->makeStore('store-a-buyback');
        $storeB = $this->makeStore('store-b-buyback');
        $managerA = $this->staff($storeA, 'store_manager');
        $managerB = $this->staff($storeB, 'store_manager');

        $buybackA = BuyBack::create([
            'store_id' => $storeA->id,
            'buyback_number' => BuyBack::generateNumber($storeA->id),
            'total_value' => 50000,
            'refund_amount' => 50000,
            'status' => 'pending',
            'created_by' => $managerA->id,
        ]);

        $response = $this->actingAs($managerB)->get("/store/{$storeB->slug}/pos/buy-back/{$buybackA->id}");
        $response->assertNotFound();
    }

    public function test_buyback_print_view_renders_voucher_and_toolbar(): void
    {
        $store = $this->makeStore();
        $manager = $this->staff($store, 'store_manager');
        $product = $this->makeProduct($store, 50000);

        $buyback = BuyBack::create([
            'store_id' => $store->id,
            'buyback_number' => BuyBack::generateNumber($store->id),
            'total_value' => 45000,
            'refund_amount' => 45000,
            'status' => 'completed',
            'created_by' => $manager->id,
        ]);
        $buyback->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 45000,
        ]);

        $res = $this->actingAs($manager)->get("/store/{$store->slug}/pos/buy-back/{$buyback->id}/print");
        $res->assertOk();
        $res->assertSee('btnPrint');
        $res->assertSee('btnShareJpg');
        $res->assertSee('btnDownloadPdf');
        $res->assertSee('80mm');
        $res->assertSee('58mm');
        $res->assertSee($buyback->buyback_number);
        $res->assertSee($store->name);
    }

    /**
     * A shop with no internet must still be able to print, save a PDF and share
     * the slip. That only works while every asset on this page is served from
     * this origin: the admin CSP is script-src 'self' + nonce / font-src 'self',
     * so a CDN <script> or Google Fonts <link> is blocked outright — the
     * Download PDF and Share JPG buttons were dead because of exactly that.
     */
    public function test_buyback_print_page_depends_on_no_external_host(): void
    {
        $store = $this->makeStore();
        $manager = $this->staff($store, 'store_manager');
        $product = $this->makeProduct($store, 50000);

        $buyback = BuyBack::create([
            'store_id'       => $store->id,
            'buyback_number' => BuyBack::generateNumber($store->id),
            'total_value'    => 45000,
            'refund_amount'  => 45000,
            'status'         => 'completed',
            'created_by'     => $manager->id,
        ]);
        $buyback->items()->create([
            'product_id' => $product->id,
            'quantity'   => 1,
            'unit_price' => 45000,
        ]);

        $html = $this->actingAs($manager)
            ->get("/store/{$store->slug}/pos/buy-back/{$buyback->id}/print")
            ->assertOk()
            ->getContent();

        foreach (['fonts.googleapis.com', 'fonts.gstatic.com', 'cdnjs.cloudflare.com', 'cdn.jsdelivr.net', 'unpkg.com'] as $host) {
            $this->assertStringNotContainsString($host, $html, "the slip must not fetch {$host}");
        }

        // The PDF/JPG tool is bundled locally instead (resources/js/buyback-print.js).
        $this->assertStringContainsString('buyback-print', $html);
    }

    public function test_buyback_show_json_returns_modal_data(): void
    {
        $store = $this->makeStore();
        $manager = $this->staff($store, 'store_manager');
        $product = $this->makeProduct($store, 70000);

        $buyback = BuyBack::create([
            'store_id' => $store->id,
            'buyback_number' => BuyBack::generateNumber($store->id),
            'total_value' => 50000,
            'refund_amount' => 50000,
            'status' => 'pending',
            'created_by' => $manager->id,
            'reason' => 'Upgrade to newer model',
        ]);
        $buyback->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 50000,
        ]);

        $res = $this->actingAs($manager)->get("/store/{$store->slug}/pos/buy-back/{$buyback->id}?format=json");
        $res->assertOk();
        $res->assertJsonStructure([
            'id',
            'buyback_number',
            'status',
            'reason',
            'total_value',
            'total_formatted',
            'created_at_formatted',
            'items' => [
                '*' => [
                    'id',
                    'product_id',
                    'name',
                    'sku',
                    'quantity',
                    'quantity_formatted',
                    'unit_price',
                    'unit_price_formatted',
                    'line_total',
                    'line_total_formatted',
                ],
            ],
            'show_url',
            'print_url',
        ]);
        $res->assertJsonFragment([
            'buyback_number' => $buyback->buyback_number,
            'reason' => 'Upgrade to newer model',
        ]);
    }

    public function test_buyback_index_contains_print_and_modal_actions(): void
    {
        $store = $this->makeStore();
        $manager = $this->staff($store, 'store_manager');
        $product = $this->makeProduct($store, 30000);

        $buyback = BuyBack::create([
            'store_id' => $store->id,
            'buyback_number' => BuyBack::generateNumber($store->id),
            'total_value' => 25000,
            'refund_amount' => 25000,
            'status' => 'completed',
            'created_by' => $manager->id,
        ]);
        $buyback->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 25000,
        ]);

        $res = $this->actingAs($manager)->get("/store/{$store->slug}/pos/buy-back");
        $res->assertOk();
        $res->assertSee('openDetail');
        $res->assertSee("/store/{$store->slug}/pos/buy-back/{$buyback->id}/print");
        $res->assertSee(__('messages.buyback_detail_title'));
    }
}
