<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Verifies that the add_order_confirmation_token migration safely handles
 * databases that already contain orders.
 *
 * Unlike typical RefreshDatabase tests that run all migrations first,
 * this test simulates the real upgrade path:
 *  1. Orders table is dropped down to its pre-token schema.
 *  2. Existing orders are inserted without a confirmation_token column.
 *  3. The actual migration file is loaded and its up() method executed.
 *  4. Token assignment and constraints are verified.
 *  5. The down() method is tested for safe rollback.
 */
class MigrationSafetyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Simulate the pre-migration state by dropping the confirmation_token
     * column and its unique index from the orders table.
     */
    private function downgradeOrderTable(): void
    {
        // SQLite requires dropping the index before the column
        if (Schema::hasIndex('orders', 'orders_confirmation_token_unique')) {
            Schema::table('orders', function ($table) {
                $table->dropUnique('orders_confirmation_token_unique');
            });
        }

        if (Schema::hasColumn('orders', 'confirmation_token')) {
            Schema::table('orders', function ($table) {
                $table->dropColumn('confirmation_token');
            });
        }
    }

    /**
     * Load the actual migration file and return an instance of its anonymous class.
     */
    private function loadMigration(): object
    {
        return require base_path('database/migrations/2026_07_28_030000_add_order_confirmation_token.php');
    }

    public function test_upgrade_path_backfills_existing_orders(): void
    {
        $store = Store::create(['name' => 'Test Store', 'slug' => 'test-store']);

        // 1. Downgrade the orders table to pre-token schema
        $this->downgradeOrderTable();
        $this->assertFalse(Schema::hasColumn('orders', 'confirmation_token'));

        // 2. Insert three "existing" orders without confirmation_token
        $orderIds = [];
        for ($i = 1; $i <= 3; $i++) {
            $orderIds[] = DB::table('orders')->insertGetId([
                'store_id' => $store->id,
                'order_number' => "ORD-UPGRADE-{$i}",
                'customer_name' => "Upgrade Customer {$i}",
                'customer_phone' => "0911111111{$i}",
                'contact_channel' => 'viber',
                'pricing_type' => 'retail',
                'total_amount' => 5000 * $i,
                'status' => 'pending_contact',
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ]);
        }
        $this->assertCount(3, $orderIds);

        // 3. Run the actual migration's up() method
        $migration = $this->loadMigration();
        $migration->up();

        // 4. Verify the column now exists
        $this->assertTrue(Schema::hasColumn('orders', 'confirmation_token'));

        // 5. Verify all existing rows received non-null, unique, 40-character tokens
        $rows = DB::table('orders')->whereIn('id', $orderIds)->orderBy('id')->get();
        $this->assertCount(3, $rows);

        $tokens = [];
        foreach ($rows as $row) {
            $this->assertNotNull($row->confirmation_token, "Order {$row->id} must have a token");
            $this->assertEquals(40, strlen($row->confirmation_token),
                "Token for order {$row->id} must be exactly 40 characters");
            $tokens[] = $row->confirmation_token;
        }

        // 6. All tokens must be unique
        $this->assertCount(3, array_unique($tokens));

        // 7. Verify that duplicate tokens are rejected by the unique index
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('orders')->where('id', $orderIds[0])->update(['confirmation_token' => $tokens[1]]);
    }

    public function test_upgrade_path_rollback(): void
    {
        $store = Store::create(['name' => 'Rollback Store', 'slug' => 'rollback-store']);

        // 1. Start pre-migration state
        $this->downgradeOrderTable();

        // 2. Insert an order
        DB::table('orders')->insert([
            'store_id' => $store->id,
            'order_number' => 'ORD-ROLLBACK-1',
            'customer_name' => 'Rollback Customer',
            'customer_phone' => '09111111111',
            'contact_channel' => 'viber',
            'pricing_type' => 'retail',
            'total_amount' => 10000.00,
            'status' => 'pending_contact',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Run up()
        $migration = $this->loadMigration();
        $migration->up();

        $this->assertTrue(Schema::hasColumn('orders', 'confirmation_token'));

        // 4. Run down() — must succeed without error
        $migration->down();

        // 5. After rollback, the column must be gone
        $this->assertFalse(Schema::hasColumn('orders', 'confirmation_token'));

        // 6. The original order data must still be intact
        $this->assertDatabaseHas('orders', ['order_number' => 'ORD-ROLLBACK-1']);
    }

    public function test_new_orders_via_model_get_tokens(): void
    {
        $store = Store::create(['name' => 'Model Store', 'slug' => 'model-store']);

        $orderA = Order::create([
            'store_id' => $store->id,
            'order_number' => 'ORD-MODEL-A',
            'customer_name' => 'Model A',
            'customer_phone' => '09111111111',
            'contact_channel' => 'viber',
            'pricing_type' => 'retail',
            'total_amount' => 10000.00,
            'status' => 'pending_contact',
        ]);

        $orderB = Order::create([
            'store_id' => $store->id,
            'order_number' => 'ORD-MODEL-B',
            'customer_name' => 'Model B',
            'customer_phone' => '09222222222',
            'contact_channel' => 'telegram',
            'pricing_type' => 'wholesale',
            'total_amount' => 20000.00,
            'status' => 'pending_contact',
        ]);

        // Both tokens are exactly 40 characters
        $this->assertEquals(40, strlen($orderA->confirmation_token));
        $this->assertEquals(40, strlen($orderB->confirmation_token));

        // Tokens are unique
        $this->assertNotEquals($orderA->confirmation_token, $orderB->confirmation_token);

        // Tokens work for confirmation
        $response = $this->get("/store/model-store/orders/{$orderA->id}/confirmation?token={$orderA->confirmation_token}");
        $response->assertStatus(200);
        $response->assertSee(__('messages.order_summary'));
    }

    public function test_mass_assignment_protection(): void
    {
        $order = new Order();
        $this->assertNotContains('confirmation_token', $order->getFillable());

        $store = Store::create(['name' => 'MA Store', 'slug' => 'ma-store']);
        $injectedToken = 'injected-by-client-1234567890abcdef';

        // Manually create order via DB to inject token directly
        $orderId = DB::table('orders')->insertGetId([
            'store_id' => $store->id,
            'order_number' => 'ORD-MA-001',
            'customer_name' => 'MA Customer',
            'customer_phone' => '09111111111',
            'contact_channel' => 'viber',
            'pricing_type' => 'retail',
            'total_amount' => 10000.00,
            'status' => 'pending_contact',
            'confirmation_token' => $injectedToken,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify injected token is stored (simulates what would happen if fillable)
        $this->assertEquals($injectedToken, DB::table('orders')->where('id', $orderId)->value('confirmation_token'));

        // Verify the model's boot() would override it — create via model, can't control
        $orderViaModel = Order::create([
            'store_id' => $store->id,
            'order_number' => 'ORD-MA-002',
            'customer_name' => 'MA Customer 2',
            'customer_phone' => '09222222222',
            'contact_channel' => 'telegram',
            'pricing_type' => 'retail',
            'total_amount' => 20000.00,
            'status' => 'pending_contact',
        ]);

        $this->assertEquals(40, strlen($orderViaModel->confirmation_token));
        $this->assertNotEquals($injectedToken, $orderViaModel->confirmation_token);
    }

    public function test_duplicate_tokens_rejected_by_database(): void
    {
        $store = Store::create(['name' => 'Dup Store', 'slug' => 'dup-store']);

        $order = Order::create([
            'store_id' => $store->id,
            'order_number' => 'ORD-DUP-001',
            'customer_name' => 'Dup Customer',
            'customer_phone' => '09111111111',
            'contact_channel' => 'viber',
            'pricing_type' => 'retail',
            'total_amount' => 10000.00,
            'status' => 'pending_contact',
        ]);

        $this->assertEquals(40, strlen($order->confirmation_token));

        // Force duplicate token via DB query — must throw
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('orders')->insert([
            'store_id' => $store->id,
            'order_number' => 'ORD-DUP-002',
            'customer_name' => 'Dup Customer 2',
            'customer_phone' => '09222222222',
            'contact_channel' => 'telegram',
            'pricing_type' => 'retail',
            'total_amount' => 20000.00,
            'status' => 'pending_contact',
            'confirmation_token' => $order->confirmation_token,
        ]);
    }
}
