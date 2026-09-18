<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Nobody gets an account by typing a phone number.
 *
 * Registration used to merge by phone number and overwrite the password of any
 * existing non-staff account, so anyone who knew a customer's number could take
 * the account over (measured: the victim's own password stopped working) — and
 * the account page then adopted that phone's earlier orders. There is no
 * SMS/Viber verification in the app yet, so the storefront may only claim an
 * account that is still empty, and the shop keeps the reset path.
 */
class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Security Shop',
            'slug' => 'security-shop',
            'is_active' => true,
        ]);
    }

    private function payload(string $phone, string $name = 'Claimant', string $password = 'Customer@2026'): array
    {
        return [
            'name' => $name,
            'phone' => $phone,
            'password' => $password,
            'password_confirmation' => $password,
        ];
    }

    /** A customer who registered online once — a password they chose exists. */
    private function registeredCustomer(string $phone): User
    {
        return User::create([
            'name' => 'Real Owner',
            'phone' => $phone,
            'password' => bcrypt('OwnerPass!1'),
            'password_set_at' => now()->subDay(),
            'role' => 'customer',
        ]);
    }

    /** A customer the cashier created at the counter: placeholder password. */
    private function counterCustomer(string $phone): User
    {
        $user = User::create([
            'name' => 'Counter Customer',
            'phone' => $phone,
            'password' => bcrypt(\Illuminate\Support\Str::random(24)),
            'role' => 'customer',
        ]);
        $user->stores()->attach($this->store->id, ['role' => 'retail_customer', 'status' => 'active']);

        return $user;
    }

    /* ------------------------------------------------------------------ */
    /*  Registration                                                       */
    /* ------------------------------------------------------------------ */

    public function test_registering_a_phone_that_already_has_a_password_cannot_take_the_account_over(): void
    {
        $victim = $this->registeredCustomer('09970000901');

        $response = $this->post('/register', $this->payload('09970000901', 'Attacker', 'AttackerPass!1'));

        $response->assertSessionHasErrors('phone');
        $this->assertGuest();

        $fresh = $victim->fresh();
        $this->assertSame('Real Owner', $fresh->name);
        $this->assertTrue(Hash::check('OwnerPass!1', $fresh->password), 'the owner password must survive');
        $this->assertFalse(Hash::check('AttackerPass!1', $fresh->password));
    }

    public function test_a_counter_account_that_already_holds_points_cannot_be_claimed_from_the_storefront(): void
    {
        $counter = $this->counterCustomer('09970000902');
        $counter->stores()->updateExistingPivot($this->store->id, ['loyalty_points' => 120]);

        $this->post('/register', $this->payload('09970000902'))->assertSessionHasErrors('phone');

        $this->assertSame(120, (int) \Illuminate\Support\Facades\DB::table('store_user')->where('user_id', $counter->id)->where('store_id', $this->store->id)->value('loyalty_points'));
        $this->assertNull($counter->fresh()->password_set_at);
    }

    public function test_a_counter_account_that_already_has_orders_cannot_be_claimed_from_the_storefront(): void
    {
        $counter = $this->counterCustomer('09970000903');

        Order::create([
            'store_id' => $this->store->id,
            'user_id' => $counter->id,
            'order_number' => 'ORD-SEC' . rand(1000, 9999),
            'customer_name' => 'Counter Customer',
            'customer_phone' => '09970000903',
            'contact_channel' => 'phone',
            'pricing_type' => 'retail',
            'total_amount' => 25000,
            'payment_status' => 'unpaid',
            'status' => 'pending_contact',
        ]);

        $this->post('/register', $this->payload('09970000903'))->assertSessionHasErrors('phone');
    }

    public function test_an_empty_counter_account_can_still_be_claimed_online(): void
    {
        $counter = $this->counterCustomer('09970000904');

        $this->post('/register', $this->payload('09970000904', 'Real Customer', 'MyPass!2345'))
            ->assertRedirect('/store/security-shop');

        $fresh = $counter->fresh();
        $this->assertSame($counter->id, $fresh->id, 'one record per phone — the claim merges, it does not duplicate');
        $this->assertSame('Real Customer', $fresh->name);
        $this->assertTrue(Hash::check('MyPass!2345', $fresh->password));
        $this->assertNotNull($fresh->password_set_at, 'the claim marks the account as owned');
        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $this->store->id,
            'action' => 'customer_account_claimed',
            'entity_id' => $counter->id,
        ]);
    }

    public function test_a_fresh_registration_marks_the_password_as_chosen(): void
    {
        $this->post('/register', $this->payload('09970000905'));

        $user = User::where('phone', '09970000905')->sole();
        $this->assertNotNull($user->password_set_at);
    }

    public function test_a_staff_phone_still_cannot_be_claimed(): void
    {
        $manager = User::create([
            'name' => 'Manager',
            'phone' => '09970000906',
            'password' => bcrypt('ManagerPass!1'),
            'password_set_at' => now(),
            'role' => 'customer',
        ]);
        $manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->post('/register', $this->payload('09970000906'))->assertSessionHasErrors('phone');
    }

    /**
     * The store OWNER too, and even with an empty account: the guard used to
     * list only store_manager/staff, so an owner was claimable from the
     * storefront as long as they had no orders yet.
     */
    public function test_a_store_owner_phone_cannot_be_claimed_even_with_an_empty_account(): void
    {
        $owner = User::create([
            'name' => 'Shop Owner',
            'phone' => '09970000913',
            'password' => bcrypt('OwnerPass!1'),
            'role' => 'customer',
        ]);
        $owner->stores()->attach($this->store->id, ['role' => 'store_owner', 'status' => 'active']);

        $this->post('/register', $this->payload('09970000913'))->assertSessionHasErrors('phone');

        $this->assertTrue(Hash::check('OwnerPass!1', $owner->fresh()->password));
    }

    /* ------------------------------------------------------------------ */
    /*  Order history                                                      */
    /* ------------------------------------------------------------------ */

    public function test_the_account_page_adopts_only_orders_placed_after_the_account_existed(): void
    {
        $shopper = $this->registeredCustomer('09970000907');

        // A guest order from BEFORE the account: not this account's to show.
        $old = Order::create([
            'store_id' => $this->store->id,
            'user_id' => null,
            'order_number' => 'ORD-OLD' . rand(1000, 9999),
            'customer_name' => 'Someone Else',
            'customer_phone' => '09970000907',
            'contact_channel' => 'phone',
            'pricing_type' => 'retail',
            'total_amount' => 19000,
            'payment_status' => 'unpaid',
            'status' => 'pending_contact',
        ]);
        $old->created_at = now()->subDays(3);
        $old->save();

        // A guest order placed after the account existed (they forgot to sign in).
        $recent = Order::create([
            'store_id' => $this->store->id,
            'user_id' => null,
            'order_number' => 'ORD-NEW' . rand(1000, 9999),
            'customer_name' => 'Real Owner',
            'customer_phone' => '09970000907',
            'contact_channel' => 'phone',
            'pricing_type' => 'retail',
            'total_amount' => 21000,
            'payment_status' => 'unpaid',
            'status' => 'pending_contact',
        ]);
        $recent->created_at = now()->addMinute();
        $recent->save();

        $this->actingAs($shopper)->get('/account/orders?store_slug=security-shop')->assertOk();

        $this->assertNull($old->fresh()->user_id, 'an older stranger order must not be adopted');
        $this->assertSame($shopper->id, $recent->fresh()->user_id, 'their own later order is linked');
    }

    /* ------------------------------------------------------------------ */
    /*  Staff reset (the recovery path)                                     */
    /* ------------------------------------------------------------------ */

    public function test_staff_can_reset_a_customers_password(): void
    {
        $staff = User::create([
            'name' => 'Directory Staff',
            'phone' => '09970000908',
            'password' => bcrypt('StaffPass!1'),
            'password_set_at' => now(),
            'role' => 'customer',
        ]);
        $staff->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $customer = $this->registeredCustomer('09970000909');
        $customer->stores()->attach($this->store->id, ['role' => 'retail_customer', 'status' => 'active']);

        $this->actingAs($staff)->put("/store/security-shop/admin/customers/{$customer->id}", [
            'name' => 'Real Owner',
            'phone' => '09970000909',
            'role' => 'retail_customer',
            'status' => 'active',
            'new_password' => 'TempPass!2026',
        ])->assertRedirect();

        $fresh = $customer->fresh();
        $this->assertTrue(Hash::check('TempPass!2026', $fresh->password));
        $this->assertFalse(Hash::check('OwnerPass!1', $fresh->password));
        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $this->store->id,
            'action' => 'customer_password_reset',
            'entity_id' => $customer->id,
        ]);
    }

    public function test_a_staff_update_without_a_password_leaves_it_alone(): void
    {
        $staff = User::create([
            'name' => 'Directory Staff',
            'phone' => '09970000910',
            'password' => bcrypt('StaffPass!1'),
            'password_set_at' => now(),
            'role' => 'customer',
        ]);
        $staff->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $customer = $this->registeredCustomer('09970000911');
        $customer->stores()->attach($this->store->id, ['role' => 'retail_customer', 'status' => 'active']);

        $this->actingAs($staff)->put("/store/security-shop/admin/customers/{$customer->id}", [
            'name' => 'Renamed Only',
            'phone' => '09970000911',
            'role' => 'retail_customer',
            'status' => 'active',
        ])->assertRedirect();

        $fresh = $customer->fresh();
        $this->assertSame('Renamed Only', $fresh->name);
        $this->assertTrue(Hash::check('OwnerPass!1', $fresh->password));
    }

}
