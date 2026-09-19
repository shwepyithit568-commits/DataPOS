<?php
$baseUrl = 'http://127.0.0.1:8501';
$cookieFile = __DIR__ . '/cookies.txt';
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

function httpGet($url, $cookieFile) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    return ['status' => $status, 'redirect' => $redirectUrl, 'body' => $body];
}

// 1. Quick login as Manager
echo "Logging in as Manager (09200000011)...\n";
$login = httpGet("{$baseUrl}/quick-login?phone=09200000011", $cookieFile);
echo "Login HTTP Status: {$login['status']} (Redirect: {$login['redirect']})\n\n";

$slug = 'shwe-pyi-thit-mobile';

$routes = [
    // Store Dashboard & POS
    'Store Dashboard' => "/store/{$slug}/admin/dashboard",
    'POS Counter' => "/store/{$slug}/pos",
    'POS Closing' => "/store/{$slug}/pos/closing",
    'Sales Returns' => "/store/{$slug}/pos/returns",
    
    // Inventory
    'Products Catalog' => "/store/{$slug}/admin/products",
    'Product Master Data' => "/store/{$slug}/admin/master-data",
    'Warehouses' => "/store/{$slug}/admin/warehouses",
    'Stock Ledger' => "/store/{$slug}/admin/inventory/ledger",
    'Stock Balances' => "/store/{$slug}/admin/inventory/balances",
    'Stock Transfers' => "/store/{$slug}/admin/transfers",
    'Opening Stock' => "/store/{$slug}/admin/opening-stocks",
    'Stock Adjustments' => "/store/{$slug}/admin/inventory/adjustments",
    'Stock Reconciliation' => "/store/{$slug}/admin/inventory/reconciliation",
    
    // Purchasing
    'Suppliers Directory' => "/store/{$slug}/admin/suppliers",
    'Purchase Orders' => "/store/{$slug}/admin/purchases",
    'Purchase Payables' => "/store/{$slug}/pos/purchases/payables",
    'Purchase Returns' => "/store/{$slug}/pos/purchases/returns",
    
    // Ecommerce
    'Web Orders' => "/store/{$slug}/admin/orders",
    'Storefront Banners' => "/store/{$slug}/admin/banners",
    'Storefront Pages' => "/store/{$slug}/admin/pages",
    'Storefront Navigation' => "/store/{$slug}/admin/navigation",
    'Storefront Blog' => "/store/{$slug}/admin/blog",
    'Glass Finder Admin' => "/store/{$slug}/admin/glass-finder",
    
    // Customers
    'Customer Directory' => "/store/{$slug}/admin/customers",
    'Customer Receivables' => "/store/{$slug}/admin/receivables",
    'Wholesale Applications' => "/store/{$slug}/admin/wholesale-applications",
    
    // Service
    'Repair Center' => "/store/{$slug}/admin/repairs",
    'Service Settings' => "/store/{$slug}/admin/service-settings",
    'Spare Parts' => "/store/{$slug}/admin/spare-parts",
    
    // Finance & Expenses
    'Expense Directory' => "/store/{$slug}/admin/expenses",
    'Expense Categories' => "/store/{$slug}/admin/expense-categories",
    'Profit & Loss Report' => "/store/{$slug}/admin/profit-loss",
    
    // Reports
    'Sales Report' => "/store/{$slug}/pos/reports/sales",
    'Payments Report' => "/store/{$slug}/pos/reports/payments",
    'Cash Drawer Report' => "/store/{$slug}/pos/reports/cash",
    'Stock Report' => "/store/{$slug}/pos/reports/stock",
    'Tax Report' => "/store/{$slug}/pos/reports/tax",
    'Daily Closing Report' => "/store/{$slug}/pos/reports/daily-closing",
    
    // Settings & Setup
    'General Settings' => "/store/{$slug}/admin/settings/general",
    'Currency Settings' => "/store/{$slug}/admin/settings/currency",
    'POS Settings' => "/store/{$slug}/admin/settings/pos",
    'Payment Methods' => "/store/{$slug}/admin/settings/payment-methods",
    'Delivery Methods' => "/store/{$slug}/admin/settings/delivery",
    'Printers' => "/store/{$slug}/admin/printers",
    'Voucher Customizer' => "/store/{$slug}/admin/vouchers",
];

$pass = 0;
$fail = 0;

foreach ($routes as $name => $path) {
    $res = httpGet("{$baseUrl}{$path}", $cookieFile);
    if ($res['status'] === 200) {
        echo "  [PASS] {$name} ({$path}) -> HTTP 200 OK\n";
        $pass++;
    } elseif ($res['status'] === 302) {
        echo "  [REDIRECT] {$name} ({$path}) -> HTTP 302 (to {$res['redirect']})\n";
        $pass++;
    } else {
        echo "  [FAIL] {$name} ({$path}) -> HTTP {$res['status']}\n";
        $fail++;
    }
}

echo "\n======================================\n";
echo "Total Routes Checked: " . count($routes) . "\n";
echo "PASS / ACCESSIBLE: $pass | FAIL: $fail\n";

if (file_exists($cookieFile)) {
    unlink($cookieFile);
}
