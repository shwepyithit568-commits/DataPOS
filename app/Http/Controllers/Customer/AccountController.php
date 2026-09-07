<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\GlassFavorite;
use App\Models\Order;
use App\Services\StoreContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(StoreContext $context): View
    {
        $user = auth()->user();
        $store = $context->getStore();

        $storeRole = $store ? $user->getStoreRole($store->id) : null;
        $isWholesaleApproved = $user->isPlatformOwner() || $storeRole === 'wholesale_customer';

        $wholesaleApplication = ($store && $user)
            ? \App\Models\WholesaleApplication::where('store_id', $store->id)
                ->where('user_id', $user->id)
                ->first()
            : null;

        if ($wholesaleApplication && $wholesaleApplication->status === 'approved') {
            $isWholesaleApproved = true;
        }

        $wholesaleUrl = $store
            ? route('store.wholesale.apply', ['store_slug' => $store->slug])
            : url('/wholesale/apply');

        $ordersCount = Order::where('user_id', $user->id)
            ->when($store, fn ($q) => $q->where('store_id', $store->id))
            ->count();

        $favoritesCount = GlassFavorite::where('user_id', $user->id)->count();

        // Admin entry: store-scoped dashboard when a store is in context, otherwise the
        // global admin dashboard (renders the store picker for a platform owner).
        $canAccessAdmin = $user->isPlatformOwner()
            || ($store && $user->hasStoreRole($store->id, ['store_manager', 'staff']));
        $adminUrl = $canAccessAdmin
            ? ($store ? route('store.admin.dashboard', ['store_slug' => $store->slug]) : route('admin.dashboard'))
            : null;

        return view('customer.account.index', compact(
            'user',
            'store',
            'isWholesaleApproved',
            'adminUrl',
            'wholesaleApplication',
            'wholesaleUrl',
            'ordersCount',
            'favoritesCount'
        ));
    }

    public function orders(Request $request, StoreContext $context): View
    {
        $user = auth()->user();
        $store = $context->getStore();

        // Auto-claim any unlinked orders created with the user's phone number
        if (!empty($user->phone)) {
            Order::whereNull('user_id')
                ->where('customer_phone', $user->phone)
                ->update(['user_id' => $user->id]);
        }

        $status = (string) $request->query('status', 'all');
        $search = trim((string) $request->query('q', ''));

        $query = Order::where('user_id', $user->id)
            ->with(['items.product', 'items.variant']);

        if ($store) {
            $query->where('store_id', $store->id);
        }

        if (in_array($status, ['pending_contact', 'confirmed', 'delivered', 'cancelled'], true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($itemQ) use ($search) {
                        $itemQ->where('product_name', 'like', "%{$search}%")
                            ->orWhere('variant_sku', 'like', "%{$search}%");
                    });
            });
        }

        // Live status counts for tab badges
        $baseCountQuery = Order::where('user_id', $user->id)
            ->when($store, fn ($q) => $q->where('store_id', $store->id));

        $statusCounts = [
            'all' => (clone $baseCountQuery)->count(),
            'pending_contact' => (clone $baseCountQuery)->where('status', 'pending_contact')->count(),
            'confirmed' => (clone $baseCountQuery)->where('status', 'confirmed')->count(),
            'delivered' => (clone $baseCountQuery)->where('status', 'delivered')->count(),
            'cancelled' => (clone $baseCountQuery)->where('status', 'cancelled')->count(),
        ];

        $orders = $query->latest('id')
            ->paginate(10)
            ->withQueryString();

        $accountUrl = $store
            ? url('/account?store_slug=' . $store->slug)
            : url('/account');

        $orderBuilderUrl = $store
            ? url('/order-builder?store_slug=' . $store->slug)
            : url('/order-builder');

        return view('customer.account.orders', compact(
            'user',
            'store',
            'orders',
            'status',
            'search',
            'statusCounts',
            'accountUrl',
            'orderBuilderUrl'
        ));
    }

    public function showOrder(Order $order, StoreContext $context): View
    {
        $user = auth()->user();
        $store = $context->getStore();

        // Auto-claim order if it matches the user's registered phone
        if ($order->user_id !== $user->id && !empty($user->phone) && $order->customer_phone === $user->phone) {
            $order->update(['user_id' => $user->id]);
        }

        // Security check: User A cannot view User B orders
        if ($order->user_id !== $user->id) {
            abort(403, 'Unauthorized order access.');
        }

        if ($store && $order->store_id !== $store->id) {
            abort(404, 'Order not found for this store.');
        }

        $order->load(['items.product', 'items.variant']);

        $storeSlug = $store?->slug ?? request('store_slug');
        $ordersUrl = $storeSlug
            ? url('/account/orders?store_slug=' . $storeSlug)
            : url('/account/orders');

        $orderBuilderUrl = $storeSlug
            ? url('/order-builder?store_slug=' . $storeSlug)
            : url('/order-builder');

        return view('customer.account.order_show', compact(
            'user',
            'store',
            'order',
            'ordersUrl',
            'orderBuilderUrl'
        ));
    }

    public function favorites(StoreContext $context): View
    {
        $user = auth()->user();
        $store = $context->getStore();

        $favorites = GlassFavorite::where('user_id', $user->id)
            ->with(['glassItem'])
            ->latest()
            ->paginate(10);

        return view('customer.account.favorites', compact('user', 'store', 'favorites'));
    }
}
