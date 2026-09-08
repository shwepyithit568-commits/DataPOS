<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\StaffRole;
use App\Models\Store;
use App\Models\StorefrontSetting;
use App\Models\Supplier;
use App\Models\User;
use App\POS\Enums\InventoryMovementType;
use App\POS\Models\CashierShift;
use App\POS\Services\CashierShiftService;
use App\POS\Services\InventoryService;
use App\POS\Services\StoreLocationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UatCoordinatorSetupSeeder extends Seeder
{
    public const RUN_ID = 'UAT-20260908-0733';

    public function run(): void
    {
        $now = Carbon::now();

        // 1. Provision Store A (UAT-POS-STORE)
        $storeA = Store::firstOrCreate(
            ['slug' => 'uat-pos-store'],
            [
                'name'              => 'UAT-POS-STORE',
                'business_profile'  => 'mobile_electronics',
                'viber_number'      => '09850000001',
                'telegram_username' => 'uat_pos_store',
                'is_active'         => true,
                'is_primary'        => false,
            ]
        );

        $locationService = app(StoreLocationService::class);
        $locA = $locationService->ensureDefaults($storeA);
        $whA = $locA['warehouse'];
        $branchA = $locA['branch'];

        StorefrontSetting::firstOrCreate(
            ['store_id' => $storeA->id],
            [
                'store_name'          => 'UAT-POS-STORE',
                'tagline'              => 'UAT Acceptance Testing Store A',
                'phone'                => '09850000001',
                'default_language'     => 'my',
                'theme_preset'        => 'midnight_tech',
                'theme_primary_color' => '#3b82f6',
                'theme_accent_color'  => '#10b981',
                'theme_header_bg'     => '#1e293b',
                'theme_body_bg'       => '#0f172a',
                'theme_glow_style'    => 'subtle',
                'theme_dark_mode'     => true,
                'font_preset'         => 'outfit',
                'grid_density'        => 'compact',
            ]
        );

        // 2. Provision Store B (UAT-ISOLATION-STORE)
        $storeB = Store::firstOrCreate(
            ['slug' => 'uat-isolation-store'],
            [
                'name'              => 'UAT-ISOLATION-STORE',
                'business_profile'  => 'general_retail',
                'viber_number'      => '09850000002',
                'telegram_username' => 'uat_isolation_store',
                'is_active'         => true,
                'is_primary'        => false,
            ]
        );

        $locationService->ensureDefaults($storeB);

        StorefrontSetting::firstOrCreate(
            ['store_id' => $storeB->id],
            [
                'store_name'          => 'UAT-ISOLATION-STORE',
                'tagline'              => 'UAT Cross-Store Isolation Store B',
                'phone'                => '09850000002',
                'default_language'     => 'my',
                'theme_preset'        => 'retail_trust',
                'theme_primary_color' => '#059669',
                'theme_accent_color'  => '#f59e0b',
                'theme_header_bg'     => '#ffffff',
                'theme_body_bg'       => '#f8fafc',
                'theme_glow_style'    => 'none',
                'theme_dark_mode'     => false,
                'font_preset'         => 'inter',
                'grid_density'        => 'comfortable',
            ]
        );

        // 3. Bootstrap Staff Roles for Store A & B
        StaffRole::bootstrapDefaultRoles($storeA);
        StaffRole::bootstrapDefaultRoles($storeB);

        // Add Ecommerce Staff role to Store A
        $ecomRole = StaffRole::firstOrCreate(
            ['store_id' => $storeA->id, 'slug' => 'ecommerce_staff'],
            [
                'name'        => 'Ecommerce Staff / အွန်လိုင်းအရောင်း ဝန်ထမ်း',
                'description' => 'Processes online orders, customer chats, shipping and deliveries.',
                'color'       => '#ec4899',
                'permissions' => [
                    'orders.view',
                    'orders.create',
                    'orders.update',
                    'orders.edit',
                    'ecommerce_orders.view',
                    'ecommerce_orders.create',
                    'ecommerce_orders.update',
                    'ecommerce_orders.edit',
                    'products.view',
                    'customers.view',
                    'shipping_rates.view',
                    'storefront_design.view',
                ],
                'is_system'   => false,
                'is_active'   => true,
            ]
        );

        // Add Restricted Custom Role to Store A
        $restrictedRole = StaffRole::firstOrCreate(
            ['store_id' => $storeA->id, 'slug' => 'restricted_catalog_viewer'],
            [
                'name'        => 'Restricted Catalog Viewer / ကန့်သတ် ဝန်ထမ်း',
                'description' => 'Can only view products catalog and stock balance. No edits or financial views.',
                'color'       => '#64748b',
                'permissions' => [
                    'products.view',
                    'stock_balance.view',
                ],
                'is_system'   => false,
                'is_active'   => true,
            ]
        );

        $staffRolesA = StaffRole::where('store_id', $storeA->id)->pluck('id', 'slug')->all();

        // 4. Create Test Users
        $usersData = [
            'platform_owner' => [
                'phone' => '09100000001',
                'name'  => 'Owner (Platform Admin)',
                'role'  => 'platform_owner',
            ],
            'store_owner' => [
                'phone' => '09800000001',
                'name'  => 'UAT Store Owner',
                'role'  => 'customer',
                'store_role' => 'store_owner',
                'staff_role_id' => $staffRolesA['store_owner'] ?? null,
            ],
            'store_manager' => [
                'phone' => '09800000002',
                'name'  => 'UAT Store Manager',
                'role'  => 'customer',
                'store_role' => 'store_manager',
                'staff_role_id' => $staffRolesA['store_manager'] ?? null,
            ],
            'cashier' => [
                'phone' => '09800000003',
                'name'  => 'UAT Cashier',
                'role'  => 'customer',
                'store_role' => 'staff',
                'staff_role_id' => $staffRolesA['cashier'] ?? null,
            ],
            'inventory_staff' => [
                'phone' => '09800000004',
                'name'  => 'UAT Inventory Staff',
                'role'  => 'customer',
                'store_role' => 'staff',
                'staff_role_id' => $staffRolesA['stock_keeper'] ?? null,
            ],
            'accountant' => [
                'phone' => '09800000005',
                'name'  => 'UAT Accountant',
                'role'  => 'customer',
                'store_role' => 'staff',
                'staff_role_id' => $staffRolesA['accountant'] ?? null,
            ],
            'technician' => [
                'phone' => '09800000006',
                'name'  => 'UAT Technician',
                'role'  => 'customer',
                'store_role' => 'staff',
                'staff_role_id' => $staffRolesA['technician'] ?? null,
            ],
            'ecommerce_staff' => [
                'phone' => '09800000007',
                'name'  => 'UAT Ecommerce Staff',
                'role'  => 'customer',
                'store_role' => 'staff',
                'staff_role_id' => $ecomRole->id,
            ],
            'restricted_role' => [
                'phone' => '09800000008',
                'name'  => 'UAT Restricted Staff',
                'role'  => 'customer',
                'store_role' => 'staff',
                'staff_role_id' => $restrictedRole->id,
            ],
        ];

        $createdUsers = [];
        foreach ($usersData as $key => $u) {
            $user = User::firstOrCreate(
                ['phone' => $u['phone']],
                [
                    'name'     => $u['name'],
                    'password' => Hash::make('password'),
                    'pos_pin'  => Hash::make('1234'),
                    'role'     => $u['role'],
                ]
            );

            $createdUsers[$key] = $user;

            if (isset($u['store_role'])) {
                DB::table('store_user')->updateOrInsert(
                    ['store_id' => $storeA->id, 'user_id' => $user->id],
                    [
                        'role'          => $u['store_role'],
                        'staff_role_id' => $u['staff_role_id'],
                        'status'        => 'active',
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]
                );
            }
        }

        // 5. Shared Test Dataset
        // Supplier
        $supplier = Supplier::firstOrCreate(
            ['store_id' => $storeA->id, 'name' => 'UAT Supplier'],
            [
                'contact_person' => 'UAT Supplier Rep',
                'phone'          => '09811111111',
                'email'          => 'uat_supplier@datapos.local',
                'address'        => 'UAT Industrial Zone, Yangon',
                'notes'          => 'Standard UAT Supplier',
            ]
        );

        // Customer (User with retail_customer role in store_user)
        $customerUser = User::firstOrCreate(
            ['phone' => '09822222222'],
            [
                'name'     => 'UAT Customer',
                'password' => Hash::make('password'),
                'role'     => 'customer',
            ]
        );

        DB::table('store_user')->updateOrInsert(
            ['store_id' => $storeA->id, 'user_id' => $customerUser->id],
            [
                'role'          => 'retail_customer',
                'staff_role_id' => null,
                'status'        => 'active',
                'created_at'    => $now,
                'updated_at'    => $now,
            ]
        );

        // Category & Brand for Store A
        $catPhones = Category::firstOrCreate(
            ['store_id' => $storeA->id, 'slug' => 'smartphones'],
            ['name' => 'Smartphones', 'description' => 'Mobile Phones']
        );
        $catAccessories = Category::firstOrCreate(
            ['store_id' => $storeA->id, 'slug' => 'accessories'],
            ['name' => 'Accessories', 'description' => 'Phone Accessories']
        );
        $brandA = Brand::firstOrCreate(
            ['store_id' => $storeA->id, 'slug' => 'uat-brand'],
            ['name' => 'UAT Brand']
        );

        // Product A: SKU: UAT-PHONE-001, Cost: MMK 300,000, Selling: MMK 350,000, Opening Qty: 10
        $productA = Product::firstOrCreate(
            ['store_id' => $storeA->id, 'sku' => 'UAT-PHONE-001'],
            [
                'category_id'     => $catPhones->id,
                'brand_id'        => $brandA->id,
                'name'            => 'UAT Phone Product A',
                'slug'            => 'uat-phone-product-a',
                'description'     => 'UAT Test Smartphone Product A',
                'purchase_cost'   => 300000.00,
                'retail_price'    => 350000.00,
                'wholesale_price' => 320000.00,
                'stock_status'    => 'in_stock',
                'is_ecommerce'    => true,
            ]
        );

        // Product B: SKU: UAT-CASE-001, Cost: MMK 10,000, Selling: MMK 15,000, Opening Qty: 20
        $productB = Product::firstOrCreate(
            ['store_id' => $storeA->id, 'sku' => 'UAT-CASE-001'],
            [
                'category_id'     => $catAccessories->id,
                'brand_id'        => $brandA->id,
                'name'            => 'UAT Case Product B',
                'slug'            => 'uat-case-product-b',
                'description'     => 'UAT Test Protective Case Product B',
                'purchase_cost'   => 10000.00,
                'retail_price'    => 15000.00,
                'wholesale_price' => 12000.00,
                'stock_status'    => 'in_stock',
                'is_ecommerce'    => true,
            ]
        );

        // 6. Post Opening Stock movements via InventoryService
        $inventoryService = app(InventoryService::class);

        // Check if movement already posted for Product A
        $hasMoveA = DB::table('inventory_movements')
            ->where('store_id', $storeA->id)
            ->where('product_id', $productA->id)
            ->where('movement_type', InventoryMovementType::OpeningBalance->value)
            ->exists();

        if (!$hasMoveA) {
            $inventoryService->postMovement([
                'store'                  => $storeA,
                'product_id'             => $productA->id,
                'movement_type'          => InventoryMovementType::OpeningBalance->value,
                'quantity_delta'         => 10.0,
                'unit_cost'              => 300000.00,
                'warehouse_id'           => $whA->id,
                'branch_id'              => $branchA->id,
                'client_transaction_id'  => self::RUN_ID . '-PROD-A-OPEN',
                'occurred_at'            => $now,
                'posted_by'              => $createdUsers['store_owner']->id,
                'metadata'               => ['reason' => 'UAT Initial Opening Stock'],
            ]);
        }

        // Check if movement already posted for Product B
        $hasMoveB = DB::table('inventory_movements')
            ->where('store_id', $storeA->id)
            ->where('product_id', $productB->id)
            ->where('movement_type', InventoryMovementType::OpeningBalance->value)
            ->exists();

        if (!$hasMoveB) {
            $inventoryService->postMovement([
                'store'                  => $storeA,
                'product_id'             => $productB->id,
                'movement_type'          => InventoryMovementType::OpeningBalance->value,
                'quantity_delta'         => 20.0,
                'unit_cost'              => 10000.00,
                'warehouse_id'           => $whA->id,
                'branch_id'              => $branchA->id,
                'client_transaction_id'  => self::RUN_ID . '-PROD-B-OPEN',
                'occurred_at'            => $now,
                'posted_by'              => $createdUsers['store_owner']->id,
                'metadata'               => ['reason' => 'UAT Initial Opening Stock'],
            ]);
        }

        // 7. Opening Cashier Cash: MMK 100,000
        $shiftService = app(CashierShiftService::class);
        $existingShift = CashierShift::where('store_id', $storeA->id)
            ->where('register_name', 'Register 1')
            ->where('status', 'open')
            ->first();

        if (!$existingShift) {
            $shiftService->openShift(
                $storeA,
                [
                    'register_name' => 'Register 1',
                    'opening_cash'  => 100000.00,
                    'branch_id'     => $branchA->id,
                    'cashier_id'    => $createdUsers['cashier']->id,
                ],
                $createdUsers['cashier']
            );
        }
    }
}
