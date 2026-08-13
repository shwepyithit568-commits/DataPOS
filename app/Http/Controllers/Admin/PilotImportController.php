<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportHistory;
use App\Services\CustomerImportService;
use App\Services\ProductImportService;
use App\Services\StoreContext;
use App\Services\SupplierImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * AlinnThit pilot data-import hub (Phase 2.5): one page with tabs for
 * products, customers and suppliers. Each tab follows the proven
 * upload → preview (dry-run, nothing written) → confirm flow and records
 * ImportHistory + downloadable error reports.
 *
 * The active tab is read from $request->route('tab') rather than a method
 * parameter so Laravel's positional primitive binding cannot swap it with
 * the {store_slug} prefix parameter.
 */
class PilotImportController extends Controller
{
    public const TABS = ['products', 'customers', 'suppliers'];

    public function index(Request $request, StoreContext $context): View
    {
        $tab = $this->tabFromRequest($request);
        $store = $context->getStore();

        $histories = ImportHistory::where('store_id', $store->id)
            ->where('type', $tab)
            ->with('user')
            ->latest()
            ->take(10)
            ->get();

        $summary = [
            'total_imports' => ImportHistory::where('store_id', $store->id)->where('type', $tab)->count(),
            'successful_rows' => ImportHistory::where('store_id', $store->id)->where('type', $tab)->sum('success_rows'),
            'failed_rows' => ImportHistory::where('store_id', $store->id)->where('type', $tab)->sum('failed_rows'),
        ];

        return view('admin.pilot_import.index', compact('store', 'tab', 'histories', 'summary'));
    }

    public function import(Request $request, StoreContext $context): RedirectResponse
    {
        $tab = $this->tabFromRequest($request);
        $store = $context->getStore();

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
            'duplicate_strategy' => ['nullable', 'in:skip,update'],
        ]);

        $file = $request->file('file');
        $safeName = $this->safeImportFilename($file->getClientOriginalName());
        $storedPath = $file->storeAs(
            'imports/tmp',
            Str::uuid()->toString() . '-' . $safeName,
            'local'
        );

        try {
            $duplicateStrategy = $validated['duplicate_strategy'] ?? 'skip';
            $fullPath = Storage::disk('local')->path($storedPath);
            $preview = $this->service($tab)->preview($fullPath, $store, $duplicateStrategy);
            $token = Str::random(40);

            session()->put("imports.pilot.{$tab}.{$token}", [
                'path' => $storedPath,
                'filename' => $safeName,
                'duplicate_strategy' => $duplicateStrategy,
            ]);

            return back()->with('import_preview', $preview + [
                'token' => $token,
                'filename' => $safeName,
                'duplicate_strategy' => $duplicateStrategy,
            ]);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            Storage::disk('local')->delete($storedPath);

            return back()->withErrors(['file' => $e->getMessage()]);
        }
    }

    public function confirmImport(Request $request, StoreContext $context): RedirectResponse
    {
        $tab = $this->tabFromRequest($request);
        $store = $context->getStore();

        $validated = $request->validate([
            'token' => ['required', 'string'],
            'duplicate_strategy' => ['required', 'in:skip,update'],
        ]);

        $sessionKey = "imports.pilot.{$tab}.{$validated['token']}";
        $pendingImport = session()->pull($sessionKey);

        if (!$pendingImport || empty($pendingImport['path'])) {
            return back()->withErrors(['file' => 'Import preview expired. Please upload the file again.']);
        }

        $storedPath = $pendingImport['path'];

        try {
            $result = $this->service($tab)->import(
                Storage::disk('local')->path($storedPath),
                $store,
                $request->user(),
                $pendingImport['filename'] ?? "{$tab}-import.csv",
                $validated['duplicate_strategy']
            );

            return back()->with('import_result', $result);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        } finally {
            Storage::disk('local')->delete($storedPath);
        }
    }

    public function downloadTemplate(Request $request, StoreContext $context): StreamedResponse|SymfonyRedirect
    {
        $tab = $this->tabFromRequest($request);
        $storeSlug = $context->getStore()->slug;

        // The full-featured product template already exists on the products
        // import page — reuse it instead of duplicating the generator.
        if ($tab === 'products') {
            return redirect()->route('store.admin.products.import.template', ['store_slug' => $storeSlug]);
        }

        $filename = "{$tab}-import-template.csv";
        $columns = $tab === 'customers'
            ? ['name', 'phone', 'email', 'role']
            : ['name', 'phone', 'email', 'contact_person', 'address', 'notes'];
        $example = $tab === 'customers'
            ? ['Ma Su', '09 123 456 789', 'masu@example.com', 'retail_customer']
            : ['ACDC Mobile', '09 987 654 321', '', 'U Aung', 'No. 45, Maha Bandula Road, Yangon', ''];

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () use ($columns, $example) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($stream, $columns);
            fputcsv($stream, $example);
            fclose($stream);
        }, $filename, $headers);
    }

    private function tabFromRequest(Request $request): string
    {
        return $this->normalizeTab((string) ($request->route('tab') ?? 'products'));
    }

    private function normalizeTab(string $tab): string
    {
        $tab = strtolower(trim($tab));
        if (!in_array($tab, self::TABS, true)) {
            abort(404, 'Unknown import tab.');
        }

        return $tab;
    }

    private function service(string $tab): ProductImportService|CustomerImportService|SupplierImportService
    {
        return match ($tab) {
            'products' => app(ProductImportService::class),
            'customers' => app(CustomerImportService::class),
            'suppliers' => app(SupplierImportService::class),
        };
    }

    private function safeImportFilename(string $original): string
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($original)) ?? 'import.csv';

        return mb_substr($safe, 0, 120);
    }
}
