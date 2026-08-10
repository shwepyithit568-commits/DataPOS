<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_and_logged_user_can_submit_order_request(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'SKU-001',
            'name' => 'Case Cover',
            'slug' => 'case-cover',
            'retail_price' => 5000.00,
            'wholesale_price' => 3000.00,
            'stock_status' => 'in_stock',
        ]);

        // 1. Guest Order Submission -> Retail price used
        $responseGuest = $this->post('/store/main-store/orders', [
            'product_id' => $product->id,
            'customer_name' => 'Guest Kyaw',
            'customer_phone' => '09111111111',
            'customer_address' => 'Yangon',
            'contact_channel' => 'viber',
            'quantity' => 2,
        ]);

        $responseGuest->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'store_id' => $store->id,
            'customer_name' => 'Guest Kyaw',
            'pricing_type' => 'retail',
            'total_amount' => 10000.00,
            'status' => 'pending_contact',
        ]);
    }

    public function test_approved_wholesale_user_gets_wholesale_pricing(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'SKU-001',
            'name' => 'Case Cover',
            'slug' => 'case-cover',
            'retail_price' => 5000.00,
            'wholesale_price' => 3000.00,
            'stock_status' => 'in_stock',
        ]);

        $wholesaleUser = User::create([
            'name' => 'Wholesale Buyer',
            'phone' => '09888888888',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $wholesaleUser->stores()->attach($store->id, ['role' => 'wholesale_customer', 'status' => 'active']);

        $response = $this->actingAs($wholesaleUser)->post('/store/main-store/orders', [
            'product_id' => $product->id,
            'customer_name' => 'Wholesale Buyer',
            'customer_phone' => '09888888888',
            'customer_address' => 'Yangon',
            'contact_channel' => 'telegram',
            'contact_identifier' => '@wholesale_buyer',
            'quantity' => 5,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'store_id' => $store->id,
            'user_id' => $wholesaleUser->id,
            'pricing_type' => 'wholesale',
            'contact_identifier' => '@wholesale_buyer',
            'total_amount' => 15000.00, // 3000 * 5
        ]);
    }

    public function test_out_of_stock_product_blocked_from_ordering(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'SKU-OUT',
            'name' => 'Sold Out Item',
            'slug' => 'sold-out-item',
            'retail_price' => 5000.00,
            'wholesale_price' => 3000.00,
            'stock_status' => 'out_of_stock',
        ]);

        $response = $this->post('/store/main-store/orders', [
            'product_id' => $product->id,
            'customer_name' => 'Buyer',
            'customer_phone' => '09111111111',
            'customer_address' => 'Yangon',
            'contact_channel' => 'viber',
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors('product');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_address_is_required(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'SKU-ADDR',
            'name' => 'Address Test',
            'slug' => 'address-test',
            'retail_price' => 5000.00,
            'wholesale_price' => 3000.00,
            'stock_status' => 'in_stock',
        ]);

        // Submit without address — must fail
        $response = $this->post('/store/main-store/orders', [
            'product_id' => $product->id,
            'customer_name' => 'No Address',
            'customer_phone' => '09111111111',
            'contact_channel' => 'viber',
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors('customer_address');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_submission_redirects_to_confirmation_page(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        // Confirmation page renders Viber/Telegram buttons from the store
        // setting, so provide one (the page asserts both channels).
        $store->setting()->create([
            'store_name' => 'Main Store',
            'viber_number' => '09999999999',
            'telegram_username' => 'main_store_orders',
        ]);
        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'SKU-CONF',
            'name' => 'Confirm Test',
            'slug' => 'confirm-test',
            'retail_price' => 10000.00,
            'wholesale_price' => 7000.00,
            'stock_status' => 'in_stock',
        ]);

        // 1. Guest order — redirect includes confirmation_token
        $response = $this->post('/store/main-store/orders', [
            'product_id' => $product->id,
            'customer_name' => 'Confirm Buyer',
            'customer_phone' => '09123456789',
            'customer_address' => 'Yangon',
            'contact_channel' => 'viber',
            'quantity' => 1,
        ]);

        $response->assertRedirect();
        $redirectUrl = $response->headers->get('Location');
        $this->assertStringContainsString('/store/main-store/orders/', $redirectUrl);
        $this->assertStringContainsString('/confirmation', $redirectUrl);
        $this->assertStringContainsString('token=', $redirectUrl);
        $this->assertStringNotContainsString('viber://', $redirectUrl);
        $this->assertStringNotContainsString('t.me/', $redirectUrl);

        // Extract token and verify confirmation page renders for guest
        preg_match('#/orders/(\d+)/confirmation\?token=([a-zA-Z0-9]+)#', $redirectUrl, $matches);
        $orderId = $matches[1] ?? null;
        $guestToken = $matches[2] ?? null;
        $this->assertNotNull($orderId);
        $this->assertNotNull($guestToken);
        $this->assertEquals(40, strlen($guestToken)); // Str::random(40)

        $confResponse = $this->get($redirectUrl);
        $confResponse->assertStatus(200);
        $confResponse->assertSee(__('messages.order_summary'));
        $confResponse->assertSee('Confirm Buyer');
        $confResponse->assertSee('pending_contact');
        $confResponse->assertSee('Viber');
        $confResponse->assertSee('Telegram');
        // "Get Viber" not-installed fallback under the Viber send-order button.
        $confResponse->assertSee(__('messages.viber_missing'));
        $confResponse->assertSee('href="https://www.viber.com/download/"', false);

        // 2. Guest enumeration test — invalid token rejected
        $badResponse = $this->get("/store/main-store/orders/{$orderId}/confirmation?token=invalidtoken123");
        $badResponse->assertStatus(404);

        // 3. Guest enumeration test — no token at all rejected
        $noTokenResponse = $this->get("/store/main-store/orders/{$orderId}/confirmation");
        $noTokenResponse->assertStatus(404);

        // 4. Cross-store token — Store B cannot access Store A confirmation
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);
        $crossStoreResponse = $this->get("/store/store-b/orders/{$orderId}/confirmation?token={$guestToken}");
        $crossStoreResponse->assertStatus(404);

        // 5. Logged-in user A cannot view User B confirmation
        $userA = User::create([
            'name' => 'User A',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $userB = User::create([
            'name' => 'User B',
            'phone' => '09222222222',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        // Place order as User A
        $responseUserA = $this->actingAs($userA)->post('/store/main-store/orders', [
            'product_id' => $product->id,
            'customer_name' => 'User A',
            'customer_phone' => '09111111111',
            'customer_address' => 'Yangon',
            'contact_channel' => 'telegram',
            'quantity' => 2,
        ]);
        $responseUserA->assertRedirect();
        $urlUserA = $responseUserA->headers->get('Location');
        preg_match('#/orders/(\d+)/confirmation#', $urlUserA, $matchesA);
        $orderUserAId = $matchesA[1] ?? null;
        $this->assertNotNull($orderUserAId);

        // User A can view own confirmation (no token needed since logged-in)
        $userAOk = $this->actingAs($userA)->get("/store/main-store/orders/{$orderUserAId}/confirmation");
        $userAOk->assertStatus(200);
        $userAOk->assertSee('User A');

        // User B cannot view User A confirmation
        $userBDenied = $this->actingAs($userB)->get("/store/main-store/orders/{$orderUserAId}/confirmation");
        $userBDenied->assertStatus(403);
    }

    public function test_confirmation_token_is_protected_from_mass_assignment(): void
    {
        // Verify that confirmation_token is NOT in the $fillable array
        $order = new Order();
        $this->assertNotContains('confirmation_token', $order->getFillable(),
            'confirmation_token must NOT be fillable to prevent mass assignment');

        // Verify that submitting confirmation_token in POST data does NOT control
        // the stored value — the server generates it server-side via boot()::creating.
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'SKU-MA-001',
            'name' => 'Mass Assignment Test',
            'slug' => 'mass-assignment-test',
            'retail_price' => 10000.00,
            'wholesale_price' => 7000.00,
            'stock_status' => 'in_stock',
        ]);

        $hackedToken = 'hacked-by-client-' . Str::random(20);

        $response = $this->post('/store/main-store/orders', [
            'product_id' => $product->id,
            'customer_name' => 'Mass Assignment Victim',
            'customer_phone' => '09111111111',
            'customer_address' => 'Yangon',
            'contact_channel' => 'viber',
            'quantity' => 1,
            'confirmation_token' => $hackedToken, // injection attempt
        ]);

        $response->assertRedirect();
        $redirectUrl = $response->headers->get('Location');
        preg_match('#/orders/(\d+)/confirmation\?token=([a-zA-Z0-9]+)#', $redirectUrl, $matches);
        $actualToken = $matches[2] ?? null;
        $this->assertNotNull($actualToken);

        // The stored token must be the server-generated 40-char token, NOT the injected one
        $this->assertEquals(40, strlen($actualToken));
        $this->assertNotEquals($hackedToken, $actualToken,
            'confirmation_token must be server-generated, not client-controlled');

        // Also verify the DB record directly
        preg_match('#/orders/(\d+)/confirmation#', $redirectUrl, $orderMatches);
        $orderId = $orderMatches[1] ?? null;
        $this->assertNotNull($orderId);
        $dbToken = \Illuminate\Support\Facades\DB::table('orders')->where('id', $orderId)->value('confirmation_token');
        $this->assertNotEquals($hackedToken, $dbToken);
        $this->assertEquals(40, strlen($dbToken));
    }

    public function test_confirmation_links_normalize_telegram_username_and_clear_cart_after_success(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        $store->setting()->create([
            'store_name' => 'Main Store',
            'viber_number' => '09999999999',
            'telegram_username' => '@main_store_orders',
            'default_language' => 'my',
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'SKU-LINK-001',
            'name' => 'Link Test Product',
            'slug' => 'link-test-product',
            'retail_price' => 10000.00,
            'wholesale_price' => 7000.00,
            'stock_status' => 'in_stock',
        ]);

        $response = $this->post('/store/main-store/orders', [
            'product_id' => $product->id,
            'customer_name' => 'Link Buyer',
            'customer_phone' => '09111111111',
            'customer_address' => 'Yangon',
            'contact_channel' => 'telegram',
            'contact_identifier' => '@link_buyer',
            'quantity' => 1,
        ]);

        $response->assertRedirect();
        $confirmationResponse = $this->get($response->headers->get('Location'));

        $confirmationResponse->assertStatus(200);
        $confirmationResponse->assertSee('https://t.me/main_store_orders?text=', false);
        $confirmationResponse->assertDontSee('https://t.me/@main_store_orders', false);
        $confirmationResponse->assertSee('@link_buyer');
        $confirmationResponse->assertSee('$store.orderBuilder', false);
        $confirmationResponse->assertSee('clear()', false);
    }

    public function test_admin_order_status_forms_require_explicit_update_button(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        $order = Order::create([
            'store_id' => $store->id,
            'order_number' => 'ORD-UI-001',
            'customer_name' => 'Admin Buyer',
            'customer_phone' => '09111111111',
            'contact_identifier' => '@admin_buyer',
            'contact_channel' => 'viber',
            'pricing_type' => 'retail',
            'total_amount' => 5000.00,
            'status' => 'pending_contact',
        ]);

        $staff = User::create([
            'name' => 'Staff',
            'phone' => '09555555555',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $staff->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        $listResponse = $this->actingAs($staff)->get('/store/main-store/admin/orders');
        $listResponse->assertStatus(200);
        $listResponse->assertSee('/store/main-store/admin/orders/' . $order->id . '/status', false);
        $listResponse->assertSee('@admin_buyer');
        $listResponse->assertSee('Update');

        $detailResponse = $this->actingAs($staff)->get('/store/main-store/admin/orders/' . $order->id);
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('Contact:');
        $detailResponse->assertSee('@admin_buyer');
        $detailResponse->assertDontSee('onchange="this.form.submit()"', false);
        $detailResponse->assertSee('Update');
    }

    public function test_store_isolation_and_admin_order_confirmation(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);

        $orderA = Order::create([
            'store_id' => $storeA->id,
            'order_number' => 'ORD-TEST-A',
            'customer_name' => 'Customer A',
            'customer_phone' => '09111111111',
            'contact_channel' => 'viber',
            'pricing_type' => 'retail',
            'total_amount' => 5000.00,
            'status' => 'pending_contact',
        ]);

        $staffA = User::create([
            'name' => 'Staff A',
            'phone' => '09555555555',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $staffA->stores()->attach($storeA->id, ['role' => 'staff', 'status' => 'active']);

        // 1. Staff A confirms Order A
        $responseConfirm = $this->actingAs($staffA)->patch('/store/store-a/admin/orders/' . $orderA->id . '/status', [
            'status' => 'confirmed',
        ]);
        $responseConfirm->assertRedirect();
        $this->assertDatabaseHas('orders', ['id' => $orderA->id, 'status' => 'confirmed']);

        // 2. Staff A attempts to confirm Store B order -> 403 Forbidden
        $orderB = Order::create([
            'store_id' => $storeB->id,
            'order_number' => 'ORD-TEST-B',
            'customer_name' => 'Customer B',
            'customer_phone' => '09222222222',
            'contact_channel' => 'viber',
            'pricing_type' => 'retail',
            'total_amount' => 5000.00,
            'status' => 'pending_contact',
        ]);

        $responseHacked = $this->actingAs($staffA)->patch('/store/store-b/admin/orders/' . $orderB->id . '/status', [
            'status' => 'confirmed',
        ]);
        $responseHacked->assertStatus(403);
    }
}
