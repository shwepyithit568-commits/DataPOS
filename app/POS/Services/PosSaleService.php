<?php

namespace App\POS\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promotion;
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
        private readonly PromotionService $promotions,
        private readonly MembershipLoyaltyService $loyalty,
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

        $products = Product::query()
            ->where('store_id', $store->id)
            ->where(fn ($w) => $w->where('sku', 'like', "%{$q}%")->orWhere('barcode', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%"))
            ->limit($limit)
            ->get();

        $variants = ProductVariant::query()
            ->whereHas('product', fn ($q2) => $q2->where('store_id', $store->id))
            ->where(fn ($w) => $w->where('sku', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%"))
            ->with('product')
            ->limit($limit)
            ->get();

        // One grouped balance query for every hit (product + variant rows) —
        // the pre-fix code ran a SUM query per product AND per variant.
        $balances = $this->inventory->balancesForProducts(
            $store->id,
            $products->pluck('id')->merge($variants->pluck('product_id'))->all(),
        );

        $products->each(function (Product $p) use (&$results, $customer, $balances) {
            $results[] = [
                'type' => 'product',
                'id' => $p->id,
                'product_id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => $this->priceFor($customer, $p),
                'balance' => $this->balanceFromMap($balances, $p->id),
                'variant_of' => null,
            ];
        });

        $variants->each(function (ProductVariant $v) use (&$results, $customer, $balances) {
            $varBal = $this->balanceFromMap($balances, $v->product_id, $v->id);
            if ($varBal === '0.000' && (float) ($v->quantity_on_hand ?? 0) > 0 && !isset($balances[$v->product_id]['variants'][$v->id])) {
                $varBal = number_format((float) $v->quantity_on_hand, 3, '.', '');
            }
            $results[] = [
                'type' => 'variant',
                'id' => $v->id,
                'product_id' => $v->product_id,
                'name' => $v->product->name . ' — ' . $v->name,
                'sku' => $v->sku,
                'price' => $this->priceFor($customer, $v->product, $v),
                'balance' => $varBal,
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
    public function gridProducts(Store $store, ?int $categoryId = null, ?int $brandId = null, string $query = '', ?User $customer = null, int $limit = 120, bool $exactCode = false): array
    {
        $q = trim($query);

        $products = Product::query()
            ->where('store_id', $store->id)
            ->when($categoryId, fn ($w) => $w->where('category_id', $categoryId))
            ->when($brandId, fn ($w) => $w->where('brand_id', $brandId))
            ->when($exactCode && $q === '', fn ($w) => $w->whereRaw('1 = 0'))
            ->when($q !== '' && $exactCode, fn ($w) => $w->where(fn ($w2) => $w2
                ->where('sku', $q)->orWhere('barcode', $q)
                ->orWhereHas('variants', fn ($vq) => $vq->where('sku', $q))
            ))
            ->when($q !== '' && !$exactCode, fn ($w) => $w->where(fn ($w2) => $w2
                ->where('sku', 'like', "%{$q}%")
                ->orWhere('barcode', 'like', "%{$q}%")
                ->orWhere('name', 'like', "%{$q}%")
                ->orWhereHas('variants', fn ($vq) => $vq->where('sku', 'like', "%{$q}%"))
            ))
            ->with(['category:id,name', 'brand:id,name', 'variants:id,product_id,name,sku,retail_price,wholesale_price,is_default,quantity_on_hand'])
            ->orderBy('name')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        // One grouped balance query for the whole grid (product + every
        // variant) instead of a SUM query per row — the pre-fix grid issued
        // ~1 + variants-count queries per product.
        $balances = $this->inventory->balancesForProducts($store->id, $products->pluck('id')->all());

        // Resolve the tier once — the card shows the retail-vs-tier discount
        // when a wholesale customer is attached (gridProducts is the only
        // caller that needs the comparison; search keeps the plain price).
        $isWholesale = $this->customerTier($store->id, $customer);

        return $products->map(function (Product $p) use ($store, $customer, $isWholesale, $balances) {
            $prodBal = $this->balanceFromMap($balances, $p->id);
            $variants = $p->variants->map(function ($v) use ($customer, $p, $balances) {
                $varBal = $this->balanceFromMap($balances, $p->id, $v->id);
                if ($varBal === '0.000' && (float) ($v->quantity_on_hand ?? 0) > 0 && !isset($balances[$p->id]['variants'][$v->id])) {
                    $varBal = number_format((float) $v->quantity_on_hand, 3, '.', '');
                }
                return [
                    'id' => $v->id,
                    'name' => $v->name,
                    'sku' => $v->sku,
                    'price' => $this->priceFor($customer, $p, $v),
                    'retail_price' => (string) ($v->retail_price ?? $p->retail_price),
                    'balance' => $varBal,
                ];
            })->values()->all();

            if (!empty($variants)) {
                $variantSum = array_reduce($variants, fn ($carry, $v) => $carry + (float) $v['balance'], 0.0);
                if ($variantSum > 0 && (float) $prodBal <= 0) {
                    $prodBal = number_format($variantSum, 3, '.', '');
                }
            }

            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'price' => $this->priceFor($customer, $p),
                'retail_price' => (string) $p->retail_price,
                'tier' => $isWholesale ? 'wholesale' : 'retail',
                'old_price' => $p->old_price !== null ? (string) $p->old_price : null,
                'image' => $p->image_path ? asset('storage/' . $p->image_path) : null,
                'balance' => $prodBal,
                'category_id' => $p->category_id,
                'category' => $p->category?->name,
                'brand' => $p->brand?->name,
                'variants' => $variants,
            ];
        })->values()->all();
    }

    /**
     * Read a balance from a balancesForProducts() map with the same
     * 3-decimal string semantics as totalOnHand().
     */
    private function balanceFromMap(array $balances, int $productId, ?int $variantId = null): string
    {
        $entry = $balances[$productId] ?? null;

        if ($entry === null) {
            return '0.000';
        }

        if ($variantId !== null) {
            return (string) ($entry['variants'][$variantId] ?? '0.000');
        }

        return (string) ($entry['total'] ?? '0.000');
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

    private function resumedSaleKey(Store $store): string
    {
        return 'pos.resumed_sale.' . $store->id;
    }

    private function cartDiscountKey(Store $store): string
    {
        return 'pos.cart_discount.' . $store->id;
    }

    public function getDiscount(Store $store): string
    {
        $val = session()->get($this->cartDiscountKey($store), '0');
        return is_numeric($val) && (float) $val >= 0 ? (string) $val : '0';
    }

    public function setDiscount(Store $store, string $discount): void
    {
        session()->put($this->cartDiscountKey($store), $discount);
    }

    public function clearDiscount(Store $store): void
    {
        session()->forget($this->cartDiscountKey($store));
    }

    // ---------- Cart coupon ----------
    //
    // The coupon lives in the session next to the manual discount, so every
    // screen that reads cart totals (cart panel, payment modal, cash default,
    // receipt preview) shows the discounted amount without knowing about
    // coupons at all. Validation is re-run on every totals read: a cart edit
    // that drops the bill below the coupon's minimum simply stops applying it.

    private function cartCouponKey(Store $store): string
    {
        return 'pos.cart_coupon.' . $store->id;
    }

    /** The coupon currently attached to the cart, if any. */
    public function getCoupon(Store $store): ?Promotion
    {
        $code = session()->get($this->cartCouponKey($store));

        if (! is_string($code) || trim($code) === '') {
            return null;
        }

        return Promotion::where('store_id', $store->id)->where('code', strtoupper(trim($code)))->first();
    }

    public function setCoupon(Store $store, ?string $code): void
    {
        $code = $code !== null ? trim($code) : '';

        if ($code === '') {
            $this->clearCoupon($store);

            return;
        }

        session()->put($this->cartCouponKey($store), strtoupper($code));
    }

    public function clearCoupon(Store $store): void
    {
        session()->forget($this->cartCouponKey($store));
    }

    /**
     * Validate the attached coupon against an order value.
     *
     * @return array{valid:bool, discount:string, reason:?string, params:array<string,string>, promotion:?Promotion}
     */
    public function couponFor(Store $store, string $orderTotal, ?User $customer, array $lines = []): array
    {
        $promotion = $this->getCoupon($store);

        if (! $promotion) {
            return ['valid' => false, 'discount' => '0.00', 'reason' => null, 'params' => [], 'promotion' => null];
        }

        // Pricing lives in the promotion service so the counter and the online
        // checkout can never disagree about scope or BOGO.
        return $this->promotions->quoteLines($store, $promotion->code, $lines, $customer?->id);
    }

    /**
     * Cart lines in the shape the promotion service prices from.
     *
     * @param  array<int, array{product:object, quantity:string, unit_price:string, line_total:string}>  $resolved
     */
    private function promotionLines(array $resolved): array
    {
        return array_map(fn (array $line) => [
            'product_id' => (int) $line['product']->id,
            'quantity' => (string) $line['quantity'],
            'unit_price' => (string) $line['unit_price'],
            'line_total' => (string) $line['line_total'],
        ], $resolved);
    }

    // ---------- Cart points ----------
    //
    // Points a customer spends on this cart, stored in the session beside the
    // discount and coupon so the totals on every screen are the totals charged.

    private function cartPointsKey(Store $store): string
    {
        return 'pos.cart_points.' . $store->id;
    }

    public function getPoints(Store $store): int
    {
        return max(0, (int) session()->get($this->cartPointsKey($store), 0));
    }

    public function setPoints(Store $store, int $points): void
    {
        if ($points <= 0) {
            $this->clearPoints($store);

            return;
        }

        session()->put($this->cartPointsKey($store), $points);
    }

    public function clearPoints(Store $store): void
    {
        session()->forget($this->cartPointsKey($store));
    }

    /**
     * The points discount for the live cart: how many points apply and what they
     * are worth, bounded by the customer's balance and the bill.
     *
     * @return array{points:int, value:string, max:int, enabled:bool}
     */
    public function pointsFor(Store $store, string $bill, ?User $customer): array
    {
        $loyalty = $this->loyalty;
        $enabled = $loyalty->isRedemptionEnabled($store);
        $max = $loyalty->maxRedeemablePoints($store, $customer, $bill);

        if (! $enabled || ! $customer) {
            return ['points' => 0, 'value' => '0.00', 'max' => 0, 'enabled' => false];
        }

        $points = min($this->getPoints($store), $max);
        $value = $points > 0 ? bcmul((string) $points, $loyalty->redemptionValue($store), 2) : '0.00';

        return ['points' => $points, 'value' => $value, 'max' => $max, 'enabled' => true];
    }

    /**
     * Validate a coupon the cashier just typed against the live cart, without
     * attaching it. Scoped promotions need the cart lines to price their part,
     * so this is the only correct entry point for a code that is not on the
     * cart yet.
     *
     * @return array{valid:bool, discount:string, reason:?string, params:array<string,string>, promotion:?Promotion}
     */
    public function previewCoupon(Store $store, string $code, ?User $customer): array
    {
        return $this->promotions->quoteLines(
            store: $store,
            code: $code,
            lines: $this->promotionLines($this->cartResolved($store)),
            customerId: $customer?->id,
        );
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
     * True when the customer is a wholesale member of the store. The memberships
     * relation is loaded once per user and then cached on the model instance
     * (fresh per request / test), so grid/search loops over many products don't
     * re-query the pivot for every row.
     */
    private function customerTier(int $storeId, ?User $customer): bool
    {
        if ($customer === null) {
            return false;
        }

        $customer->loadMissing('stores');
        $membership = $customer->stores->firstWhere('id', $storeId)?->pivot;

        return $membership?->status === 'active' && $membership->role === 'wholesale_customer';
    }

    /**
     * Tiered unit price for a product/variant. Wholesale customers pay the
     * wholesale price when one is set (> 0); everyone else (walk-in, retail
     * customers) pays retail. Mirrors the storefront wholesale rule.
     */
    public function priceFor(?User $customer, Product $product, ?ProductVariant $variant = null): string
    {
        if ($this->customerTier($product->store_id, $customer)) {
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

    /**
     * Negotiated per-line price override. null clears it and the line returns
     * to the customer-tier price; any non-negative decimal wins until cleared.
     * The override is kept when the same product is added again (the cashier
     * set it deliberately) and survives hold/resume.
     */
    public function setCartLinePrice(Store $store, int $index, ?string $unitPrice, ?User $approver = null): void
    {
        $lines = $this->cartLines($store);
        if (! isset($lines[$index])) {
            throw new InventoryException('Cart line not found.');
        }

        if ($unitPrice === null) {
            unset($lines[$index]['unit_price'], $lines[$index]['approved_by']);
        } else {
            // Canonical 2-decimal form, so the cart snapshot is consistent
            // with the tier prices (decimal columns) it replaces.
            $lines[$index]['unit_price'] = bcadd($unitPrice, '0', 2);
            if ($approver !== null) {
                $lines[$index]['approved_by'] = $approver->id;
            }
        }

        session([$this->cartKey($store) => $lines]);
    }

    /**
     * The customer-tier unit price for a cart line (ignoring any override) —
     * the baseline the controller compares an override discount against.
     */
    public function tierPriceForCartLine(Store $store, int $index): string
    {
        $line = $this->cartLines($store)[$index] ?? null;
        if ($line === null) {
            throw new InventoryException('Cart line not found.');
        }

        $product = Product::find($line['product_id']);
        if (! $product || (int) $product->store_id !== (int) $store->id) {
            throw new InventoryException('Cart line references a product outside this store.');
        }

        $variant = $line['product_variant_id'] ? ProductVariant::find($line['product_variant_id']) : null;

        return $this->priceFor($this->cartCustomer($store), $product, $variant);
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
        session()->forget($this->resumedSaleKey($store));
        $this->clearDiscount($store);
        $this->clearCoupon($store);
        $this->clearPoints($store);
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
            $tierPrice = $this->priceFor($customer, $product, $variant);
            $override = (isset($line['unit_price']) && (string) $line['unit_price'] !== '') ? (string) $line['unit_price'] : null;
            $price = $override ?? $tierPrice;
            $quantity = $line['quantity'];

            $retailPrice = (string) ($variant?->retail_price ?? $product->retail_price);
            $isTaxable = (bool) ($product->is_taxable ?? true);
            $taxRate = $product->tax_rate !== null ? (float) $product->tax_rate : null;

            $out[] = [
                'index' => $i,
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'quantity' => $quantity,
                'name' => $name,
                'sku' => $variant?->sku ?? $product->sku,
                'unit_price' => (string) $price,
                'original_unit_price' => $override !== null ? (string) $tierPrice : null,
                'retail_unit_price' => $retailPrice,
                'line_total' => bcmul((string) $price, $quantity, 2),
                'line_retail_total' => bcmul($retailPrice, $quantity, 2),
                'approved_by' => isset($line['approved_by']) ? (int) $line['approved_by'] : null,
                'balance' => $this->inventory->totalOnHand($store->id, $product->id, $variant?->id),
                'is_taxable' => $isTaxable,
                'tax_rate' => $taxRate,
                'product' => $product,
            ];
        }

        // Attach the approver's name once for the whole cart (no per-row query).
        $approverIds = array_values(array_filter(array_column($out, 'approved_by')));
        $approverNames = $approverIds
            ? User::whereIn('id', $approverIds)->pluck('name', 'id')->all()
            : [];
        foreach ($out as &$line) {
            $line['approved_by_name'] = $line['approved_by'] !== null
                ? ($approverNames[$line['approved_by']] ?? null)
                : null;
        }
        unset($line);

        return $out;
    }

    /**
     * @return array{subtotal:string, retail_subtotal:string, discount:string, manual_discount:string, tax:string, tax_type:string, tax_enabled:bool, default_tax_rate:float, taxable_subtotal:string, exempt_subtotal:string, total:string}
     */
    public function cartTotals(Store $store): array
    {
        $subtotal = '0';
        $retailSubtotal = '0';
        $taxableSubtotal = '0';
        $exemptSubtotal = '0';
        $taxTotal = '0';

        $enableTax = (bool) ($store->setting?->getPosSetting('enable_tax', false));
        $defaultRate = (string) ($store->setting?->getPosSetting('default_tax_rate', 5.0));
        $taxType = (string) ($store->setting?->getPosSetting('tax_type', 'exclusive'));

        $lines = $this->cartResolved($store);

        foreach ($lines as $line) {
            $subtotal = bcadd($subtotal, $line['line_total'], 2);
            $retailSubtotal = bcadd($retailSubtotal, $line['line_retail_total'], 2);

            if ($enableTax) {
                $isTaxable = (bool) ($line['is_taxable'] ?? true);
                $rate = $line['tax_rate'] !== null ? (string) $line['tax_rate'] : $defaultRate;
                if (bccomp($rate, '0', 2) <= 0) {
                    $isTaxable = false;
                }

                if ($isTaxable) {
                    $taxableSubtotal = bcadd($taxableSubtotal, $line['line_total'], 2);
                    if ($taxType === 'inclusive') {
                        $lineTax = bcdiv(bcmul($line['line_total'], $rate, 4), bcadd('100', $rate, 4), 2);
                    } else {
                        $lineTax = bcmul($line['line_total'], bcdiv($rate, '100', 6), 2);
                    }
                    $taxTotal = bcadd($taxTotal, $lineTax, 2);
                } else {
                    $exemptSubtotal = bcadd($exemptSubtotal, $line['line_total'], 2);
                }
            } else {
                $exemptSubtotal = bcadd($exemptSubtotal, $line['line_total'], 2);
            }
        }

        // `$discount` accumulates everything that comes off the bill — the
        // cashier's manual discount PLUS the coupon and PLUS the points spent.
        // Both extras are re-priced from the sale's own lines when the sale is
        // posted, so only the manual part may be sent back as the posted
        // discount; that is why it is exposed on its own as `manual_discount`.
        $manualDiscount = $this->getDiscount($store);
        $discount = $manualDiscount;
        if ($enableTax && $taxType === 'exclusive') {
            $rawTotal = bcadd($subtotal, $taxTotal, 2);
        } else {
            $rawTotal = $subtotal;
        }

        // Coupon: validated against the subtotal, added to the manual discount
        // and capped at the bill. When the cart changes so the coupon no longer
        // qualifies (min order, expiry, limit) it silently stops applying here —
        // the same rule the posting path uses, so the shown total is the
        // charged total.
        $coupon = $this->couponFor($store, $subtotal, $this->cartCustomer($store), $lines);
        $couponDiscount = $coupon['valid'] ? $coupon['discount'] : '0.00';

        if (bccomp($couponDiscount, '0', 2) > 0) {
            $discount = bcadd($discount, $couponDiscount, 2);
        }

        // Points the customer is spending on this bill — bounded by the balance
        // and by what is left after the other discounts.
        $pointsBill = bcsub($rawTotal, $discount, 2);
        $points = $this->pointsFor($store, $pointsBill, $this->cartCustomer($store));

        if (bccomp($points['value'], '0', 2) > 0) {
            $discount = bcadd($discount, $points['value'], 2);
        }

        if (bccomp($discount, $rawTotal, 2) > 0) {
            $discount = $rawTotal;
        }

        $total = bcsub($rawTotal, $discount, 2);
        if (bccomp($total, '0', 2) < 0) {
            $total = '0.00';
        }

        return [
            'subtotal' => $subtotal,
            'retail_subtotal' => $retailSubtotal,
            'manual_discount' => $manualDiscount,
            'discount' => $discount,
            'tax' => $taxTotal,
            'tax_type' => $taxType,
            'tax_enabled' => $enableTax,
            'default_tax_rate' => (float) $defaultRate,
            'taxable_subtotal' => $taxableSubtotal,
            'exempt_subtotal' => $exemptSubtotal,
            'total' => $total,
            'coupon_code' => $coupon['promotion']?->code,
            'coupon_name' => $coupon['promotion']?->name,
            'coupon_discount' => $couponDiscount,
            'coupon_valid' => $coupon['valid'],
            'coupon_reason' => $coupon['reason'],
            'points_redeemed' => $points['points'],
            'points_value' => $points['value'],
            'points_max' => $points['max'],
            'points_max_value' => $points['max'] > 0
                ? bcmul((string) $points['max'], $this->loyalty->redemptionValue($store), 2)
                : '0.00',
            'points_enabled' => $points['enabled'],
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
                'original_unit_price' => $line['original_unit_price'],
                'retail_unit_price' => $line['retail_unit_price'],
                'line_total' => $line['line_total'],
                'line_retail_total' => $line['line_retail_total'],
                'approved_by' => $line['approved_by'],
                'approved_by_name' => $line['approved_by_name'],
                'balance' => $line['balance'],
                'is_taxable' => $line['is_taxable'] ?? true,
                'tax_rate' => $line['tax_rate'] ?? null,
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

        $shiftsEnabled = $store->hasCapability(\App\Capabilities\Capability::OPERATIONS_CASHIER_SHIFTS);

        return [
            'shifts_enabled' => $shiftsEnabled,
            'shift_open' => $shiftsEnabled ? (bool) $this->shifts->openShiftFor($store, $actor) : true,
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
     *
     * 'resumed' rows are expired too: a sale recalled into the cart but never
     * posted (cashier cleared the cart / walked away) would otherwise linger
     * forever as a zombie that no list ever shows.
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
            ->whereIn('status', ['held', 'resumed'])
            ->where('created_at', '<', $cutoff)
            ->get();

        foreach ($stale as $sale) {
            $sale->update([
                'status' => 'voided',
                'voided_at' => now(),
                'notes' => trim(($sale->notes ?? '') . ($sale->status === 'resumed'
                    ? ' Abandoned — resumed over ' . $olderThanHours . 'h without posting, auto-voided at '
                    : ' Expired — held over ' . $olderThanHours . 'h, auto-voided at ') . now()->format('Y-m-d H:i')),
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

        // A fresh hold breaks the resume link: the cart came from a recalled
        // sale, but holding it again starts a NEW held row — the session must
        // no longer point at the old 'resumed' record.
        session()->forget($this->resumedSaleKey($store));

        return DB::transaction(function () use ($store, $lines, $actor, $shift) {
            $totals = $this->cartTotals($store);

            $sale = PosSale::create([
                'store_id' => $store->id,
                'branch_id' => $shift?->branch_id,
                'cashier_shift_id' => $shift?->id,
                'cashier_id' => $actor->id,
                'status' => 'held',
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'tax' => $totals['tax'],
                'tax_type' => $totals['tax_type'],
                'taxable_amount' => $totals['taxable_subtotal'],
                'exempt_amount' => $totals['exempt_subtotal'],
                'total' => $totals['total'],
                'created_by' => $actor->id,
            ]);

            foreach ($lines as $line) {
                $sale->items()->create([
                    'product_id' => $line['product_id'],
                    'product_variant_id' => $line['product_variant_id'],
                    'product_name' => $line['name'],
                    'sku' => $line['sku'],
                    'unit_price' => $line['unit_price'],
                    // A negotiated override is snapshotted so the held sale
                    // (and its receipt after posting) shows what it replaced.
                    'original_unit_price' => $line['original_unit_price'] ?? null,
                    'approved_by' => $line['approved_by'] ?? null,
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                    'is_taxable' => $line['is_taxable'] ?? true,
                    'tax_rate' => $line['tax_rate'] ?? 0,
                    'tax_amount' => '0',
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

        // Resuming REPLACES the active cart with the held sale's lines — never
        // let that silently discard a cart the cashier already priced.
        if ($this->cartLines($store) !== []) {
            throw new InventoryException('The cart already has items — post, hold or clear it before resuming a held sale.');
        }

        $lines = [];
        foreach ($sale->items as $item) {
            $lines[] = [
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'quantity' => (string) $item->quantity,
                // A negotiated price survives hold/resume (original_unit_price
                // marks the override); other lines re-price at the current tier.
                'unit_price' => $item->original_unit_price !== null ? (string) $item->unit_price : null,
                'approved_by' => $item->approved_by,
            ];
        }

        // The held record leaves the held list the moment it is recalled — it
        // is marked 'resumed' (not deleted, so the audit trail is kept) and
        // can only be posted or voided from here on.
        $sale->update([
            'status' => 'resumed',
            'notes' => trim(($sale->notes ?? '') . ' Resumed by ' . ($actor?->name ?? 'unknown') . ' at ' . now()->format('Y-m-d H:i')),
        ]);

        // Remember which row this cart came from so posting (which the UI does
        // without the sale id) reuses THE SAME row instead of orphaning a
        // 'resumed' record that can never be closed.
        session([$this->resumedSaleKey($store) => $sale->id]);

        if (bccomp((string) $sale->discount, '0', 2) > 0) {
            $this->setDiscount($store, (string) $sale->discount);
        } else {
            $this->clearDiscount($store);
        }

        session([$this->cartKey($store) => $lines]);
    }

    /**
     * The held sale the cashier most recently recalled via resume(), when it
     * is still in the 'resumed' state. The controller uses it on post() so the
     * resumed row transitions to 'posted' instead of being orphaned. A stale
     * key (row posted / voided / expired / cross-store meanwhile) clears itself.
     */
    public function sessionResumedSale(Store $store): ?PosSale
    {
        $id = (int) session()->get($this->resumedSaleKey($store), 0);
        if ($id <= 0) {
            return null;
        }

        $sale = PosSale::find($id);
        if (! $sale || (int) $sale->store_id !== (int) $store->id || $sale->status !== 'resumed') {
            session()->forget($this->resumedSaleKey($store));

            return null;
        }

        return $sale;
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
        ?string $explicitDiscount = null,
        ?string $couponCode = null,
    ): PosSale {
        app(PeriodLockService::class)->assertDateNotLocked($store, now(), 'sale');

        if ($store->hasCapability(\App\Capabilities\Capability::OPERATIONS_CASHIER_SHIFTS)) {
            if (! $shift?->isOpen() || (int) $shift->store_id !== (int) $store->id) {
                throw new InventoryException('An open cashier shift is required to post a sale.');
            }
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

            $tierPrice = $this->priceFor($customer, $product, $variant);
            $override = (isset($line['unit_price']) && (string) $line['unit_price'] !== '') ? (string) $line['unit_price'] : null;
            $price = $override ?? $tierPrice;
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
                'original_unit_price' => $override !== null ? (string) $tierPrice : null,
                'approved_by' => isset($line['approved_by']) ? (int) $line['approved_by'] : null,
                'line_total' => $lineTotal,
                'name' => $variant ? $product->name . ' — ' . $variant->name : $product->name,
                'sku' => $variant?->sku ?? $product->sku,
            ];
        }

        // Payment math: calculate Commercial Tax according to store settings and product exemptions.
        $enableTax = (bool) ($store->setting?->getPosSetting('enable_tax', false));
        $defaultRate = (string) ($store->setting?->getPosSetting('default_tax_rate', 5.0));
        $taxType = (string) ($store->setting?->getPosSetting('tax_type', 'exclusive'));

        $taxableAmount = '0';
        $exemptAmount = '0';
        $taxTotal = '0';

        foreach ($resolved as &$resItem) {
            $isTaxable = (bool) ($resItem['product']->is_taxable ?? true);
            $rate = $resItem['product']->tax_rate !== null ? (string) $resItem['product']->tax_rate : $defaultRate;
            if (bccomp($rate, '0', 2) <= 0) {
                $isTaxable = false;
            }

            if ($enableTax && $isTaxable) {
                $taxableAmount = bcadd($taxableAmount, $resItem['line_total'], 2);
                if ($taxType === 'inclusive') {
                    $itemTax = bcdiv(bcmul($resItem['line_total'], $rate, 4), bcadd('100', $rate, 4), 2);
                } else {
                    $itemTax = bcmul($resItem['line_total'], bcdiv($rate, '100', 6), 2);
                }
                $taxTotal = bcadd($taxTotal, $itemTax, 2);
                $resItem['is_taxable'] = true;
                $resItem['tax_rate'] = $rate;
                $resItem['tax_amount'] = $itemTax;
            } else {
                $exemptAmount = bcadd($exemptAmount, $resItem['line_total'], 2);
                $resItem['is_taxable'] = false;
                $resItem['tax_rate'] = '0';
                $resItem['tax_amount'] = '0';
            }
        }
        unset($resItem);

        $discount = $explicitDiscount !== null ? (string) $explicitDiscount : $this->getDiscount($store);
        if (bccomp($discount, '0', 2) < 0) {
            $discount = '0.00';
        }

        $tax = $enableTax ? $taxTotal : '0';

        // Coupon: validated against the pre-discount subtotal (a promotion is an
        // offer on the order value), added to any manual discount, and capped at
        // the bill so it can never go negative. The redemption is written inside
        // the posting transaction below.
        $coupon = null;
        $couponDiscount = '0.00';
        $postedCoupon = $couponCode !== null && trim($couponCode) !== '';
        $sessionCoupon = $this->getCoupon($store);

        if ($postedCoupon || $sessionCoupon) {
            $code = $postedCoupon ? trim((string) $couponCode) : (string) $sessionCoupon->code;

            // Scoped and BOGO coupons are priced from the sale's own lines.
            $check = $this->promotions->quoteLines($store, $code, $this->promotionLines($resolved), $customerId);

            if ($check['valid']) {
                $coupon = $check['promotion'];
                $couponDiscount = $check['discount'];
                $discount = bcadd($discount, $couponDiscount, 2);

                $billBase = ($enableTax && $taxType === 'exclusive') ? bcadd($subtotal, $tax, 2) : $subtotal;

                if (bccomp($discount, $billBase, 2) > 0) {
                    $discount = $billBase;
                }
            } elseif ($postedCoupon) {
                // The cashier asked for this code on this sale — refuse loudly.
                throw new InventoryException(
                    __('messages.coupon_rejected') . ' ' . __('messages.' . ($check['reason'] ?? 'coupon_not_found'), $check['params'] ?? [])
                );
            }
            // A cart coupon that stopped qualifying (the bill dropped below its
            // minimum, it expired, its limit is used up) is simply not applied:
            // it was already excluded from the total the cashier was shown.
        }

        // Points: spend them on this bill (bounded by the balance and what is
        // left). The ledger row is written inside the posting transaction.
        $pointsBill = bcsub(($enableTax && $taxType === 'exclusive') ? bcadd($subtotal, $tax, 2) : $subtotal, $discount, 2);
        $points = $this->pointsFor($store, $pointsBill, $customer);

        if (bccomp($points['value'], '0', 2) > 0) {
            $discount = bcadd($discount, $points['value'], 2);
        }

        if ($enableTax && $taxType === 'exclusive') {
            $billBase = bcadd($subtotal, $tax, 2);
        } else {
            $billBase = $subtotal;
        }

        if (bccomp($discount, $billBase, 2) > 0) {
            $discount = $billBase;
        }

        if ($enableTax && $taxType === 'exclusive') {
            $total = bcsub(bcadd($subtotal, $tax, 2), $discount, 2);
        } else {
            $total = bcsub($subtotal, $discount, 2);
        }
        if ($enableTax && $taxType === 'exclusive') {
            $total = bcsub(bcadd($subtotal, $tax, 2), $discount, 2);
        } else {
            $total = bcsub($subtotal, $discount, 2);
        }
        if (bccomp($total, '0', 2) < 0) {
            $total = '0.00';
        }

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

            $reference = isset($payment['reference']) && trim((string) $payment['reference']) !== ''
                ? trim((string) $payment['reference'])
                : null;

            if ($method === 'cash') {
                $kept = bccomp($amount, $remaining, 2) > 0 ? $remaining : $amount;
                $change = bcsub($amount, $kept, 2);
                $cashKept = bcadd($cashKept, $kept, 2);
                $paymentRows[] = ['method' => 'cash', 'amount' => $amount, 'change_given' => $change, 'reference' => $reference];
                $remaining = bcsub($remaining, $kept, 2);
            } else {
                if (bccomp($amount, $remaining, 2) > 0) {
                    throw new InventoryException("'{$method}' payment exceeds the remaining total.");
                }
                $paymentRows[] = ['method' => $method, 'amount' => $amount, 'change_given' => '0', 'reference' => $reference];
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
                    $customerId, $creditTotal, $taxType, $taxableAmount, $exemptAmount,
                    $coupon, $couponDiscount, $points['points'],
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
        string $taxType = 'exclusive',
        string $taxableAmount = '0',
        string $exemptAmount = '0',
        ?Promotion $coupon = null,
        string $couponDiscount = '0.00',
        int $pointsToRedeem = 0,
    ): PosSale {
        return DB::transaction(function () use (
            $store, $resolved, $paymentRows, $actor, $shift, $heldSale,
            $subtotal, $discount, $tax, $total, $warehouseId, $cashKept,
            $customerId, $creditTotal, $taxType, $taxableAmount, $exemptAmount,
            $coupon, $couponDiscount, $pointsToRedeem,
        ) {
            if ($heldSale) {
                // held = still waiting in the held list; resumed = recalled
                // into the active cart. Both reuse the same row when posted.
                //
                // Re-read under a lock: without it, two simultaneous posts of the
                // same held sale would both pass this check and each write a full
                // set of payment rows against the one sale.
                $locked = PosSale::whereKey($heldSale->id)->lockForUpdate()->firstOrFail();

                if ((int) $locked->store_id !== (int) $store->id || ! in_array($locked->status, ['held', 'resumed'], true)) {
                    throw new InventoryException('The held sale cannot be posted from this store.');
                }
                $sale = $locked;
            } else {
                $sale = new PosSale(['store_id' => $store->id]);
            }

            $sale->fill([
                'branch_id' => $shift?->branch_id,
                'cashier_shift_id' => $shift?->id,
                'cashier_id' => $actor->id,
                'customer_id' => $customerId,
                'receipt_number' => $this->nextReceiptNumber($store),
                'status' => 'posted',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'tax_type' => $taxType,
                'taxable_amount' => $taxableAmount,
                'exempt_amount' => $exemptAmount,
                'total' => $total,
                'posted_at' => now(),
                'created_by' => $actor->id,
            ]);

            // Which coupon priced this sale — the columns existed but nothing
            // ever filled them, so promotions could never be reported on.
            if ($coupon) {
                $sale->promotion_id = $coupon->id;
                $sale->coupon_code = $coupon->code;
            }

            $sale->save();

            if ($heldSale) {
                $sale->items()->delete();
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
                    'original_unit_price' => $line['original_unit_price'],
                    'approved_by' => $line['approved_by'] ?? null,
                    'quantity' => $line['quantity'],
                    'unit_cost' => $unitCost,
                    'line_total' => $line['line_total'],
                    'is_taxable' => $line['is_taxable'] ?? true,
                    'tax_rate' => $line['tax_rate'] ?? 0,
                    'tax_amount' => $line['tax_amount'] ?? 0,
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
                    'reference' => $row['reference'] ?? null,
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
                    branchId: $shift?->branch_id,
                );
            }

            // Only the net cash actually kept goes into the drawer.
            if ($shift && bccomp($cashKept, '0', 2) > 0) {
                $this->shifts->recordCashSale($shift, $cashKept);
            }

            // Coupon redemption ledger: usage row + used counter, inside the same
            // transaction so the limit can never be bypassed by a retry.
            if ($coupon) {
                $this->promotions->redeem(
                    store: $store,
                    promotion: $coupon,
                    discountApplied: $couponDiscount,
                    customerId: $customerId,
                    posSaleId: $sale->id,
                    actor: $actor,
                );
            }

            // Loyalty: points spent on this bill leave first, then the points the
            // purchase earned are credited — all inside the sale transaction.
            if ($pointsToRedeem > 0) {
                $this->loyalty->redeemForSale($sale, $pointsToRedeem, $actor);
            }

            $this->loyalty->accrueForSale($sale, $actor);

            $this->clearCart($store);

            return $sale->load(['items', 'payments', 'customer']);
        });
    }

    /**
     * Atomic RCP-YYYYMMDD-#### sequence per store via DocumentSequenceService.
     */
    private function nextReceiptNumber(Store $store): string
    {
        return app(DocumentSequenceService::class)->nextNumber($store, 'sale');
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
