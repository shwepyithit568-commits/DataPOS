<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PushNotificationLog;
use App\Models\PushSubscription;
use App\Models\Store;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use App\Notifications\OrderStatusNotification;
use App\Notifications\PaymentReceivedNotification;
use App\Notifications\TestPushNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Push Store', 'slug' => 'push-store']);

        $this->manager = User::factory()->create(['phone' => '09111110001']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager']);

        $this->customer = User::factory()->create(['phone' => '09111110002', 'role' => 'customer']);
    }

    private function subscriptionPayload(string $endpoint = 'https://push.example.com/sub/abc123'): array
    {
        return [
            'endpoint' => $endpoint,
            'keys' => [
                'p256dh' => 'BCkqQExampleP256DhKeyValueForTestingOnly_1234567890',
                'auth' => 'AuthTokenExampleValue_1234567890',
            ],
        ];
    }

    public function test_subscribe_stores_subscription_for_guest(): void
    {
        $this->postJson('/api/push/subscribe', $this->subscriptionPayload())
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => 'https://push.example.com/sub/abc123',
            'user_id' => null,
        ]);

        $subscription = PushSubscription::first();
        $this->assertSame('BCkqQExampleP256DhKeyValueForTestingOnly_1234567890', $subscription->keys['p256dh']);
        $this->assertSame('AuthTokenExampleValue_1234567890', $subscription->keys['auth']);
    }

    public function test_subscribe_attaches_authenticated_user(): void
    {
        $this->actingAs($this->manager)
            ->postJson('/api/push/subscribe', $this->subscriptionPayload('https://push.example.com/sub/user'))
            ->assertOk();

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => 'https://push.example.com/sub/user',
            'user_id' => $this->manager->id,
        ]);
    }

    public function test_subscribe_updates_existing_endpoint_instead_of_duplicating(): void
    {
        $this->postJson('/api/push/subscribe', $this->subscriptionPayload('https://push.example.com/sub/dup'))
            ->assertOk();
        $this->postJson('/api/push/subscribe', $this->subscriptionPayload('https://push.example.com/sub/dup'))
            ->assertOk();

        $this->assertSame(1, PushSubscription::where('endpoint', 'https://push.example.com/sub/dup')->count());
    }

    public function test_subscribe_validates_keys(): void
    {
        $this->postJson('/api/push/subscribe', [
            'endpoint' => 'https://push.example.com/sub/bad',
            'keys' => ['p256dh' => 'only-p256dh'],
        ])->assertStatus(422);
    }

    public function test_unsubscribe_removes_subscription(): void
    {
        $this->postJson('/api/push/subscribe', $this->subscriptionPayload('https://push.example.com/sub/del'))
            ->assertOk();

        $this->deleteJson('/api/push/unsubscribe', [
            'endpoint' => 'https://push.example.com/sub/del',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint' => 'https://push.example.com/sub/del',
        ]);
    }

    public function test_test_endpoint_rejects_non_admin(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/push/test')
            ->assertStatus(403);
    }

    public function test_test_endpoint_allows_store_manager(): void
    {
        Notification::fake();

        PushSubscription::create([
            'endpoint' => 'https://push.example.com/sub/t1',
            'keys' => ['p256dh' => 'x', 'auth' => 'y'],
        ]);

        $this->actingAs($this->manager)
            ->postJson('/api/push/test')
            ->assertOk()
            ->assertJson(['success' => true]);

        Notification::assertSentTimes(TestPushNotification::class, 1);
    }

    public function test_test_endpoint_returns_error_when_no_subscribers(): void
    {
        $this->actingAs($this->manager)
            ->postJson('/api/push/test')
            ->assertStatus(422);
    }

    public function test_model_maps_keys_json_to_channel_attributes(): void
    {
        $subscription = PushSubscription::create([
            'endpoint' => 'https://push.example.com/sub/map',
            'keys' => [
                'p256dh' => 'P256DH_VALUE',
                'auth' => 'AUTH_VALUE',
            ],
        ]);

        $this->assertSame('P256DH_VALUE', $subscription->public_key);
        $this->assertSame('AUTH_VALUE', $subscription->auth_token);
        $this->assertNull($subscription->content_encoding);
    }

    public function test_model_remove_subscription_by_endpoint(): void
    {
        PushSubscription::create([
            'endpoint' => 'https://push.example.com/sub/rm',
            'keys' => ['p256dh' => 'x', 'auth' => 'y'],
        ]);

        $this->assertTrue(PushSubscription::removeSubscription('https://push.example.com/sub/rm'));
        $this->assertFalse(PushSubscription::removeSubscription('https://push.example.com/sub/rm'));
        $this->assertSame(0, PushSubscription::count());
    }

    public function test_new_order_notification_builds_webpush_message(): void
    {
        $order = Order::create([
            'store_id' => $this->store->id,
            'order_number' => 'ORD-PUSH-1',
            'customer_name' => 'Push Buyer',
            'customer_phone' => '09111110003',
            'total_amount' => 50000.00,
            'status' => 'pending_contact',
        ]);

        $order->items()->create([
            'product_name' => 'Phone Screen',
            'unit_price' => 25000.00,
            'quantity' => 2,
            'subtotal' => 50000.00,
        ]);

        $notification = new NewOrderNotification($order);
        $message = $notification->toWebPush($this->manager, $notification)->toArray();

        $this->assertSame('🆕 အော်ဒါ #ORD-PUSH-1', $message['title']);
        $this->assertStringContainsString('ဝယ်သူ: Push Buyer', $message['body']);
        $this->assertStringContainsString('Ks 50000.00', $message['body']);
        $this->assertStringContainsString('ပစ္စည်းအရေအတွက်: 2', $message['body']);
        $this->assertStringContainsString('/icons/icon-192.png', $message['icon']);
        $this->assertStringContainsString('/icons/badge-72.png', $message['badge']);
        $this->assertStringContainsString('/store/push-store/admin/orders/' . $order->id, $message['data']['url']);
    }

    public function test_new_order_dispatch_is_deduped_and_logged(): void
    {
        Notification::fake();

        $order = Order::create([
            'store_id' => $this->store->id,
            'order_number' => 'ORD-DEDUPE-1',
            'customer_name' => 'Dedupe Buyer',
            'customer_phone' => '09111110004',
            'total_amount' => 25000.00,
            'status' => 'pending_contact',
        ]);

        $notifier = app(\App\Support\AdminPushNotifier::class);
        $notifier->dispatch($this->store, 'order-created.' . $order->id, new NewOrderNotification($order));
        $notifier->dispatch($this->store, 'order-created.' . $order->id, new NewOrderNotification($order));

        Notification::assertSentTimes(NewOrderNotification::class, 1);

        $this->assertSame(1, PushNotificationLog::count());
        $log = PushNotificationLog::first();
        $this->assertSame('order', $log->type);
        $this->assertSame('🆕 အော်ဒါ #ORD-DEDUPE-1', $log->title);
        $this->assertStringContainsString('Dedupe Buyer', $log->body);
        $this->assertSame(1, $log->recipient_count);
    }

    public function test_order_status_change_notifies_store_admin(): void
    {
        Notification::fake();

        $order = Order::create([
            'store_id' => $this->store->id,
            'order_number' => 'ORD-STATUS-1',
            'customer_name' => 'Status Buyer',
            'customer_phone' => '09111110005',
            'total_amount' => 30000.00,
            'status' => 'pending_contact',
        ]);

        $this->actingAs($this->manager)
            ->patch("/store/push-store/admin/orders/{$order->id}/status", ['status' => 'confirmed'])
            ->assertRedirect();

        Notification::assertSentTo($this->manager, OrderStatusNotification::class);
        $this->assertDatabaseHas('push_notification_logs', [
            'type' => 'status',
            'title' => '📦 အော်ဒါ #ORD-STATUS-1 သည် အတည်ပြုပြီး ဖြစ်ပါပြီ',
            'recipient_count' => 1,
        ]);
    }

    public function test_double_status_update_sends_single_notification(): void
    {
        Notification::fake();

        $order = Order::create([
            'store_id' => $this->store->id,
            'order_number' => 'ORD-DBL-1',
            'customer_name' => 'Double Buyer',
            'customer_phone' => '09111110006',
            'total_amount' => 10000.00,
            'status' => 'pending_contact',
        ]);

        $this->actingAs($this->manager)
            ->patch("/store/push-store/admin/orders/{$order->id}/status", ['status' => 'delivered'])
            ->assertRedirect();
        $this->actingAs($this->manager)
            ->patch("/store/push-store/admin/orders/{$order->id}/status", ['status' => 'delivered'])
            ->assertRedirect();

        Notification::assertSentTimes(OrderStatusNotification::class, 1);
        $this->assertSame(1, PushNotificationLog::count());
    }

    public function test_payment_received_notifies_store_admin(): void
    {
        Notification::fake();

        $order = Order::create([
            'store_id' => $this->store->id,
            'order_number' => 'ORD-PAY-1',
            'customer_name' => 'Pay Buyer',
            'customer_phone' => '09111110007',
            'total_amount' => 45000.00,
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($this->manager)
            ->patch("/store/push-store/admin/orders/{$order->id}/finances", ['payment_status' => 'paid'])
            ->assertRedirect();

        Notification::assertSentTo($this->manager, PaymentReceivedNotification::class);
        $this->assertDatabaseHas('push_notification_logs', [
            'type' => 'payment',
            'title' => '💵 ငွေပေးချေမှု ရရှိပါပြီ — အော်ဒါ #ORD-PAY-1',
            'recipient_count' => 1,
        ]);
    }

    public function test_marking_unpaid_does_not_notify(): void
    {
        Notification::fake();

        $order = Order::create([
            'store_id' => $this->store->id,
            'order_number' => 'ORD-UNPAID-1',
            'customer_name' => 'Unpaid Buyer',
            'customer_phone' => '09111110008',
            'total_amount' => 9000.00,
            'status' => 'confirmed',
        ]);

        $this->actingAs($this->manager)
            ->patch("/store/push-store/admin/orders/{$order->id}/finances", ['payment_status' => 'unpaid'])
            ->assertRedirect();

        Notification::assertNotSentTo($this->manager, PaymentReceivedNotification::class);
        $this->assertSame(0, PushNotificationLog::count());
    }

    public function test_push_history_page_lists_and_filters(): void
    {
        PushNotificationLog::create([
            'type' => 'order',
            'title' => '🆕 အော်ဒါ #HIST-1',
            'body' => 'Order log body',
            'recipient_count' => 1,
            'sent_at' => now(),
        ]);
        PushNotificationLog::create([
            'type' => 'system',
            'title' => 'System broadcast',
            'body' => 'System log body',
            'recipient_count' => 2,
            'sent_at' => now(),
        ]);

        $this->actingAs($this->manager)
            ->get('/store/push-store/admin/push/history')
            ->assertOk()
            ->assertSee('အော်ဒါ #HIST-1')
            ->assertSee('System broadcast');

        $this->actingAs($this->manager)
            ->get('/store/push-store/admin/push/history?type=order')
            ->assertOk()
            ->assertSee('အော်ဒါ #HIST-1')
            ->assertDontSee('System broadcast');
    }

    public function test_push_history_requires_admin_access(): void
    {
        $this->actingAs($this->customer)
            ->get('/store/push-store/admin/push/history')
            ->assertStatus(403);
    }

    public function test_new_order_notifies_store_admin_with_subscription(): void
    {
        Notification::fake();

        $product = \App\Models\Product::create([
            'store_id' => $this->store->id,
            'sku' => 'SKU-PUSH-1',
            'name' => 'Push Cover',
            'slug' => 'push-cover',
            'retail_price' => 5000.00,
            'wholesale_price' => 3000.00,
            'stock_status' => 'in_stock',
        ]);

        // Manager has a push subscription; customer does not.
        PushSubscription::create([
            'endpoint' => 'https://push.example.com/sub/manager',
            'keys' => ['p256dh' => 'x', 'auth' => 'y'],
            'user_id' => $this->manager->id,
        ]);

        // Order creation via the storefront route — the dispatch point.
        $this->post('/store/push-store/orders', [
            'product_id' => $product->id,
            'customer_name' => 'Push Buyer',
            'customer_phone' => '09111110003',
            'customer_address' => 'Yangon',
            'contact_channel' => 'viber',
            'quantity' => 1,
        ])->assertRedirect();

        Notification::assertSentTo($this->manager, NewOrderNotification::class);
        Notification::assertNotSentTo($this->customer, NewOrderNotification::class);

        $this->assertDatabaseHas('orders', [
            'store_id' => $this->store->id,
            'customer_name' => 'Push Buyer',
            'total_amount' => 5000.00,
        ]);
    }
}
