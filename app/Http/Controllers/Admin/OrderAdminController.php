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

        $query = $this->filteredQuery($request, $store);

        // Sorting
        $sort = $request->get('sort', 'newest');
        match ($sort) {
            'oldest'        => $query->oldest(),
            'amount_high'   => $query->orderBy('total_amount', 'desc'),
            'amount_low'    => $query->orderBy('total_amount', 'asc'),
            default         => $query->latest(),
        };

        $perPage = request('per_page') === 'all' ? 100000 : (int) request('per_page', 25);
        $orders = $query->paginate($perPage)->withQueryString();
        $totalCount = $orders->total();

        // Summary stats — rebuild base filtered query to avoid clone-after-paginate issues
        $baseFiltered = fn () => $this->filteredQuery($request, $store);

        $stats = [
            'total'     => $totalCount,
            'pending'   => $baseFiltered()->where('status', 'pending_contact')->count(),
            'confirmed' => $baseFiltered()->where('status', 'confirmed')->count(),
            'delivered' => $baseFiltered()->where('status', 'delivered')->count(),
            'cancelled'      => $baseFiltered()->where('status', 'cancelled')->count(),
            // Revenue = confirmed + delivered (delivered orders were confirmed
            // before, so excluding them would drop revenue on status change).
            'revenue'        => $this->revenueSum($baseFiltered()->whereIn('status', ['confirmed', 'delivered'])),
            'pendingRevenue' => $this->revenueSum($baseFiltered()->where('status', 'pending_contact')),
        ];

        return view('admin.orders.index', compact('store', 'orders', 'totalCount', 'stats'));
    }

    public function show(string $store_slug, Order $order, StoreContext $context): View
    {
        $store = $context->getStore();

        if ($order->store_id !== $store->id) {
            abort(403, 'Unauthorized store order access.');
        }

        $order->load(['items', 'user']);

        return view('admin.orders.show', compact('store', 'order'));
    }

    /**
     * Printable invoice — a standalone page (no admin chrome) meant to be
     * printed to PDF / shared with the customer.
     */
    public function invoice(string $store_slug, Order $order, StoreContext $context): View
    {
        $store = $context->getStore();

        if ($order->store_id !== $store->id) {
            abort(403, 'Unauthorized store order access.');
        }

        $order->load(['items', 'user']);
        $setting = $store->setting;

        return view('admin.orders.invoice', compact('store', 'order', 'setting'));
    }

    public function updateStatus(Request $request, string $store_slug, Order $order, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($order->store_id !== $store->id) {
            abort(403, 'Unauthorized store order update.');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:pending_contact,confirmed,delivered,cancelled'],
        ]);

        $fromStatus = $order->status;

        // Inventory ledger integration (SoT §5 / target-design §2.5): reserve /
        // commit / release stock BEFORE the status persists — a failed
        // reservation blocks the transition so POS and online orders can never
        // oversell the same stock.
        try {
            app(OrderInventoryAdapter::class)->handleStatusChange($order, $fromStatus, $validated['status']);
        } catch (InventoryException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        $order->update(['status' => $validated['status']]);

        // Notify the team that the order progressed (deduped per status value,
        // queued, best-effort — a push failure must never fail the update).
        app(\App\Support\AdminPushNotifier::class)->dispatch(
            $store,
            'order-status.' . $order->id . '.' . $validated['status'],
            new \App\Notifications\OrderStatusNotification($order, $validated['status']),
        );

        return back()->with('success', 'Order status updated to ' . $validated['status']);
    }

    /**
     * Record the final agreed price (negotiated over the phone/Viber/Telegram)
     * and whether the customer has paid. Glass-finder orders carry a Ks 0
     * total until the owner sets the agreed amount here.
     */
    public function updateFinances(Request $request, string $store_slug, Order $order, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($order->store_id !== $store->id) {
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

        // Notify the team when an order is marked paid (deduped, queued,
        // best-effort). Unpaid marking does not notify.
        if ($validated['payment_status'] === 'paid') {
            app(\App\Support\AdminPushNotifier::class)->dispatch(
                $store,
                'order-payment.' . $order->id . '.paid',
                new \App\Notifications\PaymentReceivedNotification($order),
            );
        }

        return back()->with('success', 'Order payment details updated.');
    }

    /**
     * Save an internal admin note on the order. This is a private remark
     * (delivery instructions, follow-up reminders, negotiated details) —
     * customers never see it; their own note stays in customer_note.
     */
    public function updateNote(Request $request, string $store_slug, Order $order, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($order->store_id !== $store->id) {
            abort(403, 'Unauthorized store order update.');
        }

        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $note = trim((string) ($validated['admin_note'] ?? ''));

        $order->update(['admin_note' => $note === '' ? null : $note]);

        return back()->with('success', 'Admin note saved.');
    }

    /**
     * Delete an order — owner (store_manager) only.
     * Cascades to order items via the model relationship.
     */
    public function destroy(string $store_slug, Order $order, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($order->store_id !== $store->id) {
            abort(403, 'Unauthorized store order access.');
        }

        // Only store_manager (owner) can delete orders
        $user = request()->user();
        if (!$user->isPlatformOwner() && !$user->hasStoreRole($store->id, ['store_manager'])) {
            abort(403, 'Only the store owner can delete orders.');
        }

        $orderNumber = $order->order_number;
        $order->items()->delete();
        $order->delete();

        return back()->with('success', "Order {$orderNumber} has been deleted.");
    }

    /**
     * Stream the current (filtered) order list as an Excel-friendly CSV.
     */
    public function export(Request $request, StoreContext $context): StreamedResponse
    {
        $store = $context->getStore();

        $orders = $this->filteredQuery($request, $store)->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="orders-' . now()->format('Ymd-His') . '.csv"',
        ];

        return response()->streamDownload(function () use ($orders) {
            $stream = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens the file with correct encoding
            fwrite($stream, "\xEF\xBB\xBF");

            fputcsv($stream, [
                'Order No', 'Date', 'Customer Name', 'Phone', 'Contact Channel',
                'Pricing Type', 'Status', 'Payment Status', 'Total (Ks)', 'Agreed Amount (Ks)', 'Items',
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
                    $order->contact_channel,
                    $order->pricing_type,
                    $order->status,
                    $order->payment_status,
                    number_format((float) $order->total_amount),
                    $order->agreed_amount !== null ? number_format((float) $order->agreed_amount) : '',
                    $items,
                ]);
            }

            fclose($stream);
        }, 'orders-' . now()->format('Ymd-His') . '.csv', $headers);
    }

    /**
     * Shared search/filter builder so the index, stats and CSV export all
     * honour the exact same filters.
     */
    private function filteredQuery(Request $request, Store $store): Builder
    {
        $query = Order::where('store_id', $store->id)->with(['items', 'user']);

        // Search: order number, customer name, or customer phone
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', '%' . $search . '%')
                  ->orWhere('customer_name', 'like', '%' . $search . '%')
                  ->orWhere('customer_phone', 'like', '%' . $search . '%')
                  ->orWhere('contact_identifier', 'like', '%' . $search . '%');
            });
        }

        // Filter: status
        if ($request->filled('status') && in_array($request->status, ['pending_contact', 'confirmed', 'delivered', 'cancelled'])) {
            $query->where('status', $request->status);
        }

        // Filter: pricing type
        if ($request->filled('pricing_type') && in_array($request->pricing_type, ['retail', 'wholesale'])) {
            $query->where('pricing_type', $request->pricing_type);
        }

        // Filter: contact channel
        if ($request->filled('contact_channel') && in_array($request->contact_channel, ['viber', 'telegram', 'phone'])) {
            $query->where('contact_channel', $request->contact_channel);
        }

        return $query;
    }

    /**
     * Sum of confirmed-order revenue, using the admin-confirmed agreed
     * amount where set (glass orders) and the line-item total otherwise.
     */
    private function revenueSum(Builder $query): float
    {
        $row = $query->selectRaw(
            'SUM(COALESCE(agreed_amount, total_amount)) as revenue'
        )->first();

        return (float) ($row->revenue ?? 0);
    }
}
