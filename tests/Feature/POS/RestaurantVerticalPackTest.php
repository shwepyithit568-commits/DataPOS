<?php

namespace Tests\Feature\POS;

use App\Models\KitchenOrderTicket;
use App\Models\RestaurantTable;
use App\Models\Store;
use App\Services\RestaurantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RestaurantVerticalPackTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected RestaurantService $restaurantService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name'             => 'Golden Palace Restaurant & Cafe',
            'slug'             => 'golden-palace',
            'business_profile' => 'general_retail',
            'is_active'        => true,
        ]);

        $this->restaurantService = app(RestaurantService::class);
    }

    public function test_restaurant_table_lifecycle_management(): void
    {
        $table = RestaurantTable::create([
            'store_id' => $this->store->id,
            'name'     => 'VIP Table 01',
            'zone'     => 'VIP Room',
            'capacity' => 8,
            'status'   => 'available',
        ]);

        $this->assertTrue($table->isAvailable());
        $this->assertFalse($table->isOccupied());

        // 1. Occupy
        $this->restaurantService->occupyTable($table, 'SES-VIP-101');
        $table->refresh();

        $this->assertTrue($table->isOccupied());
        $this->assertSame('SES-VIP-101', $table->active_session_id);

        // 2. Release
        $this->restaurantService->releaseTable($table);
        $table->refresh();

        $this->assertTrue($table->isAvailable());
        $this->assertNull($table->active_session_id);
    }

    public function test_kitchen_order_ticket_creation_with_modifiers_and_auto_table_occupy(): void
    {
        $table = RestaurantTable::create([
            'store_id' => $this->store->id,
            'name'     => 'Table 05',
            'zone'     => 'Indoor Main Hall',
            'capacity' => 4,
            'status'   => 'available',
        ]);

        $kotData = [
            'table_id'   => $table->id,
            'order_type' => 'dine_in',
            'items'      => [
                [
                    'name'      => 'Chicken Biryani',
                    'qty'       => 2,
                    'modifiers' => 'Extra spicy, no cucumber',
                ],
                [
                    'name'      => 'Iced Lemon Tea',
                    'qty'       => 2,
                    'modifiers' => 'Less sugar, no ice',
                ],
            ],
            'notes'      => 'Rush order for VIP guests',
        ];

        $kot = $this->restaurantService->createKot($this->store, $kotData);

        $this->assertNotNull($kot);
        $this->assertStringStartsWith('KOT-', $kot->ticket_number);
        $this->assertSame('pending', $kot->status);
        $this->assertCount(2, $kot->items);

        // Assert Table was automatically occupied
        $table->refresh();
        $this->assertTrue($table->isOccupied());
    }

    public function test_kitchen_order_ticket_status_workflow(): void
    {
        $kot = KitchenOrderTicket::create([
            'store_id'      => $this->store->id,
            'ticket_number' => 'KOT-260829-001',
            'order_type'    => 'dine_in',
            'items'         => [['name' => 'Fried Rice', 'qty' => 1]],
            'status'        => 'pending',
        ]);

        // Advance to preparing
        $this->restaurantService->updateKotStatus($kot, 'preparing');
        $this->assertSame('preparing', $kot->fresh()->status);

        // Advance to ready
        $this->restaurantService->updateKotStatus($kot, 'ready');
        $this->assertSame('ready', $kot->fresh()->status);

        // Advance to served
        $this->restaurantService->updateKotStatus($kot, 'served');
        $this->assertSame('served', $kot->fresh()->status);

        // Invalid status throws ValidationException
        $this->expectException(ValidationException::class);
        $this->restaurantService->updateKotStatus($kot, 'invalid_status_xyz');
    }

    public function test_kot_escpos_kitchen_thermal_ticket_generation(): void
    {
        $table = RestaurantTable::create([
            'store_id' => $this->store->id,
            'name'     => 'Table 12',
            'zone'     => 'Outdoor Terrace',
            'capacity' => 4,
        ]);

        $kot = KitchenOrderTicket::create([
            'store_id'      => $this->store->id,
            'table_id'      => $table->id,
            'ticket_number' => 'KOT-260829-099',
            'order_type'    => 'dine_in',
            'items'         => [
                [
                    'name'      => 'Spicy Seafood Soup',
                    'qty'       => 1,
                    'modifiers' => 'Very spicy, extra lime',
                ],
            ],
            'notes'         => 'Serve with extra small bowls',
        ]);

        $escpos = $this->restaurantService->generateKotEscPos($kot);

        $this->assertStringContainsString('*** KITCHEN ORDER ***', $escpos);
        $this->assertStringContainsString('KOT-260829-099', $escpos);
        $this->assertStringContainsString('Table 12 (Outdoor Terrace)', $escpos);
        $this->assertStringContainsString('1x Spicy Seafood Soup', $escpos);
        $this->assertStringContainsString('>> NOTE: Very spicy, extra lime', $escpos);
        $this->assertStringContainsString('Serve with extra small bowls', $escpos);
        $this->assertStringContainsString("\x1DV\x41\x00", $escpos); // Paper cut
    }

    public function test_bill_splitting_calculation(): void
    {
        // 1. Equal split with zero remainder
        $split1 = $this->restaurantService->calculateSplitBill(45000, 4);
        $this->assertSame(4, $split1['split_count']);
        $this->assertSame(11250.0, $split1['per_person_amount']);
        $this->assertSame(0.0, $split1['remainder']);

        // 2. Split with remainder
        $split2 = $this->restaurantService->calculateSplitBill(10000, 3);
        $this->assertSame(3, $split2['split_count']);
        $this->assertSame(3333.0, $split2['per_person_amount']);
        $this->assertSame(1.0, $split2['remainder']);
    }
}
