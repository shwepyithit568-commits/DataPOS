<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use App\POS\Models\DeviceWarranty;
use App\POS\Models\InventoryBalance;
use App\POS\Models\PurchaseOrder;
use App\POS\Services\PurchaseOrderService;

$store = Store::where('slug', 'shwe-pyi-thit-mobile')->firstOrFail();
$manager = User::where('phone', '09200000011')->firstOrFail();
$pPB = Product::where('store_id', $store->id)->where('barcode', 'UAT-PB-001')->firstOrFail();

echo "Executing PUR-01 for {$store->name}...\n";

// 1. Create Supplier: မန္တလေး အီလက်ထရွန်းနစ် ကုန်တိုက်ကြီး
$supplier = Supplier::firstOrCreate(
    ['store_id' => $store->id, 'name' => 'မန္တလေး အီလက်ထရွန်းနစ် ကုန်တိုက်ကြီး'],
    [
        'phone' => '09770000001',
        'address' => 'မန္တလေးမြို့',
        'notes' => 'UAT Main Supplier',
        'total_credit' => 0,
        'total_repaid' => 0,
    ]
);
echo "Supplier: ID {$supplier->id}, Name: {$supplier->name}\n";

// 2. Create PO: POWERBANK 10 x 35,000 = 350,000; Pay 200,000; Payable 150,000
$poService = app(PurchaseOrderService::class);
$items = [
    [
        'product_id' => $pPB->id,
        'product_variant_id' => null,
        'quantity' => '10.000',
        'unit_cost' => '35000.00',
    ],
];

$payment = [
    'payment_status' => PurchaseOrder::PAYMENT_PARTIAL,
    'paid_amount' => '200000.00',
];

$po = PurchaseOrder::where('store_id', $store->id)->where('po_number', 'PO-20260919-0001')->first();
if (! $po) {
    $po = $poService->create(
        $store,
        $items,
        $supplier->id,
        'UAT-PUR-01-REF',
        'Procurement of 10 Powerbanks with partial SAFE cash payment',
        $manager,
        $payment
    );
    echo "PO Created: ID {$po->id}, Number {$po->po_number}\n";
    $poService->markOrdered($po, $manager);
    $po->refresh();
    echo "PO Ordered: Status {$po->status}\n";
    $receiptResult = $poService->receive($po, $manager);
    $po->refresh();
    echo "PO Received: Status {$po->status}\n";
} else {
    echo "PO already exists: ID {$po->id}, Status {$po->status}\n";
}

// 5. Register 10 Serials: RMX-2026-001 to RMX-2026-010
$warrantyService = app(\App\POS\Services\WarrantyTrackerService::class);
for ($i = 1; $i <= 10; $i++) {
    $serial = sprintf('RMX-2026-%03d', $i);
    $existing = DeviceWarranty::where('store_id', $store->id)->where('serial_number', $serial)->first();
    if (! $existing) {
        $warrantyService->register($store, [
            'product_id' => $pPB->id,
            'product_name' => $pPB->name,
            'serial_number' => $serial,
            'warranty_duration_months' => 6,
            'purchase_date' => now()->toDateString(),
            'warranty_type' => 'shop',
            'status' => 'active',
        ], $manager);
    }
}
echo "10 Serials registered: RMX-2026-001 to RMX-2026-010\n";

// Verify balances and payables
$balPB = InventoryBalance::where('store_id', $store->id)
    ->where('product_id', $pPB->id)
    ->first();
$supplier->refresh();

echo "=== PUR-01 Verification ===\n";
echo "- POWERBANK On-Hand: {$balPB->quantity_on_hand}\n";
echo "- PO Remaining Balance: {$po->remaining_balance}\n";
echo "- Supplier Total Credit: {$supplier->total_credit}\n";
echo "- Supplier Total Repaid: {$supplier->total_repaid}\n";
echo "- Supplier Outstanding: " . ($supplier->total_credit - $supplier->total_repaid) . "\n";
