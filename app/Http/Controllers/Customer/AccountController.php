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

        // Admin entry: store-scoped dashboard when a store is in context, otherwise the
        // global admin dashboard (renders the store picker for a platform owner).
        $canAccessAdmin = $user->isPlatformOwner()
            || ($store && $user->hasStoreRole($store->id, ['store_manager', 'staff']));
        $adminUrl = $canAccessAdmin
            ? ($store ? route('store.admin.dashboard', ['store_slug' => $store->slug]) : route('admin.dashboard'))
            : null;

        return view('customer.account.index', compact('user', 'store', 'isWholesaleApproved', 'adminUrl'));
    }

    public function orders(StoreContext $context): View
    {
        $user = auth()->user();
        $store = $context->getStore();

        $query = Order::where('user_id', $user->id)
            ->with(['items']);

        if ($store) {
            $query->where('store_id', $store->id);
        }

        $orders = $query->latest()
            ->paginate(10);

        return view('customer.account.orders', compact('user', 'store', 'orders'));
    }

    public function showOrder(Order $order, StoreContext $context): View
    {
        $user = auth()->user();
        $store = $context->getStore();

        // Security check: User A cannot view User B orders
        if ($order->user_id !== $user->id) {
            abort(403, 'Unauthorized order access.');
        }

        if ($store && $order->store_id !== $store->id) {
            abort(404, 'Order not found for this store.');
        }

        $order->load(['items']);

        return view('customer.account.order_show', compact('user', 'store', 'order'));
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
