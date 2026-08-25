<?php

namespace Tests\Feature;

use App\Models\EloadAccount;
use App\Models\EloadTransaction;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloadRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $otherManager;
    protected Store $otherStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Eload Store 1', 'slug' => 'eload-store-1']);
        $this->store->setting()->create(['store_name' => 'Eload Store 1', 'default_language' => 'en']);

        $this->manager = User::factory()->create(['phone' => '09111222333']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->otherStore = Store::create(['name' => 'Eload Store 2', 'slug' => 'eload-store-2']);
        $this->otherStore->setting()->create(['store_name' => 'Eload Store 2', 'default_language' => 'en']);

        $this->otherManager = User::factory()->create(['phone' => '09444555666']);
        $this->otherManager->stores()->attach($this->otherStore->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    public function test_manager_can_access_eload_dashboard(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/eload");

        $response->assertOk();
        $response->assertSee(__('messages.sidebar_eload'));
    }

    public function test_creating_topup_transaction_auto_calculates_profit_and_deducts_float(): void
    {
        // Setup MPT Account with 100,000 Ks and 4% discount margin
        $account = EloadAccount::create([
            'store_id'         => $this->store->id,
            'operator'         => 'mpt',
            'name'             => 'MPT Agent SIM 1',
            'phone_number'     => '095123456',
            'balance'          => 100000.00,
            'discount_percent' => 4.00,
            'is_active'        => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/eload", [
                'operator'         => 'mpt',
                'phone_number'     => '09250123456',
                'amount'           => 10000,
                'type'             => 'topup',
                'payment_method'   => 'cash',
                'eload_account_id' => $account->id,
            ]);

        $response->assertRedirect();

        $tx = EloadTransaction::where('store_id', $this->store->id)->first();
        $this->assertNotNull($tx);
        $this->assertEquals('mpt', $tx->operator);
        $this->assertEquals('09250123456', $tx->phone_number);
        $this->assertEquals(10000.0, (float) $tx->amount);
        $this->assertEquals(9600.0, (float) $tx->cost); // 10000 * 0.96 = 9600
        $this->assertEquals(400.0, (float) $tx->profit); // 10000 - 9600 = 400
        $this->assertEquals('completed', $tx->status);

        // Verify float balance deducted
        $account->refresh();
        $this->assertEquals(90000.0, (float) $account->balance);
    }

    public function test_refilling_operator_float_balance(): void
    {
        $account = EloadAccount::create([
            'store_id'         => $this->store->id,
            'operator'         => 'atom',
            'name'             => 'ATOM SIM',
            'balance'          => 50000.00,
            'discount_percent' => 3.50,
        ]);

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/eload/refill", [
                'eload_account_id' => $account->id,
                'amount'           => 200000,
                'notes'            => 'Bank transfer refill',
            ]);

        $response->assertRedirect();

        $account->refresh();
        $this->assertEquals(250000.0, (float) $account->balance);
    }

    public function test_saving_and_managing_operator_account(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/eload/accounts", [
                'operator'         => 'ooredoo',
                'name'             => 'Ooredoo Main Agent',
                'phone_number'     => '09971234567',
                'balance'          => 300000,
                'discount_percent' => 4.0,
            ]);

        $response->assertRedirect();

        $acc = EloadAccount::where('store_id', $this->store->id)->where('operator', 'ooredoo')->first();
        $this->assertNotNull($acc);
        $this->assertEquals('Ooredoo Main Agent', $acc->name);
        $this->assertEquals(300000.0, (float) $acc->balance);
    }

    public function test_refunding_transaction_restores_operator_float(): void
    {
        $account = EloadAccount::create([
            'store_id'         => $this->store->id,
            'operator'         => 'mytel',
            'name'             => 'Mytel Float',
            'balance'          => 50000.00,
            'discount_percent' => 5.0,
        ]);

        $tx = EloadTransaction::create([
            'store_id'         => $this->store->id,
            'eload_account_id' => $account->id,
            'operator'         => 'mytel',
            'phone_number'     => '09691234567',
            'amount'           => 10000,
            'cost'             => 9500,
            'profit'           => 500,
            'status'           => 'completed',
            'occurred_at'      => now(),
        ]);

        $account->decrement('balance', 10000); // Current balance 40,000

        // Refund the transaction
        $response = $this->actingAs($this->manager)
            ->patch("/store/{$this->store->slug}/admin/eload/transactions/{$tx->id}/status", [
                'status' => 'refunded',
            ]);

        $response->assertRedirect();

        $tx->refresh();
        $this->assertEquals('refunded', $tx->status);

        $account->refresh();
        $this->assertEquals(50000.0, (float) $account->balance); // 40,000 + 10,000 restored
    }

    public function test_print_slip_renders_properly(): void
    {
        $tx = EloadTransaction::create([
            'store_id'         => $this->store->id,
            'operator'         => 'mpt',
            'phone_number'     => '09250123456',
            'amount'           => 5000,
            'cost'             => 4800,
            'profit'           => 200,
            'status'           => 'completed',
            'ref_no'           => 'EL-TEST-001',
            'occurred_at'      => now(),
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/eload/transactions/{$tx->id}/slip");

        $response->assertOk();
        $response->assertSee('EL-TEST-001');
        $response->assertSee('09250123456');
        $response->assertSee('5,000 Ks');
    }

    public function test_store_isolation(): void
    {
        $tx = EloadTransaction::create([
            'store_id'         => $this->otherStore->id,
            'operator'         => 'atom',
            'phone_number'     => '09791111111',
            'amount'           => 3000,
            'cost'             => 2895,
            'profit'           => 105,
            'status'           => 'completed',
            'occurred_at'      => now(),
        ]);

        // Manager of Store 1 cannot access Store 2's slip or update its status
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/eload/transactions/{$tx->id}/slip");

        $response->assertNotFound();
    }

    public function test_custom_discount_margin_percent_in_topup(): void
    {
        $account = EloadAccount::create([
            'store_id'         => $this->store->id,
            'operator'         => 'mytel',
            'name'             => 'Mytel Agent',
            'balance'          => 100000.00,
            'discount_percent' => 5.00,
            'is_active'        => true,
        ]);

        // Submit with custom 6.5% discount margin instead of default 5%
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/eload", [
                'operator'         => 'mytel',
                'phone_number'     => '09691234567',
                'amount'           => 10000,
                'discount_percent' => 6.5,
                'type'             => 'topup',
                'payment_method'   => 'kpay',
            ]);

        $response->assertRedirect();

        $tx = EloadTransaction::where('phone_number', '09691234567')->first();
        $this->assertNotNull($tx);
        $this->assertEquals(10000.0, (float) $tx->amount);
        $this->assertEquals(9350.0, (float) $tx->cost); // 10000 * (1 - 0.065) = 9350
        $this->assertEquals(650.0, (float) $tx->profit); // 10000 - 9350 = 650
    }
}
