<?php

namespace App\POS\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\CashierShift;
use App\POS\Models\PosPayment;
use App\POS\Models\PosSale;
use App\POS\Models\PosSaleItem;
use Illuminate\Support\Facades\DB;

/**
 * POS cart + sale posting (target-design §2.8 / SoT §8).
 *
 * - The live cart is session-backed (draft state); Hold persists it as a
 *   `held` PosSale row so it survives browser close and can be resumed.
 * - post() is atomic: sale record + receipt number + item snapshots (COGS
 *   carried from the ledger) + inventory movements + payments + the cashier
 *   shift's cash_sales are committed in one transaction. A failed movement
 *   (e.g. insufficient stock) aborts the whole sale — nothing is half-posted.
 * - Receipt number is assigned at posting time only, and is unique per store.
 * - Money uses bcmath throughout (MMK, §2.6) — never float arithmetic.
 */
class PosSaleService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly CostingService $costing,
        private readonly CashierShiftService $shifts,
        private readonly CustomerDebtService $debts,
    ) {
    }

    /* ------------------------------------------------------------------ */
    /*  Product search                                                     */
    /* ------------------------------------------------------------------ */

    /**
     * Products (and their variants) matching SKU/barcode-style code or name,
     * scoped to the store, with live ledger balances.
     *
     * @return array<int, array{type:string, id:int, name:string, sku:?string, price:string, balance:string, variant_of:?string}>
     */
    public function searchProducts(Store $store, string $query, ?User $customer = null, int $limit = 12): array
    {
        $q = trim($query);
        if ($q === '') {
            return [];
        }

        $results = [];

        Product::query()
            ->where('store_id', $store->id)
            ->where(fn ($w) => $w->where('sku', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%"))
            ->limit($limit)
            ->get()
            ->each(function (Product $p) use (&$results, $customer) {
                $results[] = [
                    'type' => 'product',
                    'id' => $p->id,
                    'product_id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'price' => $this->priceFor($customer, $p),
                    'balance' => $this->inventory->totalOnHand($p->store_id, $p->id),
                    'variant_of' => null,
                ];
            });

        ProductVariant::query()
            ->whereHas('product', fn ($q2) => $q2->where('store_id', $store->id))
            ->where(fn ($w) => $w->where('sku', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%"))
            ->with('product')
            ->limit($limit)
            ->get()
            ->each(function (ProductVariant $v) use (&$results, $customer) {
                $results[] = [
                    'type' => 'variant',
                    'id' => $v->id,
                    'product_id' => $v->product_id,
                    'name' => $v->product->name . ' — ' . $v->name,
                    'sku' => $v->sku,
                    'price' => $this->priceFor($customer, $v->product, $v),
                    'balance' => $this->inventory->totalOnHand($v->product->store_id, $v->product_id, $v->id),
                    'variant_of' => $v->product->name,
                ];
            });

        return array_slice($results, 0, $limit);
    }

    /* ------------------------------------------------------------------ */
    /*  Product grid (POS home — reference UI from alinthit_pos)           */
    /* ------------------------------------------------------------------ */

    /**
     * Products for the POS product grid: live ledger balances, category/brand
     * names and selectable variants, filterable by category / brand / query.
     *
     * @return array<int, array{id:int, name:string, sku:?string, price:string, balance:string, category_id:?int, category:?string, brand:?string, variants:array<int, array{id:int, name:string, sku:?string, price:string, balance:string}>}>
     */
    public function gridProducts(Store $store, ?int $categoryId = null, ?int $brandId = null, string $query = '', ?User $customer = null, int $limit = 120): array
    {
        $q = trim($query);

        $products = Product::query()
            ->where('store_id', $store->id)
            ->when($categoryId, fn ($w) => $w->where('category_id', $categoryId))
            ->when($brandId, fn ($w) => $w->where('brand_id', $brandId))
            ->when($q !== '', fn ($w) => $w->where(fn ($w2) => $w2->where('sku', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%")))
            ->with(['category:id,name', 'brand:id,name', 'variants:id,product_id,name,sku,retail_price,wholesale_price,is_default'])
            ->orderBy('name')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        return $products->map(function (Product $p) use ($store, $customer) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => $this->priceFor($customer, $p),
                'old_price' => $p->old_price !== null ? (string) $p->old_price : null,
                'image' => $p->image_path ? asset('storage/' . $p->image_path) : null,
                'balance' => $this->inventory->totalOnHand($store->id, $p->id),
                'category_id' => $p->category_id,
                'category' => $p->category?->name,
                'brand' => $p->brand?->name,
                'variants' => $p->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'sku' => $v->sku,
                    'price' => $this->priceFor($customer, $p, $v),
                    'balance' => $this->inventory->totalOnHand($store->id, $p->id, $v->id),
                ])->values()->all(),
            ];
        })->values()->all();
    }

    /* ------------------------------------------------------------------ */
    /*  Session cart                                                       */
    /* ------------------------------------------------------------------ */

    private function cartKey(Store $store): string
    {
        return 'pos.cart.' . $store->id;
    }

    private function cartCustomerKey(Store $store): string
    {
        return 'pos.cart_customer.' . $store->id;
    }

    /**
     * True when the user is an active retail/wholesale customer of this store
     * (the same membership rule post() enforces — never cross-store).
     */
    private function isStoreCustomer(Store $store, User $user): bool
    {
        return $user->stores()
            ->where('stores.id', $store->id)
            ->whereIn('store_user.role', ['retail_customer', 'wholesale_customer'])
            ->where('store_user.status', 'active')
            ->exists();
    }

    /**
     * Tiered unit price for a product/variant. Wholesale customers pay the
     * wholesale price when one is set (> 0); everyone else (walk-in, retail
     * customers) pays retail. Mirrors the storefront wholesale rule.
     */
    public function priceFor(?User $customer, Product $product, ?ProductVariant $variant = null): string
    {
        if ($customer !== null && $customer->getStoreRole($product->store_id) === 'wholesale_customer') {
            $wholesale = $variant?->wholesale_price ?? $product->wholesale_price;
            if ($wholesale !== null && bccomp((string) $wholesale, '0', 2) > 0) {
                return (string) $wholesale;
            }
        }

        return (string) ($variant?->retail_price ?? $product->retail_price);
    }

    /**
     * Attach a customer to the cart (server-side) so the whole POS prices at
     * their tier — walk-in (null) resets to retail pricing. An explicit
     * walk-in is stored as a sentinel (0) so it overrides the logged-in
     * customer fallback: a cashier can deliberately drop the tier mid-sale.
     */
    public function attachCartCustomer(Store $store, ?User $customer): void
    {
        if ($customer !== null && ! $this->isStoreCustomer($store, $customer)) {
            throw new InventoryException('The selected customer does not belong to this store.');
        }

        session([$this->cartCustomerKey($store) => $customer?->id ?? 0]);
    }

    /**
     * The customer currently attached to the cart, or null (walk-in → retail).
     *
     * Resolution order:
     *   1. The cashier's explicit choice (attach / quick-add / detach) always
     *      wins — including an explicit walk-in.
     *   2. Otherwise the authenticated user, when they are an active
     *      retail/wholesale customer of this store — so a customer who logged
     *      into the storefront keeps their tier (wholesale pricing) at the
     *      register without the cashier re-selecting them.
     *
     * Stale ids (customer removed/deactivated) resolve to null.
     */
    public function cartCustomer(Store $store): ?User
    {
        $key = $this->cartCustomerKey($store);
        $id = session()->get($key, 'unset');

        if ($id === 'unset') {
            $user = auth()->user();

            return ($user !== null && $this->isStoreCustomer($store, $user)) ? $user : null;
        }

        if (! $id) {
            return null; // explicit walk-in
        }

        $user = User::find($id);

        return ($user !== null && $this->isStoreCustomer($store, $user)) ? $user : null;
    }

    /**
     * @return array<int, array{product_id:int, product_variant_id:?int, quantity:string}>
     */
    public function cartLines(Store $store): array
    {
        return session()->get($this->cartKey($store), []);
    }

    public function addToCart(Store $store, int $productId, ?int $variantId, string $quantity): void
    {
        $product = Product::findOrFail($productId);
        if ((int) $product->store_id !== (int) $store->id) {
            throw new InventoryException('Product does not belong to this store.');
        }
        if (bccomp($quantity, '0', 3) <= 0) {
            throw new InventoryException('Quantity must be positive.');
        }

        if ($variantId !== null) {
            $variant = ProductVariant::findOrFail($variantId);
            if ((int) $variant->product_id !== (int) $product->id) {
                throw new InventoryException('Variant does not belong to the selected product.');
            }
        }

        $lines = $this->cartLines($store);

        foreach ($lines as $i => $line) {
            if ((int) $line['product_id'] === $productId && (int) ($line['product_variant_id'] ?? 0) === (int) ($variantId ?? 0)) {
                $lines[$i]['quantity'] = bcadd($line['quantity'], $quantity, 3);
                session([$this->cartKey($store) => $lines]);

                return;
            }
        }

        $lines[] = [
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'quantity' => $quantity,
        ];

        session([$this->cartKey($store) => $lines]);
    }

    public function updateCartLine(Store $store, int $index, string $quantity): void
    {
        $lines = $this->cartLines($store);
        if (! isset($lines[$index])) {
            throw new InventoryException('Cart line not found.');
        }
        if (bccomp($quantity, '0', 3) <= 0) {
            $this->removeCartLine($store, $index);

            return;
        }
        $lines[$index]['quantity'] = $quantity;
        session([$this->cartKey($store) => $lines]);
    }

    public function removeCartLine(Store $store, int $index): void
    {
        $lines = $this->cartLines($store);
        unset($lines[$index]);
        session([$this->cartKey($store) => array_values($lines)]);
    }

    public function clearCart(Store $store): void
    {
        session()->forget($this->cartKey($store));
        session()->forget($this->cartCustomerKey($store));
    }

    /**
     * Resolved cart lines with product/variant data + live balances + prices.
     *
     * @return array<int, array{index:int, product_id:int, product_variant_id:?int, quantity:string, name:string, sku:?string, unit_price:string, line_total:string, balance:string, product:Product}>
     */
    public function cartResolved(Store $store): array
    {
        $customer = $this->cartCustomer($store);
        $out = [];
        foreach ($this->cartLines($store) as $i => $line) {
            $product = Product::find($line['product_id']);
            if (! $product || (int) $product->store_id !== (int) $store->id) {
                continue;
            }

            $variant = $line['product_variant_id'] ? ProductVariant::find($line['product_variant_id']) : null;
            $name = $variant ? $product->name . ' — ' . $variant->name : $product->name;
            $price = $this->priceFor($customer, $product, $variant);
            $quantity = $line['quantity'];

            $out[] = [
                'index' => $i,
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'quantity' => $quantity,
                'name' => $name,
                'sku' => $variant?->sku ?? $product->sku,
                'unit_price' => (string) $price,
                'line_total' => bcmul((string) $price, $quantity, 2),
                'balance' => $this->inventory->totalOnHand($store->id, $product->id, $variant?->id),
                'product' => $product,
            ];
        }

        return $out;
    }

    /**
     * @return array{subtotal:string, discount:string, total:string}
     */
    public function cartTotals(Store $store): array
    {
        $subtotal = '0';
        foreach ($this->cartResolved($store) as $line) {
            $subtotal = bcadd($subtotal, $line['line_total'], 2);
        }

        return [
            'subtotal' => $subtotal,
            'discount' => '0',
            'total' => $subtotal,
        ];
    }

    /**
     * Live cart snapshot for the POS UI — the JSON payload returned by the
     * cart-state endpoint and echoed back after every AJAX cart mutation so
     * the product grid + cart panel stay in sync without a page reload.
     *
     * @return array{shift_open:bool, lines:array<int, array{index:int, product_id:int, product_variant_id:?int, name:string, sku:?string, quantity:string, unit_price:string, line_total:string, balance:string}>, totals:array{subtotal:string, discount:string, total:string}, held_count:int, held:array<int, array{id:int, total:string, items_count:int, held_at:string}>, expired_count:int, expiry:array{threshold_hours:int, oldest_held_at:?string, soon_count:int}}
     */
    public function cartState(Store $store, ?User $actor): array
    {
        $expiredCount = $this->expireStaleHolds($store);
        $threshold = $store->setting?->posHoldExpiryHours() ?? 24;

        $lines = array_map(function (array $line) {
            return [
                'index' => $line['index'],
                'product_id' => $line['product_id'],
                'product_variant_id' => $line['product_variant_id'],
                'name' => $line['name'],
                'sku' => $line['sku'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'line_total' => $line['line_total'],
                'balance' => $line['balance'],
            ];
        }, $this->cartResolved($store));

        $held = PosSale::query()
            ->withCount('items')
            ->where('store_id', $store->id)
            ->where('status', 'held')
            ->orderByDesc('id')
            ->get();

        $oldest = $held->sortBy('created_at')->first();
        $soonCount = 0;
        if ($threshold > 0 && $held->isNotEmpty()) {
            // 'Soon to expire' = remaining time under an hour (threshold of 1h
            // makes every hold qualify, which is accurate — all expire within
            // the hour). A disabled window (0) reports no soon holds.
            $soonCutoff = now()->subHours(max(0, $threshold - 1));
            $soonCount = $held->filter(fn (PosSale $sale) => $sale->created_at?->lt($soonCutoff))->count();
        }

        $customer = $this->cartCustomer($store);

        return [
            'shift_open' => (bool) $this->shifts->openShiftFor($store, $actor),
            'lines' => array_values($lines),
            'totals' => $this->cartTotals($store),
            'held_count' => $held->count(),
            'held' => $held->map(fn (PosSale $sale) => [
                'id' => (int) $sale->id,
                'total' => (string) $sale->total,
                'items_count' => (int) $sale->items_count,
                'held_at' => $sale->created_at?->format('H:i') ?? '—',
            ])->values()->all(),
            'expired_count' => $expiredCount,
            'expiry' => [
                'threshold_hours' => $threshold,
                'oldest_held_at' => $oldest?->created_at?->toIso8601String(),
                'soon_count' => $soonCount,
            ],
            'customer' => $customer ? [
                'id' => (int) $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'role' => $customer->getStoreRole($store->id),
                'balance' => (string) $this->debts->balanceFor($store->id, $customer->id),
            ] : null,
        ];
    }

    /**
     * Auto-expire holds older than the store's configured window (lazy, runs
     * on every cart-state read so a stale hold leaves the list without a cron).
     * They are marked 'voided' with a note so the audit trail is kept and they
     * cannot be recalled anymore. A store window of 0 disables auto-expiry.
     */
    public function expireStaleHolds(Store $store, ?int $olderThanHours = null): int
    {
        $olderThanHours ??= $store->setting?->posHoldExpiryHours() ?? 24;
        if ($olderThanHours <= 0) {
            return 0;
        }

        $cutoff = now()->subHours($olderThanHours);

        $stale = PosSale::query()
            ->where('store_id', $store->id)
            ->where('status', 'held')
            ->where('created_at', '<', $cutoff)
            ->get();

        foreach ($stale as $sale) {
            $sale->update([
                'status' => 'voided',
                'voided_at' => now(),
                'notes' => trim(($sale->notes ?? '') . ' Expired — held over ' . $olderThanHours . 'h, auto-voided at ' . now()->format('Y-m-d H:i')),
            ]);
        }

        return $stale->count();
    }

    /* ------------------------------------------------------------------ */
    /*  Hold / resume / void                                               */
    /* ------------------------------------------------------------------ */

    public function holdCart(Store $store, User $actor, ?CashierShift $shift = null): PosSale
    {
        $lines = $this->cartResolved($store);
        if ($lines === []) {
            throw new InventoryException('Cart is empty — nothing to hold.');
        }

        return DB::transaction(function () use ($store, $lines, $actor, $shift) {
            $subtotal = '0';
            foreach ($lines as $line) {
                $subtotal = bcadd($subtotal, $line['line_total'], 2);
            }

            $sale = PosSale::create([
                'store_id' => $store->id,
                'branch_id' => $shift?->branch_id,
                'cashier_shift_id' => $shift?->id,
                'cashier_id' => $actor->id,
                'status' => 'held',
                'subtotal' => $subtotal,
                'discount' => '0',
                'tax' => '0',
                'total' => $subtotal,
                'created_by' => $actor->id,
            ]);

            foreach ($lines as $line) {
                $sale->items()->create([
                    'product_id' => $line['product_id'],
                    'product_variant_id' => $line['product_variant_id'],
                    'product_name' => $line['name'],
                    'sku' => $line['sku'],
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ]);
            }

            $this->clearCart($store);

            return $sale;
        });
    }

    public function resumeHeld(Store $store, PosSale $sale, ?User $actor = null): void
    {
        if (! $sale->isHeld() || (int) $sale->store_id !== (int) $store->id) {
            throw new InventoryException('This held sale cannot be resumed.');
        }

        $lines = [];
        foreach ($sale->items as $item) {
            $lines[] = [
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'quantity' => (string) $item->quantity,
            ];
        }

        // The held record leaves the held list the moment it is recalled — it
        // is marked 'resumed' (not deleted, so the audit trail is kept) and
        // can only be posted or voided from here on.
        $sale->update([
            'status' => 'resumed',
            'notes' => trim(($sale->notes ?? '') . ' Resumed by ' . ($actor?->name ?? 'unknown') . ' at ' . now()->format('Y-m-d H:i')),
        ]);

        session([$this->cartKey($store) => $lines]);
    }

    public function voidHeld(Store $store, PosSale $sale, User $actor): void
    {
        if ((int) $sale->store_id !== (int) $store->id || $sale->isPosted() || $sale->status === 'voided') {
            throw new InventoryException('Only draft/held sales can be voided before posting.');
        }

        $sale->update([
            'status' => 'voided',
            'voided_at' => now(),
            'notes' => trim(($sale->notes ?? '') . ' Voided by ' . $actor->name . ' at ' . now()->format('Y-m-d H:i')),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Posting (atomic)                                                   */
    /* ------------------------------------------------------------------ */

    /**
     * Post a sale atomically.
     *
     * @param  array<int, array{product_id:int, product_variant_id:?int, quantity:string}>  $lines
     * @param  array<int, array{method:string, amount:string}>  $payments
     * @param  int|null  $customerId  Attached customer (required when a `credit`
     *                                payment is used — the unpaid portion becomes
     *                                a debt receivable, SoT §17).
     */
    public function post(
        Store $store,
        array $lines,
        array $payments,
        User $actor,
        ?CashierShift $shift = null,
        ?PosSale $heldSale = null,
        ?int $customerId = null,
    ): PosSale {
        if (! $shift?->isOpen() || (int) $shift->store_id !== (int) $store->id) {
            throw new InventoryException('An open cashier shift is required to post a sale.');
        }

        if ($lines === []) {
            throw new InventoryException('Cart is empty — nothing to post.');
        }

        // Drop zero-amount payment rows (UI sends all six methods; 0 = unused).
        $payments = array_values(array_filter($payments, fn ($p) => bccomp((string) ($p['amount'] ?? '0'), '0', 2) > 0));

        if ($payments === []) {
            throw new InventoryException('At least one payment is required.');
        }

        $usesCredit = collect($payments)->contains(fn ($p) => ($p['method'] ?? '') === 'credit');

        // The cart's attached customer is the source of truth for pricing and
        // receivables; a form-posted customer_id must agree with it. This also
        // keeps direct service calls (tests) working with either source.
        $sessionCustomer = $this->cartCustomer($store);
        $customer = $customerId ? User::find($customerId) : $sessionCustomer;

        if ($customerId && $sessionCustomer && (int) $sessionCustomer->id !== (int) $customerId) {
            throw new InventoryException('The customer changed during checkout — please review the cart.');
        }

        // Persist the resolved customer on the sale record (the cart-attached
        // customer when none was posted explicitly).
        $customerId = $customer?->id;

        if ($usesCredit && ! $customer) {
            throw new InventoryException('A customer must be attached to the sale to use credit (debt) payment.');
        }

        // The attached customer must belong to this store (retail/wholesale
        // customer membership) — never allow cross-store receivable posting.
        if ($customer && ! $this->isStoreCustomer($store, $customer)) {
            throw new InventoryException('The selected customer does not belong to this store.');
        }

        $warehouseId = $this->inventory->defaultWarehouseId($store->id);

        // Resolve + validate lines against the store.
        $resolved = [];
        $subtotal = '0';
        foreach ($lines as $line) {
            $product = Product::find($line['product_id']);
            if (! $product || (int) $product->store_id !== (int) $store->id) {
                throw new InventoryException('A cart line references a product outside this store.');
            }

            $variant = $line['product_variant_id'] ? ProductVariant::find($line['product_variant_id']) : null;
            if ($variant && (int) $variant->product_id !== (int) $product->id) {
                throw new InventoryException('A cart line references a variant outside its product.');
            }

            $quantity = (string) $line['quantity'];
            if (bccomp($quantity, '0', 3) <= 0) {
                throw new InventoryException("Quantity for '{$product->name}' must be positive.");
            }

            $price = $this->priceFor($customer, $product, $variant);
            $balance = $this->inventory->balanceFor($store->id, $product->id, $variant?->id, $warehouseId);

            if (! $balance || bccomp((string) $balance->quantity_on_hand, $quantity, 3) < 0) {
                throw new InventoryException(
                    "Insufficient stock for '{$product->name}' (on hand: " . ($balance ? (string) $balance->quantity_on_hand : '0') . ').'
                );
            }

            $lineTotal = bcmul($price, $quantity, 2);
            $subtotal = bcadd($subtotal, $lineTotal, 2);

            $resolved[] = [
                'product' => $product,
                'variant' => $variant,
                'quantity' => $quantity,
                'unit_price' => $price,
                'line_total' => $lineTotal,
                'name' => $variant ? $product->name . ' — ' . $variant->name : $product->name,
                'sku' => $variant?->sku ?? $product->sku,
            ];
        }

        // Payment math: total of payments must cover the total; cash may
        // overpay (change returned), other methods must not overpay.
        $discount = '0';
        $tax = '0';
        $total = bcsub(bcadd($subtotal, $tax, 2), $discount, 2);

        $remaining = $total;
        $cashKept = '0';
        $creditTotal = '0';
        $paymentRows = [];
        foreach ($payments as $payment) {
            $method = (string) $payment['method'];
            if (! in_array($method, ['cash', 'kpay', 'wavepay', 'cb_pay', 'mmqr', 'credit'], true)) {
                throw new InventoryException("Unknown payment method '{$method}'.");
            }
            $amount = (string) $payment['amount'];
            if (bccomp($amount, '0', 2) <= 0) {
                throw new InventoryException('Payment amounts must be positive.');
            }

            if ($method === 'cash') {
                $kept = bccomp($amount, $remaining, 2) > 0 ? $remaining : $amount;
                $change = bcsub($amount, $kept, 2);
                $cashKept = bcadd($cashKept, $kept, 2);
                $paymentRows[] = ['method' => 'cash', 'amount' => $amount, 'change_given' => $change];
                $remaining = bcsub($remaining, $kept, 2);
            } else {
                if (bccomp($amount, $remaining, 2) > 0) {
                    throw new InventoryException("'{$method}' payment exceeds the remaining total.");
                }
                $paymentRows[] = ['method' => $method, 'amount' => $amount, 'change_given' => '0'];
                if ($method === 'credit') {
                    $creditTotal = bcadd($creditTotal, $amount, 2);
                }
                $remaining = bcsub($remaining, $amount, 2);
            }
        }

        if (bccomp($remaining, '0', 2) !== 0) {
            throw new InventoryException('Payments do not cover the sale total (missing: ' . $remaining . ').');
        }

        // Retry the atomic transaction on the rare concurrent receipt-number
        // collision (unique index store_id+receipt_number is the backstop).
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return $this->postTransaction(
                    $store, $resolved, $paymentRows, $actor, $shift, $heldSale,
                    $subtotal, $discount, $tax, $total, $warehouseId, $cashKept,
                    $customerId, $creditTotal,
                );
            } catch (\Illuminate\Database\QueryException $e) {
                if ($attempt === 2 || ! $this->isUniqueViolation($e)) {
                    throw $e;
                }
            }
        }

        throw new InventoryException('Could not post the sale — please retry.');
    }

    /**
     * The atomic posting transaction: sale record + receipt number + item
     * snapshots (COGS carried) + ledger movements + payments + drawer update.
     */
    private function postTransaction(
        Store $store,
        array $resolved,
        array $paymentRows,
        User $actor,
        ?CashierShift $shift,
        ?PosSale $heldSale,
        string $subtotal,
        string $discount,
        string $tax,
        string $total,
        int $warehouseId,
        string $cashKept,
        ?int $customerId = null,
        string $creditTotal = '0',
    ): PosSale {
        return DB::transaction(function () use (
            $store, $resolved, $paymentRows, $actor, $shift, $heldSale,
            $subtotal, $discount, $tax, $total, $warehouseId, $cashKept,
            $customerId, $creditTotal,
        ) {
            if ($heldSale) {
                // held = still waiting in the held list; resumed = recalled
                // into the active cart. Both reuse the same row when posted.
                if ((int) $heldSale->store_id !== (int) $store->id || ! in_array($heldSale->status, ['held', 'resumed'], true)) {
                    throw new InventoryException('The held sale cannot be posted from this store.');
                }
                $sale = $heldSale;
            } else {
                $sale = new PosSale(['store_id' => $store->id]);
            }

            $sale->fill([
                'branch_id' => $shift->branch_id,
                'cashier_shift_id' => $shift->id,
                'cashier_id' => $actor->id,
                'customer_id' => $customerId,
                'receipt_number' => $this->nextReceiptNumber($store),
                'status' => 'posted',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'posted_at' => now(),
                'created_by' => $actor->id,
            ])->save();

            if ($heldSale) {
                $heldSale->items()->delete();
            }

            foreach ($resolved as $i => $line) {
                $unitCost = $this->costing->resolveUnitCost(
                    ['product_id' => $line['product']->id, 'product_variant_id' => $line['variant']?->id],
                    $store->id,
                    $line['product']->id,
                    $warehouseId,
                    $line['variant']?->id,
                );

                PosSaleItem::create([
                    'pos_sale_id' => $sale->id,
                    'product_id' => $line['product']->id,
                    'product_variant_id' => $line['variant']?->id,
                    'product_name' => $line['name'],
                    'sku' => $line['sku'],
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'unit_cost' => $unitCost,
                    'line_total' => $line['line_total'],
                ]);

                // Ledger movement — the single source of truth. Idempotent via
                // client_transaction_id, atomic with the sale record.
                $this->inventory->postMovement([
                    'store_id' => $store->id,
                    'product_id' => $line['product']->id,
                    'product_variant_id' => $line['variant']?->id,
                    'warehouse_id' => $warehouseId,
                    'movement_type' => 'pos_sale',
                    'quantity_delta' => '-' . $line['quantity'],
                    'unit_cost' => $unitCost,
                    'source_type' => 'pos_sale',
                    'source_id' => $sale->id,
                    'client_transaction_id' => "pos_sale:{$sale->id}:{$i}",
                    'occurred_at' => now(),
                    'posted_by' => $actor->id,
                ]);
            }

            foreach ($paymentRows as $row) {
                PosPayment::create([
                    'pos_sale_id' => $sale->id,
                    'method' => $row['method'],
                    'amount' => $row['amount'],
                    'change_given' => $row['change_given'],
                    'created_by' => $actor->id,
                ]);
            }

            // The credit portion becomes a debt receivable (SoT §17) — a NEW
            // ledger entry referencing this sale, atomic with the sale itself.
            // Credit never touches the drawer.
            if (bccomp($creditTotal, '0', 2) > 0) {
                $this->debts->recordSaleDebt(
                    store: $store,
                    customerId: $customerId,
                    saleId: $sale->id,
                    amount: $creditTotal,
                    actor: $actor,
                    clientTransactionId: "pos_sale:{$sale->id}:debt",
                    branchId: $shift->branch_id,
                );
            }

            // Only the net cash actually kept goes into the drawer.
            if (bccomp($cashKept, '0', 2) > 0) {
                $this->shifts->recordCashSale($shift, $cashKept);
            }

            $this->clearCart($store);

            return $sale->load(['items', 'payments', 'customer']);
        });
    }

    /**
     * RCP-YYYYMMDD-#### sequence per store.
     */
    private function nextReceiptNumber(Store $store): string
    {
        $prefix = 'RCP-' . now()->format('Ymd') . '-';
        $seq = PosSale::query()
                ->where('store_id', $store->id)
                ->where('receipt_number', 'like', $prefix . '%')
                ->count() + 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    private function isUniqueViolation(\Illuminate\Database\QueryException $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'UNIQUE constraint failed')
            || str_contains($message, 'Duplicate entry')
            || str_contains($message, 'unique constraint');
    }

    /**
     * Today's posted sales for the store (for the POS home list).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, PosSale>
     */
    public function todaySales(Store $store, int $limit = 20)
    {
        return PosSale::query()
            ->with(['items', 'cashier', 'customer'])
            ->where('store_id', $store->id)
            ->whereNotNull('posted_at')
            ->whereDate('posted_at', today())
            ->latest('posted_at')
            ->limit($limit)
            ->get();
    }
}
