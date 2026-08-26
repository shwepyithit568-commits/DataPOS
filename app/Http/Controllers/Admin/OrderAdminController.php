<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use App\POS\Exceptions\InventoryException;
use App\POS\Integrations\OrderInventoryAdapter;
use App\Services\StoreContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderAdminController extends Controller
{
    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $storeRouteParams = ['store_slug' => $store->slug];
        $tab = $request->query('tab', 'all');

        $query = $this->filteredQuery($request, $store);

        // Tab filter
        if ($tab === 'pending') {
            $query->where('status', 'pending_contact');
        } elseif ($tab === 'confirmed') {
            $query->where('status', 'confirmed');
        } elseif ($tab === 'delivered') {
            $query->where('status', 'delivered');
        } elseif ($tab === 'cancelled') {
            $query->where('status', 'cancelled');
        }

        // Sorting
        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest'        => $query->oldest('created_at'),
            'amount_high'   => $query->orderByRaw('COALESCE(agreed_amount, total_amount) DESC'),
            'amount_low'    => $query->orderByRaw('COALESCE(agreed_amount, total_amount) ASC'),
            default         => $query->latest('created_at'),
        };

        $perPage = request('per_page') === 'all' ? 1000 : (int) request('per_page', 25);
        $orders = $query->paginate($perPage)->withQueryString();
        $totalCount = $orders->total();

        // Summary KPI stats for store
        $storeOrders = Order::where('store_id', $store->id);
        $totalOrdersCount = (clone $storeOrders)->count();
        $pendingCount = (clone $storeOrders)->where('status', 'pending_contact')->count();
        $confirmedCount = (clone $storeOrders)->where('status', 'confirmed')->count();
        $deliveredCount = (clone $storeOrders)->where('status', 'delivered')->count();
        $cancelledCount = (clone $storeOrders)->where('status', 'cancelled')->count();

        $revenue = (float) (clone $storeOrders)
            ->whereIn('status', ['confirmed', 'delivered'])
            ->selectRaw('SUM(COALESCE(agreed_amount, total_amount)) as rev')
            ->value('rev') ?? 0.0;

        $pendingRevenue = (float) (clone $storeOrders)
            ->where('status', 'pending_contact')
            ->selectRaw('SUM(COALESCE(agreed_amount, total_amount)) as rev')
            ->value('rev') ?? 0.0;

        $stats = [
            'total'          => $totalOrdersCount,
            'pending'        => $pendingCount,
            'confirmed'      => $confirmedCount,
            'delivered'      => $deliveredCount,
            'cancelled'      => $cancelledCount,
            'revenue'        => $revenue,
            'pendingRevenue' => $pendingRevenue,
        ];

        return view('admin.orders.index', compact(
            'store',
            'storeRouteParams',
            'orders',
            'totalCount',
            'stats',
            'tab',
            'sort'
        ));
    }

    public function show(string $store_slug, Order $order, StoreContext $context): View
    {
        $store = $context->getStore();
        if (! $store || $order->store_id !== $store->id) {
            abort(403, 'Unauthorized store order access.');
        }

        $storeRouteParams = ['store_slug' => $store->slug];
        $order->load(['items', 'user']);

        return view('admin.orders.show', compact('store', 'storeRouteParams', 'order'));
    }

    /**
     * Printable invoice.
     */
    public function invoice(string $store_slug, Order $order, StoreContext $context): View
    {
        $store = $context->getStore();
        if (! $store || $order->store_id !== $store->id) {
            abort(403, 'Unauthorized store order access.');
        }

        $storeRouteParams = ['store_slug' => $store->slug];
        $order->load(['items', 'user']);
        $setting = $store->setting;

        return view('admin.orders.invoice', compact('store', 'storeRouteParams', 'order', 'setting'));
    }

    public function updateStatus(Request $request, string $store_slug, Order $order, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        if (! $store || $order->store_id !== $store->id) {
            abort(403, 'Unauthorized store order update.');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:pending_contact,confirmed,delivered,cancelled'],
        ]);

        $fromStatus = $order->status;

        try {
            app(OrderInventoryAdapter::class)->handleStatusChange($order, $fromStatus, $validated['status']);
        } catch (InventoryException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        $order->update(['status' => $validated['status']]);

        app(\App\Support\AdminPushNotifier::class)->dispatch(
            $store,
            'order-status.' . $order->id . '.' . $validated['status'],
            new \App\Notifications\OrderStatusNotification($order, $validated['status']),
        );

        $statusLabels = [
            'pending_contact' => 'Pending Contact',
            'confirmed'       => 'Confirmed (အတည်ပြုပြီး)',
            'delivered'       => 'Delivered (ပို့ဆောင်ပြီး)',
            'cancelled'       => 'Cancelled (ပယ်ဖျက်ပြီး)',
        ];

        return back()->with('success', 'Order status updated to: ' . ($statusLabels[$validated['status']] ?? $validated['status']));
    }

    /**
     * Record the final agreed price and whether paid.
     */
    public function updateFinances(Request $request, string $store_slug, Order $order, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        if (! $store || $order->store_id !== $store->id) {
            abort(403, 'Unauthorized store order update.');
        }

        $validated = $request->validate([
            'agreed_amount'  => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'payment_status' => ['required', 'in:unpaid,paid'],
        ]);

        $agreedAmount = ($validated['agreed_amount'] ?? '') === '' ? null : $validated['agreed_amount'];

        $order->update([
            'agreed_amount'  => $agreedAmount,
            'payment_status' => $validated['payment_status'],
        ]);

        if ($validated['payment_status'] === 'paid') {
            app(\App\Support\AdminPushNotifier::class)->dispatch(
                $store,
                'order-payment.' . $order->id . '.paid',
                new \App\Notifications\PaymentReceivedNotification($order),
            );
        }

        return back()->with('success', 'Order payment & agreed amount updated.');
    }

    /**
     * Save an internal admin note on the order.
     */
    public function updateNote(Request $request, string $store_slug, Order $order, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        if (! $store || $order->store_id !== $store->id) {
            abort(403, 'Unauthorized store order update.');
        }

        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $note = trim((string) ($validated['admin_note'] ?? ''));
        $order->update(['admin_note' => $note === '' ? null : $note]);

        return back()->with('success', 'Admin note saved successfully.');
    }

    /**
     * Delete an order — owner (store_manager) only.
     */
    public function destroy(string $store_slug, Order $order, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        if (! $store || $order->store_id !== $store->id) {
            abort(403, 'Unauthorized store order access.');
        }

        $user = request()->user();
        if (!$user->isPlatformOwner() && !$user->hasStoreRole($store->id, ['store_manager'])) {
            abort(403, 'Only the store owner can delete orders.');
        }

        $orderNumber = $order->order_number;
        $order->items()->delete();
        $order->delete();

        return back()->with('success', "Order #{$orderNumber} has been deleted.");
    }

    /**
     * Stream the current (filtered) order list as an Excel-friendly CSV.
     */
    public function export(Request $request, StoreContext $context): StreamedResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $orders = $this->filteredQuery($request, $store)->latest('created_at')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="orders-' . $store->slug . '-' . now()->format('Ymd-His') . '.csv"',
        ];

        return response()->streamDownload(function () use ($orders, $store) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");

            fputcsv($stream, ['Order Requests Report', $store->name]);
            fputcsv($stream, ['Export Date', now()->toFormattedDateString() . ' ' . now()->format('h:i A')]);
            fputcsv($stream, []);

            fputcsv($stream, [
                'Order No', 'Date', 'Customer Name', 'Phone', 'Contact Channel',
                'Pricing Type', 'Status', 'Payment Status', 'Total (Ks)', 'Agreed Amount (Ks)', 'Items Summary',
            ]);

            foreach ($orders as $order) {
                $items = $order->items
                    ->map(fn ($item) => $item->product_name . ' ×' . $item->quantity)
                    ->implode('; ');

                fputcsv($stream, [
                    $order->order_number,
                    $order->created_at->format('Y-m-d H:i'),
                    $order->customer_name,
                    $order->customer_phone,
                    strtoupper($order->contact_channel),
                    ucfirst($order->pricing_type),
                    $order->payment_status,
                    number_format((float) $order->total_amount, 0),
                    $order->agreed_amount !== null ? number_format((float) $order->agreed_amount, 0) : '',
                    $items,
                ]);
            }

            fclose($stream);
        }, 'orders-' . $store->slug . '-' . now()->format('Ymd-His') . '.csv', $headers);
    }

    private function filteredQuery(Request $request, Store $store): Builder
    {
        $query = Order::where('store_id', $store->id)->with(['items', 'user']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', '%' . $search . '%')
                  ->orWhere('customer_name', 'like', '%' . $search . '%')
                  ->orWhere('customer_phone', 'like', '%' . $search . '%')
                  ->orWhere('contact_identifier', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status') && in_array($request->status, ['pending_contact', 'confirmed', 'delivered', 'cancelled'], true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('pricing_type') && in_array($request->pricing_type, ['retail', 'wholesale'], true)) {
            $query->where('pricing_type', $request->pricing_type);
        }

        if ($request->filled('contact_channel') && in_array($request->contact_channel, ['viber', 'telegram', 'phone'], true)) {
            $query->where('contact_channel', $request->contact_channel);
        }

        return $query;
    }
}
