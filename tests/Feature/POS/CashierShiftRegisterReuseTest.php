<?php

namespace Tests\Feature\POS;

use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\CashierShift;
use App\POS\Services\CashierShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regression: the same register must be openable/closable day after day.
 *
 * The old unique(store_id, register_name, status) index made every closed shift
 * collide with the previous one, so a register could only ever be closed once.
 */
class CashierShiftRegisterReuseTest extends TestCase
{
    use RefreshDatabase;

    private CashierShiftService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CashierShiftService::class);
    }

    private function store(string $slug = 'shop-a'): Store
    {
        return Store::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true]);
    }

    private function cashier(Store $store): User
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

    public function test_same_register_can_be_closed_more_than_once(): void
    {
        $store = $this->store();
        $cashier = $this->cashier($store);

        foreach ([50000, 60000, 70000] as $openingCash) {
            $shift = $this->service->openShift(
                $store,
                ['register_name' => 'Register 1', 'opening_cash' => $openingCash],
                $cashier
            );

            $this->service->closeShift($shift, ['actual_closing_amount' => $openingCash], $cashier);
        }

        $this->assertSame(3, CashierShift::count());
        $this->assertSame(0, CashierShift::where('status', 'open')->count());
        $this->assertSame(3, CashierShift::where('status', 'closed')->count());
    }

    public function test_register_reopens_the_next_day(): void
    {
        $store = $this->store();
        $cashier = $this->cashier($store);

        $day1 = $this->service->openShift($store, ['register_name' => 'Counter A', 'opening_cash' => 10000], $cashier);
        $this->service->closeShift($day1, ['actual_closing_amount' => 10000], $cashier);

        $day2 = $this->service->openShift($store, ['register_name' => 'Counter A', 'opening_cash' => 20000], $cashier);
        $this->assertTrue($day2->isOpen());
        $this->assertNotSame($day1->id, $day2->id);
    }

    public function test_only_one_open_shift_per_register_is_allowed(): void
    {
        $store = $this->store();
        $cashier = $this->cashier($store);

        $this->service->openShift($store, ['register_name' => 'Register 1', 'opening_cash' => 10000], $cashier);

        $this->expectException(InventoryException::class);

        $this->service->openShift($store, ['register_name' => 'Register 1', 'opening_cash' => 20000], $cashier);
    }

    public function test_two_registers_can_be_open_at_once(): void
    {
        $store = $this->store();
        $cashier = $this->cashier($store);

        $this->service->openShift($store, ['register_name' => 'Register 1', 'opening_cash' => 10000], $cashier);
        $second = $this->service->openShift($store, ['register_name' => 'Register 2', 'opening_cash' => 5000], $cashier);

        $this->assertTrue($second->isOpen());
        $this->assertSame(2, CashierShift::where('status', 'open')->count());
    }

    public function test_two_stores_may_use_the_same_register_name(): void
    {
        $storeA = $this->store('shop-a');
        $storeB = $this->store('shop-b');

        $this->service->openShift($storeA, ['register_name' => 'Register 1', 'opening_cash' => 1000], $this->cashier($storeA));
        $this->service->openShift($storeB, ['register_name' => 'Register 1', 'opening_cash' => 1000], $this->cashier($storeB));

        $this->assertSame(2, CashierShift::where('status', 'open')->count());
    }

    public function test_open_shift_key_is_null_once_closed(): void
    {
        $store = $this->store();
        $cashier = $this->cashier($store);

        $shift = $this->service->openShift($store, ['register_name' => 'Register 1', 'opening_cash' => 1000], $cashier);
        $this->assertSame($store->id . ':Register 1', $shift->fresh()->open_shift_key);

        $this->service->closeShift($shift, ['actual_closing_amount' => 1000], $cashier);
        $this->assertNull($shift->fresh()->open_shift_key);
    }
}
