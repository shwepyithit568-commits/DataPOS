<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMasterPreset;
use App\Models\StaffRole;
use App\Models\Store;
use App\Models\StorePaymentMethod;
use App\Models\User;
use App\Models\VariantPreset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$store = Store::where('slug', 'shwe-pyi-thit-mobile')->firstOrFail();
$storeB = Store::where('slug', 'uat-store-b')->firstOrFail();

echo "Setting up UAT Baseline for Store: {$store->name} (ID: {$store->id})\n";

// 1. Payment Methods (SET-01)
StorePaymentMethod::firstOrCreate(
    ['store_id' => $store->id, 'code' => 'cash'],
    [
        'name' => 'Cash',
        'type' => 'custom',
        'icon_type' => 'initials',
        'icon_value' => 'CASH',
        'instructions' => 'ငွေသားဖြင့် ပေးချေခြင်း',
        'is_active' => true,
        'show_account_details' => false,
        'sort_order' => 1,
    ]
);

StorePaymentMethod::firstOrCreate(
    ['store_id' => $store->id, 'code' => 'kpay'],
    [
        'name' => 'KBZ Pay (KPay)',
        'type' => 'custom',
        'icon_type' => 'builtin',
        'icon_value' => 'kpay',
        'account_name' => 'Shwe Pyi Thit Mobile',
        'account_number' => '09450001122',
        'instructions' => 'KPay ဖြင့် ပေးချေခြင်း',
        'is_active' => true,
        'show_account_details' => true,
        'sort_order' => 2,
    ]
);
echo "Payment methods created.\n";

// 2. Staff Accounts (SET-02)
$managerRole = StaffRole::where('store_id', $store->id)->where('slug', 'store_manager')->firstOrFail();
$cashierRole = StaffRole::where('store_id', $store->id)->where('slug', 'cashier')->firstOrFail();

$manager = User::firstOrCreate(
    ['phone' => '09200000011'],
    [
        'name' => 'ကိုသန့် (Manager)',
        'role' => 'customer',
        'password' => Hash::make('Password@2026!'),
        'pos_pin' => Hash::make('1234'),
    ]
);
$manager->stores()->syncWithoutDetaching([
    $store->id => ['role' => 'store_manager', 'staff_role_id' => $managerRole->id, 'status' => 'active']
]);

$cashier = User::firstOrCreate(
    ['phone' => '09200000022'],
    [
        'name' => 'မလှလှ (Cashier)',
        'role' => 'customer',
        'password' => Hash::make('Password@2026!'),
        'pos_pin' => Hash::make('1234'),
    ]
);
$cashier->stores()->syncWithoutDetaching([
    $store->id => ['role' => 'staff', 'staff_role_id' => $cashierRole->id, 'status' => 'active']
]);

$storeBCashierRole = StaffRole::where('store_id', $storeB->id)->where('slug', 'cashier')->first();
$otherStaff = User::firstOrCreate(
    ['phone' => '09960000001'],
    [
        'name' => 'ဒေါ်မြ (Store B Staff)',
        'role' => 'customer',
        'password' => Hash::make('Password@2026!'),
        'pos_pin' => Hash::make('1234'),
    ]
);
if ($storeBCashierRole) {
    $otherStaff->stores()->syncWithoutDetaching([
        $storeB->id => ['role' => 'staff', 'staff_role_id' => $storeBCashierRole->id, 'status' => 'active']
    ]);
}
echo "Staff users created.\n";

// 3. Master Data Tabs (SET-03)
// Parent Category: Phone Accessories
$parentCategory = Category::firstOrCreate(
    ['store_id' => $store->id, 'slug' => 'phone-accessories'],
    [
        'name' => 'Phone Accessories',
        'code' => 'ACC',
        'description' => 'Phone Accessories & Gadgets',
        'icon' => '🎧',
    ]
);

// Children categories: Chargers & Adapters, Tempered Glass, Power Banks
$catCHG = Category::firstOrCreate(
    ['store_id' => $store->id, 'slug' => 'chargers-adapters'],
    [
        'name' => 'Chargers & Adapters',
        'code' => 'CHG',
        'parent_id' => $parentCategory->id,
        'description' => 'Chargers, Adapters, Cables',
    ]
);

$catGLS = Category::firstOrCreate(
    ['store_id' => $store->id, 'slug' => 'tempered-glass'],
    [
        'name' => 'Tempered Glass',
        'code' => 'GLS',
        'parent_id' => $parentCategory->id,
        'description' => 'Screen Protectors & Glass',
    ]
);

$catPB = Category::firstOrCreate(
    ['store_id' => $store->id, 'slug' => 'power-banks'],
    [
        'name' => 'Power Banks',
        'code' => 'PB',
        'parent_id' => $parentCategory->id,
        'description' => 'Portable Chargers & Power Banks',
    ]
);

// Brands: Remax / RMX, Apple / AAPL, Anker / ANK
$brandRMX = Brand::firstOrCreate(
    ['store_id' => $store->id, 'slug' => 'remax'],
    ['name' => 'Remax', 'code' => 'RMX']
);
$brandAAPL = Brand::firstOrCreate(
    ['store_id' => $store->id, 'slug' => 'apple'],
    ['name' => 'Apple', 'code' => 'AAPL']
);
$brandANK = Brand::firstOrCreate(
    ['store_id' => $store->id, 'slug' => 'anker'],
    ['name' => 'Anker', 'code' => 'ANK']
);

