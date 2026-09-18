<?php

namespace App\Http\Controllers;

use App\Models\GlassFinderItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\POS\Services\PromotionService;
use App\Services\StoreContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Show the standalone Order Builder page (/order-builder).
     */
    public function builder(StoreContext $context): View
    {
        $store = $context->getStore();

        abort_unless((bool) $store, 404, 'Store not found.');

        $user = auth()->user();

        $isWholesaleApproved = $user && (
            $user->isPlatformOwner() ||
            $user->getStoreRole($store->id) === 'wholesale_customer'
        );

        return view('storefront.orders.builder', compact('store', 'isWholesaleApproved'));
    }

    /**
     * Store a newly created order in storage and redirect to a confirmation page.
     */
    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        abort_unless((bool) $store, 404, 'Store not found.');

        $validated = $request->validate([
            'items_json' => ['nullable', 'string'],
            'product_id' => ['nullable', \Illuminate\Validation\Rule::exists('products', 'id')->where('store_id', $store->id)],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'glass_finder_item_id' => ['nullable', \Illuminate\Validation\Rule::exists('glass_finder_items', 'id')->where('store_id', $store->id)],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'customer_address' => ['required', 'string', 'max:1000'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'contact_channel' => ['required', 'in:viber,telegram,phone'],
            'contact_identifier' => ['nullable', 'string', 'max:100'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
        ]);

        $user = auth()->user();

        // Check if user is approved wholesale for active store
        $isWholesaleApproved = $user && (
            $user->isPlatformOwner() ||
            $user->getStoreRole($store->id) === 'wholesale_customer'
        );

        $pricingType = $isWholesaleApproved ? 'wholesale' : 'retail';

        $orderItemsData = [];

        // 1. Multi-item Order Builder submission via JSON
        if (!empty($validated['items_json'])) {
            $decoded = json_decode($validated['items_json'], true);
            if (!is_array($decoded)) {
                return back()->withErrors(['items_json' => 'Invalid order builder data. Please refresh and try again.'])->withInput();
            }

            if (count($decoded) > 0) {
                foreach ($decoded as $itemData) {
                    $productId = $itemData['product_id'] ?? null;
                    $variantId = $itemData['product_variant_id'] ?? $itemData['variant_id'] ?? null;
                    $glassFinderItemId = $itemData['glass_finder_item_id'] ?? null;
                    $qty = max(1, (int) ($itemData['quantity'] ?? 1));
                    $qty = min($qty, 99);

                    if ($productId) {
                        $product = Product::where('store_id', $store->id)
                            ->where('id', $productId)
                            ->first();

                        if (!$product || !$product->is_ecommerce || !$product->isInStock()) {
                            return back()->withErrors(['items_json' => 'One or more selected products are unavailable. Please update the order list and try again.'])->withInput();
                        }

                        $variant = null;
                        if ($variantId) {
                            $variant = ProductVariant::where('product_id', $product->id)
                                ->where('id', $variantId)
                                ->first();

                            if (!$variant || !$variant->isInStock()) {
                                return back()->withErrors(['items_json' => 'One or more selected product variants are unavailable. Please update the order list and try again.'])->withInput();
                            }
                        }

                        $unitPrice = $this->resolveUnitPrice($variant, $product, $isWholesaleApproved);
                        // Decimal line total: bcmul, not `*`. The product is a
                        // float and its string form then feeds bcmath below, so
                        // float rounding would land on the customer's invoice.
                        $subtotal = bcmul($unitPrice, (string) $qty, 2);

                        $orderItemsData[] = [
                            'product_id' => $product->id,
                            'product_variant_id' => $variant?->id,
                            'product_name' => $variant ? "{$product->name} - {$variant->name}" : $product->name,
                            'variant_name' => $variant?->name,
                            'variant_sku' => $variant?->sku,
                            'unit_price' => $unitPrice,
                            'quantity' => $qty,
                            'subtotal' => $subtotal,
                        ];
                    } elseif ($glassFinderItemId) {
                        $glassItem = GlassFinderItem::where('store_id', $store->id)
                            ->where('id', $glassFinderItemId)
                            ->first();

                        if (!$glassItem || !$glassItem->isInStock()) {
                            return back()->withErrors(['items_json' => 'One or more selected glass items are unavailable. Please update the order list and try again.'])->withInput();
                        }

                        $orderItemsData[] = [
                            'product_id' => null,
                            'product_name' => "Glass: {$glassItem->phone_model} (Code: {$glassItem->glass_code})",
                            'unit_price' => 0,
                            'quantity' => $qty,
                            'subtotal' => 0,
                        ];
                    } else {
                        return back()->withErrors(['items_json' => 'One or more selected order items are invalid. Please update the order list and try again.'])->withInput();
                    }
                }
            }
        }

        // 2. Single item fallback submission
        if (empty($orderItemsData)) {
            $productName = '';
            $unitPrice = 0.00;
            $qty = (int) ($validated['quantity'] ?? 1);
            $variant = null;

            if (!empty($validated['product_id'])) {
                $product = Product::where('store_id', $store->id)
                    ->where('id', $validated['product_id'])
                    ->firstOrFail();

                if (!$product->is_ecommerce || !$product->isInStock()) {
                    return back()->withErrors(['product' => 'Sorry, this product is currently unavailable or out of stock.']);
                }

                $variant = null;
                if (!empty($validated['product_variant_id'])) {
                    $variant = ProductVariant::where('product_id', $product->id)
                        ->where('id', $validated['product_variant_id'])
                        ->first();

                    if (!$variant || !$variant->isInStock()) {
                        return back()->withErrors(['product' => 'Sorry, this product variant is currently unavailable.']);
                    }
                }

                $productName = $variant ? "{$product->name} - {$variant->name}" : $product->name;
                $unitPrice = $this->resolveUnitPrice($variant, $product, $isWholesaleApproved);
            } elseif (!empty($validated['glass_finder_item_id'])) {
                $glassItem = GlassFinderItem::where('store_id', $store->id)
                    ->where('id', $validated['glass_finder_item_id'])
                    ->firstOrFail();

                if (!$glassItem->isInStock()) {
                    return back()->withErrors(['product' => 'Sorry, this glass item is currently out of stock.']);
                }

                $productName = "Glass: {$glassItem->phone_model} (Code: {$glassItem->glass_code})";
                $unitPrice = 0.00;
            } else {
                return back()->withErrors(['product' => 'Invalid product selection.']);
            }

            $subtotal = bcmul($unitPrice, (string) $qty, 2);

            $orderItemsData[] = [
                'product_id' => $validated['product_id'] ?? null,
                'product_variant_id' => $variant?->id ?? null,
                'product_name' => $productName,
                'variant_name' => $variant?->name ?? null,
                'variant_sku' => $variant?->sku ?? null,
                'unit_price' => $unitPrice,
                'quantity' => $qty,
                'subtotal' => $subtotal,
            ];
        }

        $enableTax = (bool) ($store->setting?->getPosSetting('enable_tax', false));
        $defaultRate = (string) ($store->setting?->getPosSetting('default_tax_rate', 5.0));
        $taxType = (string) ($store->setting?->getPosSetting('tax_type', 'exclusive'));

        $subtotalAmount = '0';
        $taxableAmount = '0';
        $taxTotal = '0';

        foreach ($orderItemsData as &$it) {
            $lineSubtotal = (string) ($it['subtotal'] ?? 0);
            $subtotalAmount = bcadd($subtotalAmount, $lineSubtotal, 2);

            $isTaxable = true;
            $rate = $defaultRate;
            if (!empty($it['product_id'])) {
                $p = Product::find($it['product_id']);
                $isTaxable = (bool) ($p?->is_taxable ?? true);
                if ($p?->tax_rate !== null) {
                    $rate = (string) $p->tax_rate;
                }
            } else {
                $isTaxable = false;
            }

            if ($enableTax && $isTaxable && bccomp($rate, '0', 2) > 0) {
                $taxableAmount = bcadd($taxableAmount, $lineSubtotal, 2);
                if ($taxType === 'inclusive') {
                    $itemTax = bcdiv(bcmul($lineSubtotal, $rate, 4), bcadd('100', $rate, 4), 2);
                } else {
                    $itemTax = bcmul($lineSubtotal, bcdiv($rate, '100', 6), 2);
                }
                $taxTotal = bcadd($taxTotal, $itemTax, 2);
                $it['is_taxable'] = true;
                $it['tax_rate'] = $rate;
                $it['tax_amount'] = $itemTax;
            } else {
                $it['is_taxable'] = false;
                $it['tax_rate'] = '0';
                $it['tax_amount'] = '0';
            }
        }
        unset($it);

        if ($enableTax && $taxType === 'exclusive') {
            $finalTotal = bcadd($subtotalAmount, $taxTotal, 2);
        } else {
            $finalTotal = $subtotalAmount;
        }

        // Coupon: checked against the pre-discount items, priced on the lines the
        // promotion actually covers (product/category scope), then subtracted from
        // the bill. A refused code stops the order instead of silently ignoring it
        // — the customer typed it on purpose.
        $promotions = app(PromotionService::class);
        $coupon = null;
        $couponDiscount = '0.00';

        if (! empty($validated['coupon_code'])) {
            $check = $promotions->quoteLines(
                store: $store,
                code: (string) $validated['coupon_code'],
                lines: $orderItemsData,
                customerId: $user?->id,
            );

            if (! $check['valid']) {
                return back()->withInput()->withErrors([
                    'coupon_code' => __('messages.coupon_rejected') . ' '
                        . __('messages.' . ($check['reason'] ?? 'coupon_not_found'), $check['params'] ?? []),
                ]);
            }

            $coupon = $check['promotion'];
            $couponDiscount = $check['discount'];

            $finalTotal = bcsub($finalTotal, $couponDiscount, 2);

            if (bccomp($finalTotal, '0', 2) < 0) {
                $finalTotal = '0.00';
            }
        }

        $order = DB::transaction(function () use ($store, $user, $validated, $pricingType, $finalTotal, $taxTotal, $taxType, $taxableAmount, $enableTax, $orderItemsData, $coupon, $couponDiscount, $promotions) {
            $order = Order::create([
                'store_id' => $store->id,
                'user_id' => $user?->id,
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_address' => $validated['customer_address'] ?? null,
                'customer_note' => $validated['customer_note'] ?? null,
                'contact_channel' => $validated['contact_channel'],
                'contact_identifier' => $validated['contact_identifier'] ?? null,
                'pricing_type' => $pricingType,
                'total_amount' => $finalTotal,
                'tax' => $enableTax ? $taxTotal : 0,
                'tax_type' => $taxType,
                'taxable_amount' => $taxableAmount,
                'discount_amount' => $couponDiscount,
                'coupon_code' => $coupon?->code,
                'promotion_id' => $coupon?->id,
                'status' => 'pending_contact',
            ]);

            foreach ($orderItemsData as $itemData) {
                $order->items()->create($itemData);
            }

            // The redemption ledger row lives with the order it priced.
            if ($coupon) {
                $promotions->redeem(
                    store: $store,
                    promotion: $coupon,
                    discountApplied: $couponDiscount,
                    customerId: $user?->id,
                    actor: $user,
                    orderId: $order->id,
                );
            }

            return $order;
        });

        // Notify the store's admin(s) of the new order via Web Push.
        // Deduped per order (double-clicks can't double-notify), queued so
        // order creation is never slowed, and best-effort — a push failure
        // must never break order creation. The audit log row is written here.
        app(\App\Support\AdminPushNotifier::class)->dispatch(
            $store,
            'order-created.' . $order->id,
            new \App\Notifications\NewOrderNotification($order),
        );

        // Redirect to confirmation page
        $redirectParams = [
            'store_slug' => $store->slug,
            'order' => $order->id,
        ];
        if (!$user) {
            $redirectParams['token'] = $order->confirmation_token;
        }

        return redirect()->route('orders.confirmation', $redirectParams);
    }

    /**
     * Resolve the price a shopper pays for a product (or one of its variants).
     *
     * Returns a decimal string, never a float, so line totals stay exact when
     * they are accumulated with bcmath. A wholesale shopper whose product has no
     * wholesale price falls back to retail rather than to null.
     */
    private function resolveUnitPrice(?ProductVariant $variant, Product $product, bool $isWholesaleApproved): string
    {
        $wholesale = $variant ? $variant->wholesale_price : $product->wholesale_price;
        $retail = $variant ? $variant->retail_price : $product->retail_price;

        if ($isWholesaleApproved && $wholesale !== null && $wholesale !== '') {
            return (string) $wholesale;
        }

        return (string) ($retail ?? '0');
    }

    /**
     * Check a coupon against the order builder's list before the customer submits.
     *
     * Public on purpose: it returns the same information any shop's checkout gives
     * back when a coupon is typed. Nothing is reserved or redeemed here.
     */
    public function validateCoupon(Request $request, StoreContext $context): JsonResponse
    {
        $store = $context->getStore();

        if (! $store) {
            return response()->json(['valid' => false, 'message' => __('messages.coupon_not_found')], 404);
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'max:40'],
            'items_json' => ['nullable', 'string'],
        ]);

        // Rebuild only what eligibility needs (product id + line total); the
        // submit path re-validates everything for real.
        $items = [];
        $subtotal = '0';

        $decoded = json_decode((string) ($data['items_json'] ?? '[]'), true);

        if (is_array($decoded)) {
            foreach ($decoded as $row) {
                $productId = (int) ($row['product_id'] ?? 0);
                $quantity = max(1, min(99, (int) ($row['quantity'] ?? 1)));

                if ($productId <= 0) {
                    continue;
                }

                $product = Product::where('store_id', $store->id)->where('id', $productId)->first();

                if (! $product) {
                    continue;
                }

                $unitPrice = (string) ($row['price'] ?? $product->retail_price);
                $lineTotal = bcmul($unitPrice, (string) $quantity, 2);

                $subtotal = bcadd($subtotal, $lineTotal, 2);
                $items[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }
        }

        $check = app(PromotionService::class)->quoteLines(
            store: $store,
            code: (string) $data['code'],
            lines: $items,
            customerId: auth()->id(),
        );

        $message = $check['valid']
            ? __('messages.coupon_applied', [
                'name' => $check['promotion']->name,
                'amount' => format_currency((float) $check['discount'], $store),
            ])
            : __('messages.coupon_rejected') . ' ' . __('messages.' . ($check['reason'] ?? 'coupon_not_found'), $check['params'] ?? []);

        return response()->json([
            'valid' => $check['valid'],
            'code' => $check['promotion']?->code,
            'name' => $check['promotion']?->name,
            'discount' => $check['discount'],
            'discount_formatted' => format_currency((float) $check['discount'], $store),
            'message' => $message,
        ]);
    }

    /**
     * Show order confirmation page with pre-formatted Viber & Telegram links.
     */
    public function confirmation(string $store_slug, Order $order, StoreContext $context, Request $request): View
    {
        $store = $context->getStore();

        if ($order->store_id !== $store->id) {
            abort(404, 'Order not found.');
        }

        $order->load(['items', 'items.product']);

        if ($order->user_id) {
            if (!auth()->check() || auth()->id() !== $order->user_id) {
                abort(403, 'Unauthorized access to this order confirmation.');
            }
        } else {
            $token = $request->query('token');
            if (empty($token) || $token !== $order->confirmation_token) {
                abort(404, 'Order not found or invalid confirmation link.');
            }
        }

        // Build formatted items text
        $itemsLines = $order->items->map(function ($item) use ($store) {
            return "- {$item->product_name} x{$item->quantity} (" . format_currency($item->subtotal, $store) . ")";
        })->implode("\n");

        $orderMessage = "မင်္ဂလာပါ။ Order Request (#{$order->order_number})\n"
            . "အမည်: {$order->customer_name}\n"
            . "ဖုန်း: {$order->customer_phone}\n"
            . ($order->contact_identifier ? "ဆက်သွယ်ရန်: {$order->contact_identifier}\n" : '')
            . "လိပ်စာ: {$order->customer_address}\n"
            . "မှာယူသော ပစ္စည်းများ:\n{$itemsLines}\n"
            . "စုစုပေါင်း: " . format_currency($order->total_amount, $store);

        $viberUrl = \App\Support\ContactLinkBuilder::viberChatUrl(
            $store->setting?->viber_number,
            $orderMessage
        );
        $viberIosUrl = \App\Support\ContactLinkBuilder::viberIosContactUrl(
            $store->setting?->viber_number,
            $orderMessage
        );
        $telegramUrl = \App\Support\ContactLinkBuilder::telegramUrl(
            $store->setting?->telegram_username,
            $orderMessage
        );

        return view('storefront.orders.confirmation', compact(
            'store', 'order', 'viberUrl', 'viberIosUrl', 'telegramUrl'
        ));
    }
}
