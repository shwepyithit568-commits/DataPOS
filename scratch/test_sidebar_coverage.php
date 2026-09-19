<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$store = Store::where('slug', 'shwe-pyi-thit-mobile')->first();
$owner = User::where('phone', '09450001122')->first();
$manager = User::where('phone', '09200000011')->first();
$platformOwner = User::where('phone', '09100000001')->first();

$slug = $store->slug;

$menuGroups = [
    'Platform' => [
        ['name' => 'Stores Directory', 'url' => "/admin/stores", 'user' => $platformOwner],
        ['name' => 'Store Creation Form', 'url' => "/admin/stores/create", 'user' => $platformOwner],
        ['name' => 'Theme Governance', 'url' => "/admin/theme-governance", 'user' => $platformOwner],
    ],
    'Store Dashboard & POS' => [
        ['name' => 'Store Dashboard', 'url' => "/store/{$slug}/admin/dashboard", 'user' => $manager],
        ['name' => 'POS Counter', 'url' => "/store/{$slug}/pos", 'user' => $manager],
        ['name' => 'POS Closing', 'url' => "/store/{$slug}/pos/closing", 'user' => $manager],
        ['name' => 'Sales Returns', 'url' => "/store/{$slug}/pos/returns", 'user' => $manager],
    ],
    'Inventory' => [
        ['name' => 'Products Catalog', 'url' => "/store/{$slug}/admin/products", 'user' => $manager],
        ['name' => 'Product Master Data', 'url' => "/store/{$slug}/admin/master-data", 'user' => $manager],
        ['name' => 'Warehouses', 'url' => "/store/{$slug}/admin/warehouses", 'user' => $manager],
        ['name' => 'Stock Ledger', 'url' => "/store/{$slug}/admin/inventory/ledger", 'user' => $manager],
        ['name' => 'Stock Balances', 'url' => "/store/{$slug}/admin/inventory/balances", 'user' => $manager],
        ['name' => 'Stock Transfers', 'url' => "/store/{$slug}/admin/transfers", 'user' => $manager],
        ['name' => 'Opening Stock', 'url' => "/store/{$slug}/admin/opening-stocks", 'user' => $manager],
        ['name' => 'Stock Count', 'url' => "/store/{$slug}/admin/inventory/stock-counts", 'user' => $manager],
        ['name' => 'Stock Adjustments', 'url' => "/store/{$slug}/admin/inventory/adjustments", 'user' => $manager],
        ['name' => 'Stock Reconciliation', 'url' => "/store/{$slug}/admin/inventory/reconciliation", 'user' => $manager],
    ],
    'Purchasing' => [
        ['name' => 'Suppliers Directory', 'url' => "/store/{$slug}/admin/suppliers", 'user' => $manager],
        ['name' => 'Purchase Orders', 'url' => "/store/{$slug}/admin/purchases", 'user' => $manager],
        ['name' => 'Purchase Payables', 'url' => "/store/{$slug}/pos/purchases/payables", 'user' => $manager],
        ['name' => 'Purchase Returns', 'url' => "/store/{$slug}/pos/purchases/returns", 'user' => $manager],
    ],
    'Ecommerce' => [
        ['name' => 'Web Orders', 'url' => "/store/{$slug}/admin/orders", 'user' => $manager],
        ['name' => 'Storefront Banners', 'url' => "/store/{$slug}/admin/banners", 'user' => $manager],
        ['name' => 'Storefront Pages', 'url' => "/store/{$slug}/admin/pages", 'user' => $manager],
        ['name' => 'Storefront Navigation', 'url' => "/store/{$slug}/admin/navigation", 'user' => $manager],
        ['name' => 'Storefront Blog', 'url' => "/store/{$slug}/admin/blog", 'user' => $manager],
        ['name' => 'Glass Finder Admin', 'url' => "/store/{$slug}/admin/glass-finder", 'user' => $manager],
    ],
    'Customers' => [
        ['name' => 'Customer Directory', 'url' => "/store/{$slug}/admin/customers", 'user' => $manager],
        ['name' => 'Customer Receivables', 'url' => "/store/{$slug}/admin/receivables", 'user' => $manager],
        ['name' => 'Wholesale Applications', 'url' => "/store/{$slug}/admin/wholesale-applications", 'user' => $manager],
    ],
    'Service' => [
        ['name' => 'Repair Center', 'url' => "/store/{$slug}/admin/repairs", 'user' => $manager],
        ['name' => 'Service Settings', 'url' => "/store/{$slug}/admin/service-settings", 'user' => $manager],
        ['name' => 'Spare Parts', 'url' => "/store/{$slug}/admin/spare-parts", 'user' => $manager],
    ],
    'Finance & Expenses' => [
        ['name' => 'Expense Directory', 'url' => "/store/{$slug}/admin/expenses", 'user' => $manager],
        ['name' => 'Expense Categories', 'url' => "/store/{$slug}/admin/expense-categories", 'user' => $manager],
        ['name' => 'Profit & Loss Report', 'url' => "/store/{$slug}/admin/profit-loss", 'user' => $manager],
    ],
    'Reports' => [
        ['name' => 'Sales Report', 'url' => "/store/{$slug}/pos/reports/sales", 'user' => $manager],
        ['name' => 'Payments Report', 'url' => "/store/{$slug}/pos/reports/payments", 'user' => $manager],
        ['name' => 'Cash Drawer Report', 'url' => "/store/{$slug}/pos/reports/cash", 'user' => $manager],
        ['name' => 'Stock Report', 'url' => "/store/{$slug}/pos/reports/stock", 'user' => $manager],
        ['name' => 'Tax Report', 'url' => "/store/{$slug}/pos/reports/tax", 'user' => $manager],
        ['name' => 'Daily Closing Report', 'url' => "/store/{$slug}/pos/reports/daily-closing", 'user' => $manager],
    ],
    'Security & Setup' => [
        ['name' => 'User Management', 'url' => "/store/{$slug}/admin/users", 'user' => $owner],
        ['name' => 'Staff Roles', 'url' => "/store/{$slug}/admin/staff-roles", 'user' => $owner],
        ['name' => 'General Settings', 'url' => "/store/{$slug}/admin/settings/general", 'user' => $manager],
        ['name' => 'Currency Settings', 'url' => "/store/{$slug}/admin/settings/currency", 'user' => $manager],
        ['name' => 'POS Settings', 'url' => "/store/{$slug}/admin/settings/pos", 'user' => $manager],
        ['name' => 'Payment Methods', 'url' => "/store/{$slug}/admin/settings/payment-methods", 'user' => $manager],
        ['name' => 'Delivery Methods', 'url' => "/store/{$slug}/admin/settings/delivery", 'user' => $manager],
        ['name' => 'Appearance Drafts', 'url' => "/store/{$slug}/admin/appearance/draft", 'user' => $owner],
        ['name' => 'Printers', 'url' => "/store/{$slug}/admin/printers", 'user' => $manager],
        ['name' => 'Voucher Customizer', 'url' => "/store/{$slug}/admin/vouchers", 'user' => $manager],
    ],
];

echo "=== TESTING SIDEBAR ROUTE COVERAGE ===\n";

$passCount = 0;
$failCount = 0;

foreach ($menuGroups as $group => $routes) {
    echo "\n--- Group: $group ---\n";
    foreach ($routes as $r) {
        $user = $r['user'];
        Auth::login($user);
        
        $request = Request::create($r['url'], 'GET');
        $request->setUserResolver(fn () => $user);
        
        try {
            $response = $kernel->handle($request);
            $status = $response->getStatusCode();
            $kernel->terminate($request, $response);
            
            if ($status === 200 || $status === 302) {
                echo "  [PASS] {$r['name']} ({$r['url']}) -> HTTP $status\n";
                $passCount++;
            } else {
                echo "  [FAIL] {$r['name']} ({$r['url']}) -> HTTP $status\n";
                $failCount++;
            }
        } catch (\Throwable $e) {
            echo "  [ERROR] {$r['name']} ({$r['url']}) -> " . $e->getMessage() . "\n";
            $failCount++;
        }
    }
}

echo "\n======================================\n";
echo "Total Routes Tested: " . ($passCount + $failCount) . "\n";
echo "PASS: $passCount | FAIL: $failCount\n";
