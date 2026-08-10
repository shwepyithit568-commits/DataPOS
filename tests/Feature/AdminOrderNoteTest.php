<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminOrderNoteTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private Store $otherStore;
    private User $admin;
    private User $otherAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'DataPOS Orders', 'slug' => 'datapos-orders']);
        $this->otherStore = Store::create(['name' => 'Other Store', 'slug' => 'other-store']);

        $this->admin = User::factory()->create(['phone' => '09111113333']);
        $this->admin->stores()->attach($this->store->id, ['role' => 'store_manager']);

        $this->otherAdmin = User::factory()->create(['phone' => '09111113334']);
        $this->otherAdmin->stores()->attach($this->otherStore->id, ['role' => 'store_manager']);
    }

    private function makeOrder(Store $store): Order
    {
        return Order::create([
            'store_id' => $store->id,
            'order_number' => 'ORD-NOTE-' . strtoupper(Str::random(6)),
            'customer_name' => 'Note Buyer',
            'customer_phone' => '09123456789',
            'contact_channel' => 'viber',
            'pricing_type' => 'retail',
            'total_amount' => 15000,
            'status' => 'pending_contact',
        ]);
    }

    /** A store manager can save an internal note on an order. */
    public function test_admin_can_save_order_note(): void
    {
        $order = $this->makeOrder($this->store);

        $response = $this->actingAs($this->admin)
            ->patch("/store/{$this->store->slug}/admin/orders/{$order->id}/note", [
                'admin_note' => 'Customer wants the blue frame — confirmed over Viber.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('Customer wants the blue frame — confirmed over Viber.', $order->refresh()->admin_note);
    }

    /** Saving a blank note clears any previously saved note. */
    public function test_blank_note_clears_existing_note(): void
    {
        $order = $this->makeOrder($this->store);
        $order->update(['admin_note' => 'Old remark']);

        $this->actingAs($this->admin)
            ->patch("/store/{$this->store->slug}/admin/orders/{$order->id}/note", ['admin_note' => '   ']);

        $this->assertNull($order->refresh()->admin_note);
    }

    /** The note card renders on the order detail page. */
    public function test_order_detail_page_renders_note_card(): void
    {
        $order = $this->makeOrder($this->store);

        $response = $this->actingAs($this->admin)
            ->get("/store/{$this->store->slug}/admin/orders/{$order->id}");

        $response->assertStatus(200);
        $response->assertSee('Admin Note');
        $response->assertSee('name="admin_note"', false);
        $response->assertSee('/note"', false); // PATCH action URL
    }

    /** Managers of other stores cannot touch the note. */
    public function test_cross_store_note_update_is_forbidden(): void
    {
        $order = $this->makeOrder($this->store);

        $response = $this->actingAs($this->otherAdmin)
            ->patch("/store/{$this->otherStore->slug}/admin/orders/{$order->id}/note", [
                'admin_note' => 'Hacked remark',
            ]);

        $response->assertForbidden();
        $this->assertNull($order->refresh()->admin_note);
    }

    /** The order list shows a note marker when a note exists. */
    public function test_order_list_shows_note_marker(): void
    {
        $order = $this->makeOrder($this->store);
        $order->update(['admin_note' => 'Follow up tomorrow']);

        $response = $this->actingAs($this->admin)
            ->get("/store/{$this->store->slug}/admin/orders");

        $response->assertStatus(200);
        $response->assertSee('📝');
        $response->assertSee('Follow up tomorrow', false); // inside the title attribute
    }
}
