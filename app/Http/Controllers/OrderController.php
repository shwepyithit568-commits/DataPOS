<?php

namespace App\Http\Controllers;

use App\Models\GlassFinderItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\StoreContext;
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

        abort_unless($store, 404, 'Store not found.');

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

        abort_unless($store, 404, 'Store not found.');

        $validated = $request->validate([
            'items_json' => ['nullable', 'string'],
            'product_id' => ['nullable', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'glass_finder_item_id' => ['nullable', 'exists:glass_finder_items,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'customer_address' => ['required', 'string', 'max:1000'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'contact_channel' => ['required', 'in:viber,telegram,phone'],
            'contact_identifier' => ['nullable', 'string', 'max:100'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $user = auth()->user();

        // Check if user is approved wholesale for active store
        $isWholesaleApproved = $user && (
            $user->isPlatformOwner() ||
            $user->getStoreRole($store->id) === 'wholesale_customer'
        );

        $pricingType = $isWholesaleApproved ? 'wholesale' : 'retail';

        $orderItemsData = [];
        $totalAmount = 0.00;

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

                        if (!$product || !$product->isInStock()) {
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

                        $unitPrice = $variant
                            ? ($isWholesaleApproved ? ($variant->wholesale_price ?? $variant->retail_price) : $variant->retail_price)
                            : ($isWholesaleApproved ? $product->wholesale_price : $product->retail_price);
                        $subtotal = $unitPrice * $qty;
                        $totalAmount += $subtotal;

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

                if (!$product->isInStock()) {
                    return back()->withErrors(['product' => 'Sorry, this product is currently out of stock.']);
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
                $unitPrice = $variant
                    ? ($isWholesaleApproved ? ($variant->wholesale_price ?? $variant->retail_price) : $variant->retail_price)
                    : ($isWholesaleApproved ? $product->wholesale_price : $product->retail_price);
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

            $subtotal = $unitPrice * $qty;
            $totalAmount = $subtotal;

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

        $order = DB::transaction(function () use ($store, $user, $validated, $pricingType, $totalAmount, $orderItemsData) {
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
                'total_amount' => $totalAmount,
                'status' => 'pending_contact',
            ]);

            foreach ($orderItemsData as $itemData) {
                $order->items()->create($itemData);
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
     * Show order confirmation page with pre-formatted Viber & Telegram links.
     */
    public function confirmation(string $store_slug, Order $order, StoreContext $context, Request $request): View
    {
        $store = $context->getStore();

        if ($order->store_id !== $store->id) {
            abort(404, 'Order not found.');
        }

        $order->load(['items']);

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
        $itemsLines = $order->items->map(function ($item) {
            return "- {$item->product_name} x{$item->quantity} (Ks " . number_format($item->subtotal) . ")";
        })->implode("\n");

        $orderMessage = "မင်္ဂလာပါ။ Order Request (#{$order->order_number})\n"
            . "အမည်: {$order->customer_name}\n"
            . "ဖုန်း: {$order->customer_phone}\n"
            . ($order->contact_identifier ? "ဆက်သွယ်ရန်: {$order->contact_identifier}\n" : '')
            . "လိပ်စာ: {$order->customer_address}\n"
            . "မှာယူသော ပစ္စည်းများ:\n{$itemsLines}\n"
            . "စုစုပေါင်း: Ks " . number_format($order->total_amount);

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
