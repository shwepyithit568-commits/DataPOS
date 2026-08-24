<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Printer;
use App\POS\Services\PrinterService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrinterController extends Controller
{
    public function __construct(
        protected PrinterService $printerService
    ) {
    }

    /**
     * Display the Printer configuration dashboard.
     */
    public function index(StoreContext $context): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $printers = $this->printerService->getPrinters($store);
        $stats = $this->printerService->getStatistics($store);

        return view('admin.printers.index', compact('store', 'printers', 'stats'));
    }

    /**
     * Show form to add a new printer.
     */
    public function create(StoreContext $context): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $printer = new Printer([
            'paper_width' => '80mm',
            'connection_type' => 'browser',
            'printer_role' => 'receipt',
            'port' => 9100,
            'print_copies' => 1,
            'auto_cut' => true,
            'cash_drawer_kick' => true,
            'beep_on_print' => false,
            'print_logo' => true,
            'feed_lines' => 2,
            'is_active' => true,
            'is_default' => false,
        ]);

        return view('admin.printers.form', compact('store', 'printer'));
    }

    /**
     * Store a new printer.
     */
    public function store(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'connection_type' => 'required|in:browser,network,usb,bluetooth',
            'paper_width' => 'required|in:80mm,58mm',
            'ip_address' => 'nullable|required_if:connection_type,network|string|max:60',
            'port' => 'nullable|integer|min:1|max:65535',
            'device_path' => 'nullable|string|max:255',
            'printer_role' => 'required|in:receipt,kitchen,service,label',
            'print_copies' => 'nullable|integer|min:1|max:5',
            'auto_cut' => 'nullable|boolean',
            'cash_drawer_kick' => 'nullable|boolean',
            'beep_on_print' => 'nullable|boolean',
            'print_logo' => 'nullable|boolean',
            'feed_lines' => 'nullable|integer|min:0|max:10',
            'header_text' => 'nullable|string|max:500',
            'footer_text' => 'nullable|string|max:500',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $this->printerService->savePrinter($store, $validated, null, $request->user());

        return redirect()->route('store.admin.printers.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.printers_created_success'));
    }

    /**
     * Show form to edit an existing printer.
     */
    public function edit(StoreContext $context, string $store_slug, int|string $printer): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $printer = Printer::where('store_id', $store->id)->findOrFail($printer);

        return view('admin.printers.form', compact('store', 'printer'));
    }

    /**
     * Update an existing printer.
     */
    public function update(StoreContext $context, string $store_slug, int|string $printer, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $printer = Printer::where('store_id', $store->id)->findOrFail($printer);

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'connection_type' => 'required|in:browser,network,usb,bluetooth',
            'paper_width' => 'required|in:80mm,58mm',
            'ip_address' => 'nullable|required_if:connection_type,network|string|max:60',
            'port' => 'nullable|integer|min:1|max:65535',
            'device_path' => 'nullable|string|max:255',
            'printer_role' => 'required|in:receipt,kitchen,service,label',
            'print_copies' => 'nullable|integer|min:1|max:5',
            'auto_cut' => 'nullable|boolean',
            'cash_drawer_kick' => 'nullable|boolean',
            'beep_on_print' => 'nullable|boolean',
            'print_logo' => 'nullable|boolean',
            'feed_lines' => 'nullable|integer|min:0|max:10',
            'header_text' => 'nullable|string|max:500',
            'footer_text' => 'nullable|string|max:500',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $this->printerService->savePrinter($store, $validated, $printer, $request->user());

        return redirect()->route('store.admin.printers.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.printers_updated_success'));
    }

    /**
     * Delete a printer.
     */
    public function destroy(StoreContext $context, string $store_slug, int|string $printer, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $printer = Printer::where('store_id', $store->id)->findOrFail($printer);
        $this->printerService->deletePrinter($store, $printer, $request->user());

        return redirect()->route('store.admin.printers.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.printers_deleted_success'));
    }

    /**
     * Set a printer as default.
     */
    public function setDefault(StoreContext $context, string $store_slug, int|string $printer, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $printer = Printer::where('store_id', $store->id)->findOrFail($printer);
        $this->printerService->setDefault($store, $printer, $request->user());

        return redirect()->route('store.admin.printers.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.printers_set_default_success'));
    }

    /**
     * Render live thermal test receipt.
     */
    public function testPrint(StoreContext $context, string $store_slug, int|string $printer): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $printer = Printer::where('store_id', $store->id)->findOrFail($printer);

        return view('admin.printers.test_print', compact('store', 'printer'));
    }
}
