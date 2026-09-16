<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\StaffRole;
use App\Models\Store;
use App\Models\StorefrontSetting;
use App\Models\Supplier;
use App\Models\User;
use App\POS\Services\StoreLocationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UatPosStoreProvisionSeeder extends Seeder
{
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

        StorefrontSetting::firstOrCreate(
            ['store_id' => $storeA->id],
            [
                'store_name'          => 'UAT-POS-STORE',
                'tagline'             => 'UAT Acceptance Testing Store A',
                'phone'               => '09850000001',
                'default_language'    => 'my',
                'theme_preset'        => 'midnight_tech',
                'theme_primary_color' => '#3b82f6',
                'theme_accent_color'  => '#10b981',
                'theme_header_bg'     => '#1e293b',
                'theme_body_bg'       => '#0f172a',
                'theme_glow_style'    => 'subtle',
                'theme_dark_mode'     => true,
                'font_preset'         => 'outfit',
                'grid_density'        => 'compact',
                'pos_settings'        => [
                    'enable_tax'       => false,
                    'default_tax_rate' => 5.0,
                    'tax_type'         => 'exclusive',
                ],
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
                'tagline'             => 'UAT Cross-Store Isolation Store B',
                'phone'               => '09850000002',
                'default_language'    => 'my',
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

        // 3. Bootstrap Staff Roles
        StaffRole::bootstrapDefaultRoles($storeA);
        StaffRole::bootstrapDefaultRoles($storeB);

        $staffRolesA = StaffRole::where('store_id', $storeA->id)->pluck('id', 'slug')->all();

        // 4. Create Standard UAT Users
        $usersData = [
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
            'technician' => [
                'phone' => '09800000006',
                'name'  => 'UAT Technician',
                'role'  => 'customer',
                'store_role' => 'staff',
                'staff_role_id' => $staffRolesA['technician'] ?? null,
            ],
        ];

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

        // 5. Initial Suppliers for Store A
        Supplier::firstOrCreate(
            ['store_id' => $storeA->id, 'name' => 'UAT Supplier Alpha'],
            [
                'contact_person' => 'Supplier Alpha Rep',
                'phone'          => '09811111111',
                'email'          => 'supplier_alpha@datapos.local',
                'address'        => 'Yangon Industrial Zone 1',
            ]
        );

        Supplier::firstOrCreate(
            ['store_id' => $storeA->id, 'name' => 'UAT Supplier Beta'],
            [
                'contact_person' => 'Supplier Beta Rep',
                'phone'          => '09811111112',
                'email'          => 'supplier_beta@datapos.local',
                'address'        => 'Mandalay Wholesale Center',
            ]
        );

        // 6. Customers for Store A (using Customer model if available)
        if (class_exists(Customer::class)) {
            Customer::firstOrCreate(
                ['store_id' => $storeA->id, 'phone' => '09822222221'],
                [
                    'name'          => 'UAT Walk-in Customer',
                    'customer_type' => 'retail',
                    'address'       => 'Yangon',
                ]
            );
            Customer::firstOrCreate(
                ['store_id' => $storeA->id, 'phone' => '09822222222'],
                [
                    'name'          => 'UAT Wholesale Buyer',
                    'customer_type' => 'wholesale',
                    'address'       => 'Hledan, Yangon',
                ]
            );
            Customer::firstOrCreate(
                ['store_id' => $storeA->id, 'phone' => '09822222223'],
                [
                    'name'          => 'UAT Credit Customer',
                    'customer_type' => 'retail',
                    'credit_limit'  => 1000000.00,
                    'address'       => 'Sanchaung, Yangon',
                ]
            );
            Customer::firstOrCreate(
                ['store_id' => $storeA->id, 'phone' => '09822222224'],
                [
                    'name'          => 'UAT Service Customer',
                    'customer_type' => 'retail',
                    'address'       => 'Tamwe, Yangon',
                ]
            );
        }

        // 7. Base Categories and Brands for Store A
        Category::firstOrCreate(
            ['store_id' => $storeA->id, 'slug' => 'smartphones'],
            ['name' => 'Smartphones', 'description' => 'Mobile Phones']
        );
        Category::firstOrCreate(
            ['store_id' => $storeA->id, 'slug' => 'accessories'],
            ['name' => 'Accessories', 'description' => 'Phone Accessories']
        );
        Category::firstOrCreate(
            ['store_id' => $storeA->id, 'slug' => 'repair-parts'],
            ['name' => 'Repair Parts', 'description' => 'Service and Replacement Parts']
        );

        Brand::firstOrCreate(
            ['store_id' => $storeA->id, 'slug' => 'generic-brand'],
            ['name' => 'Generic Brand']
        );
        Brand::firstOrCreate(
            ['store_id' => $storeA->id, 'slug' => 'uat-brand'],
            ['name' => 'UAT Brand']
        );
    }
}