// Shelves: စင် A-01, စင် B-02
ProductMasterPreset::firstOrCreate(
    ['store_id' => $store->id, 'type' => 'shelf_location', 'name' => 'စင် A-01'],
    ['code' => 'A-01', 'content' => 'စင် A တန်း ပထမဆင့်', 'is_active' => true]
);
ProductMasterPreset::firstOrCreate(
    ['store_id' => $store->id, 'type' => 'shelf_location', 'name' => 'စင် B-02'],
    ['code' => 'B-02', 'content' => 'စင် B တန်း ဒုတိယဆင့်', 'is_active' => true]
);

// Warranties: 6 months, 1 year
ProductMasterPreset::firstOrCreate(
    ['store_id' => $store->id, 'type' => 'warranty', 'name' => '6 months'],
    ['code' => '6M', 'content' => '၆ လ အာမခံ ( ရက်ပေါင်း ၁၈၀ )', 'is_active' => true]
);
ProductMasterPreset::firstOrCreate(
    ['store_id' => $store->id, 'type' => 'warranty', 'name' => '1 year'],
    ['code' => '1Y', 'content' => '၁ နှစ် အာမခံ ( ၃၆၅ ရက် )', 'is_active' => true]
);

// Return Policies: UAT Cash Refund 7 Days; No Returns; Defect Exchange Only
ProductMasterPreset::firstOrCreate(
    ['store_id' => $store->id, 'type' => 'return_policy', 'name' => 'UAT Cash Refund 7 Days'],
    ['code' => 'REF7', 'content' => '၇ ရက်အတွင်း ငွေပြန်အမ်းပေးသည်', 'is_active' => true]
);
ProductMasterPreset::firstOrCreate(
    ['store_id' => $store->id, 'type' => 'return_policy', 'name' => 'No Returns'],
    ['code' => 'NORET', 'content' => 'ပြန်အမ်းခွင့် မရှိပါ', 'is_active' => true]
);
ProductMasterPreset::firstOrCreate(
    ['store_id' => $store->id, 'type' => 'return_policy', 'name' => 'Defect Exchange Only'],
    ['code' => 'EXCH', 'content' => 'ချွတ်ယွင်းချက်ရှိမှသာ လဲလှယ်ပေးသည်', 'is_active' => true]
);

// Variant presets: Color & Storage
VariantPreset::firstOrCreate(
    ['store_id' => $store->id, 'name' => 'Color'],
    ['options' => ['Black', 'White', 'Titanium'], 'sort_order' => 1]
);
VariantPreset::firstOrCreate(
    ['store_id' => $store->id, 'name' => 'Storage'],
    ['options' => ['128GB', '256GB', '512GB'], 'sort_order' => 2]
);
echo "Master Data Presets created.\n";

// 4. Products Setup (SET-04)
$pCharger = Product::firstOrCreate(
    ['store_id' => $store->id, 'barcode' => 'UAT-CHG-001'],
    [
        'category_id' => $catCHG->id,
        'brand_id' => $brandRMX->id,
        'sku' => 'UAT-CHG-001',
        'name' => 'Remax RP-U25, Universal Type-C',
        'slug' => 'remax-rp-u25-universal-type-c',
        'product_type' => 'standard',
        'purchase_cost' => 15000.00,
        'retail_price' => 25000.00,
        'wholesale_price' => 18000.00,
        'shelf_location' => 'စင် A-01',
        'is_taxable' => false,
        'tax_rate' => 0.00,
        'stock_status' => 'in_stock',
        'is_ecommerce' => true,
    ]
);

$pGlass = Product::firstOrCreate(
    ['store_id' => $store->id, 'barcode' => 'UAT-GLS-001'],
    [
        'category_id' => $catGLS->id,
        'brand_id' => $brandAAPL->id,
        'sku' => 'UAT-GLS-001',
        'name' => 'Apple-compatible IP15P Tempered Glass',
        'slug' => 'apple-compatible-ip15p-tempered-glass',
        'product_type' => 'standard',
        'purchase_cost' => 1500.00,
        'retail_price' => 5000.00,
        'wholesale_price' => 2500.00,
        'shelf_location' => 'စင် A-01',
        'return_policy' => 'UAT Cash Refund 7 Days',
        'is_taxable' => false,
        'tax_rate' => 0.00,
        'stock_status' => 'in_stock',
        'is_ecommerce' => true,
    ]
);

$pPowerBank = Product::firstOrCreate(
    ['store_id' => $store->id, 'barcode' => 'UAT-PB-001'],
    [
        'category_id' => $catPB->id,
        'brand_id' => $brandRMX->id,
        'sku' => 'UAT-PB-001',
        'name' => 'Remax RPP-292',
        'slug' => 'remax-rpp-292',
        'product_type' => 'serialized',
        'purchase_cost' => 35000.00,
        'retail_price' => 55000.00,
        'wholesale_price' => 42000.00,
        'shelf_location' => 'စင် B-02',
        'warranty' => '6 months',
        'is_taxable' => false,
        'tax_rate' => 0.00,
        'stock_status' => 'in_stock',
        'is_ecommerce' => true,
    ]
);

echo "Products created:\n";
echo "1. CHARGER ID: {$pCharger->id}, Barcode: {$pCharger->barcode}, Price: {$pCharger->retail_price}\n";
echo "2. GLASS ID: {$pGlass->id}, Barcode: {$pGlass->barcode}, Price: {$pGlass->retail_price}\n";
echo "3. POWERBANK ID: {$pPowerBank->id}, Barcode: {$pPowerBank->barcode}, Price: {$pPowerBank->retail_price}\n";
echo "ALL SET-01 to SET-04 Baseline Setup SUCCESS.\n";
