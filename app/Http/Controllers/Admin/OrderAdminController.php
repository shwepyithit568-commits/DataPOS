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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderAdminController extends Controller
{
    /** Shared export columns for both CSV and XLSX order exports. */
    /**
     * Localized export column headers, shared by CSV & XLSX.
     *
     * @return array<int, string>
     */
    private function orderExportHeaders(): array
    {
        return [
            __('messages.order_number'),
            __('messages.order_date'),
            __('messages.customer_name'),
            __('messages.phone'),
            __('messages.contact_channel'),
            __('messages.pricing_type'),
            __('messages.status'),
            __('messages.payment_status'),
            __('messages.total_amount'),
            __('messages.agreed_amount'),
            __('messages.items_summary'),
        ];
    }

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
        $order->load(['items', 'items.product', 'user']);

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
        $order->load(['items', 'items.product', 'user']);
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
     * Export the current (filtered) order list as an Excel-friendly CSV or XLSX.
     */
    public function export(Request $request, StoreContext $context): StreamedResponse|BinaryFileResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $orders = $this->filteredQuery($request, $store)->latest('created_at')->get();
        $format = strtolower((string) $request->input('format', 'csv'));

        if ($format === 'xlsx' || $format === 'excel') {
            return $this->exportXlsx($store, $orders);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="orders-' . $store->slug . '-' . now()->format('Ymd-His') . '.csv"',
        ];

        return response()->streamDownload(function () use ($orders, $store) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");

            fputcsv($stream, [__('messages.order_requests_report'), $store->name]);
            fputcsv($stream, [__('messages.export_date'), now()->toFormattedDateString() . ' ' . now()->format('h:i A')]);
            fputcsv($stream, []);

            fputcsv($stream, $this->orderExportHeaders());

            foreach ($orders as $order) {
                fputcsv($stream, $this->orderExportRow($order));
            }

            fclose($stream);
        }, 'orders-' . $store->slug . '-' . now()->format('Ymd-His') . '.csv', $headers);
    }

    /**
     * Export the current (filtered) order list as a formatted XLSX workbook.
     */
    private function exportXlsx(Store $store, \Illuminate\Database\Eloquent\Collection $orders): BinaryFileResponse
    {
        $filename = 'orders-' . $store->slug . '-' . now()->format('Ymd-His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_orders_');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Orders');

        $sheet->setCellValue('A1', __('messages.order_requests_report'));
        $sheet->setCellValue('A2', $store->name);
        $sheet->setCellValue('A3', __('messages.export_date') . ': ' . now()->toFormattedDateString() . ' ' . now()->format('h:i A'));

        $sheet->fromArray([$this->orderExportHeaders()], null, 'A5');

        $highestCol = $sheet->getHighestColumn();
        $sheet->getStyle("A5:{$highestCol}5")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
            'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(5)->setRowHeight(24);
        $sheet->freezePane('A6');

        $rowIndex = 6;
        foreach ($orders as $order) {
            $sheet->fromArray([$this->orderExportRow($order)], null, "A{$rowIndex}");
            $sheet->getRowDimension($rowIndex)->setRowHeight(18);
            $rowIndex++;
        }

        // Auto-size all columns
        foreach (range('A', $highestCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        $spreadsheet->disconnectWorksheets();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Build a single order row for export, shared by CSV & XLSX.
     * Items summary includes service duration / digital delivery method.
     *
     * @return array<int, scalar|null>
     */
    private function orderExportRow(Order $order): array
    {
        $items = $order->items
            ->map(function ($item) {
                $line = $item->product_name . ' ×' . $item->quantity;
                if ($item->product && $item->product->product_type === 'service' && trim((string) $item->product->service_duration)) {
                    $line .= ' (' . __('messages.product_form_service_duration') . ': ' . $item->product->service_duration . ') ';
                } else if ($item->product && $item->product->product_type === 'digital' && trim((string) $item->product->digital_delivery_method)) {
                    $line .= ' (' . __('messages.product_form_digital_delivery_method') . ': ' . $item->product->digital_delivery_method . ')';
                }
                return $line;
            })
            ->implode('; ');

        return [
            $order->order_number,
            $order->created_at->format('Y-m-d H:i'),
            $order->customer_name,
            $order->customer_phone,
            match ($order->contact_channel) {
                'viber' => __('messages.contact_channel_viber'),
                'telegram' => __('messages.contact_channel_telegram'),
                'phone' => __('messages.phone'),
                default => ucfirst((string) $order->contact_channel),
            },
            $order->pricing_type === 'wholesale' ? __('messages.wholesale') : __('messages.retail'),
            match ($order->status) {
                'pending_contact' => __('messages.order_status_pending_contact'),
                'confirmed' => __('messages.order_status_confirm'),
                'delivered' => __('messages.order_status_delivered'),
                'cancelled' => __('messages.order_status_cancel'),
                default => ucfirst((string) $order->status),
            },
            $order->payment_status === 'paid' ? __('messages.order_status_paid') : __('messages.order_status_unpaid'),
            number_format((float) $order->total_amount, 0),
            $order->agreed_amount !== null ? number_format((float) $order->agreed_amount, 0) : '',
            $items,
        ];
    }

    private function filteredQuery(Request $request, Store $store): Builder
    {
        $query = Order::where('store_id', $store->id)->with(['items', 'items.product', 'user']);

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
