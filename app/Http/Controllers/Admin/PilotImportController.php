<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ImportHistory;
use App\Services\CustomerImportService;
use App\Services\DebtOpeningImportService;
use App\Services\DemoBusinessScenarioService;
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
 * AlinnThit pilot data-import hub: one page with tabs for
 * products, customers, suppliers, debt opening balances, and sample demo scenarios.
 * Each tab follows the proven upload → preview (dry-run, nothing written) → confirm flow
 * and records ImportHistory + downloadable error reports.
 */
class PilotImportController extends Controller
{
    public const TABS = ['products', 'customers', 'suppliers', 'debt', 'scenarios'];

    public function index(Request $request, StoreContext $context): View
    {
        $tab = $this->tabFromRequest($request);
        $store = $context->getStore();

        $stats = [
            'products' => \App\Models\Product::where('store_id', $store->id)->count(),
            'categories' => \App\Models\Category::where('store_id', $store->id)->count(),
            'brands' => \App\Models\Brand::where('store_id', $store->id)->count(),
            'suppliers' => \App\Models\Supplier::where('store_id', $store->id)->count(),
            'customers' => \App\Models\User::whereHas('stores', fn ($q) => $q->where('store_id', $store->id))->count(),
        ];

        $histories = ImportHistory::where('store_id', $store->id)
            ->with('user')
            ->latest()
            ->take(10)
            ->get();

        $summary = [
            'total_imports' => ImportHistory::where('store_id', $store->id)->count(),
            'successful_rows' => ImportHistory::where('store_id', $store->id)->sum('success_rows'),
            'failed_rows' => ImportHistory::where('store_id', $store->id)->sum('failed_rows'),
        ];

        $demoScenarios = app(DemoBusinessScenarioService::class)->scenarios();
        $demoScenariosEnabled = app()->environment(['local', 'testing', 'uat']) && (bool) config('app.show_quick_login');

        return view('admin.pilot_import.index', compact('store', 'tab', 'stats', 'histories', 'summary', 'demoScenarios', 'demoScenariosEnabled'));
    }

    public function seedStore(
        Request $request,
        StoreContext $context,
        DemoBusinessScenarioService $demoScenarios
    ): RedirectResponse {
        $store = $context->getStore();
        $validated = $request->validate([
            'scenario' => ['required', 'string'],
            'clean_old' => ['nullable', 'boolean'],
            'apply_store_identity' => ['nullable', 'boolean'],
        ]);

        $cleanOld = (bool) ($validated['clean_old'] ?? false);
        $applyStoreIdentity = (bool) ($validated['apply_store_identity'] ?? false);

        try {
            $result = $demoScenarios->seedIntoStore(
                $store,
                $validated['scenario'],
                $request->user(),
                $cleanOld,
                $applyStoreIdentity
            );

            AuditLog::write(
                $store->id,
                'pilot_demo_data_seeded',
                'store',
                $store->id,
                [
                    'scenario' => $validated['scenario'],
                    'clean_old' => $cleanOld,
                    'apply_store_identity' => $applyStoreIdentity,
                    'products' => $result['products'],
                    'featured_products' => $result['featured_products'],
                    'timed_promotions' => $result['timed_promotions'],
                    'assets' => $result['assets'],
                    'asset_warning' => $result['asset_warning'],
                ],
                auth()->id(),
                request()->ip()
            );

            return redirect()
                ->route('store.admin.pilot-import.index', ['store_slug' => $store->slug, 'tab' => 'scenarios'])
                ->with('success', "နမူနာဒေတာများ အောင်မြင်စွာ ထည့်သွင်းပြီးပါပြီ: ကုန်ပစ္စည်း {$result['products']} မျိုး၊ Featured {$result['featured_products']} မျိုး၊ Promotion {$result['timed_promotions']} မျိုး၊ Product image {$result['assets']['products']} ပုံ၊ Category image {$result['assets']['categories']} ပုံနှင့် Banner {$result['assets']['banners']} ပုံ ထည့်သွင်းပြီးပါပြီ။" . ($result['asset_warning'] ? ' Image generation warning: ' . $result['asset_warning'] : ''));
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['demo_scenario' => $e->getMessage()]);
        }
    }

    public function createDemoScenario(
        Request $request,
        StoreContext $context,
        DemoBusinessScenarioService $demoScenarios,
        string $store_slug,
        string $scenario
    ): RedirectResponse {
        try {
            $result = $demoScenarios->create($scenario, $request->user());
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return back()->withErrors(['demo_scenario' => $e->getMessage()]);
        }

        $store = $result['store'];

        return redirect()
            ->route('store.admin.products.index', ['store_slug' => $store->slug])
            ->with('success', "Demo store created/updated: {$store->name} ({$result['products']} products, {$result['assets']['products']} product images, {$result['assets']['categories']} category images, {$result['assets']['banners']} banners)." . ($result['asset_warning'] ? ' Image generation warning: ' . $result['asset_warning'] : ''));
    }

    public function cleanStoreData(
        Request $request,
        StoreContext $context,
        DemoBusinessScenarioService $demoScenarios
    ): RedirectResponse {
        $store = $context->getStore();

        try {
            $demoScenarios->cleanStoreData($store);
            $demoScenarios->purgeStorefrontAssets($store);

            AuditLog::write(
                $store->id,
                'pilot_demo_data_cleaned',
                'store',
                $store->id,
                [],
                auth()->id(),
                request()->ip()
            );

            return redirect()
                ->route('store.admin.pilot-import.index', ['store_slug' => $store->slug, 'tab' => 'scenarios'])
                ->with('success', "စမ်းသပ်ထားသော ဒေတာဟောင်းများ (Products, Stock, Debts, Categories, Brands) အားလုံးကို ရှင်းလင်းပြီးပါပြီ။");
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['clean_data' => $e->getMessage()]);
        }
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
        ]);

        $sessionKey = "imports.pilot.{$tab}.{$validated['token']}";
        $data = session()->get($sessionKey);

        if (! $data || ! Storage::disk('local')->exists($data['path'])) {
            return redirect()
                ->route('store.admin.pilot-import.index', ['store_slug' => $store->slug, 'tab' => $tab])
                ->withErrors(['file' => 'Import session expired or file not found. Please upload again.']);
        }

        $fullPath = Storage::disk('local')->path($data['path']);
        $service = $this->service($tab);

        try {
            $result = $service->import(
                $fullPath,
                $store,
                $request->user(),
                $data['filename'],
                $data['duplicate_strategy'] ?? 'skip'
            );

            session()->forget($sessionKey);
            Storage::disk('local')->delete($data['path']);

            $successCount = $result['imported'] ?? ($result['posted'] ?? ($result['success_count'] ?? $result['total'] ?? 0));
            $failedCount = $result['failed'] ?? ($result['failed_count'] ?? 0);

            $msg = "Import completed: {$successCount} successful";
            if ($failedCount > 0) {
                $msg .= ", {$failedCount} failed (download error report from history table below)";
            }

            return redirect()
                ->route('store.admin.pilot-import.index', ['store_slug' => $store->slug, 'tab' => $tab])
                ->with('import_result', $result)
                ->with('success', $msg . '.');
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return redirect()
                ->route('store.admin.pilot-import.index', ['store_slug' => $store->slug, 'tab' => $tab])
                ->withErrors(['file' => $e->getMessage()]);
        }
    }

    public function downloadTemplate(Request $request, StoreContext $context): StreamedResponse|SymfonyRedirect
    {
        $tab = $this->tabFromRequest($request);
        $store = $context->getStore();

        if ($tab === 'products') {
            return redirect()->route('store.admin.products.import.template', ['store_slug' => $store->slug]);
        }

        $filename = "{$tab}-import-template.csv";
        $columns = match ($tab) {
            'customers' => ['name', 'phone', 'email', 'role'],
            'debt' => ['phone', 'amount', 'notes'],
            default => ['name', 'phone', 'email', 'contact_person', 'address', 'notes'],
        };
        $example = match ($tab) {
            'customers' => ['Ma Su', '09 123 456 789', 'masu@example.com', 'retail_customer'],
            'debt' => ['09123456789', '150000', 'Opening balance from old ledger'],
            default => ['ACDC Mobile', '09 987 654 321', '', 'U Aung', 'No. 45, Maha Bandula Road, Yangon', ''],
        };

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
        if (! in_array($tab, self::TABS, true)) {
            abort(404, 'Unknown import tab.');
        }

        return $tab;
    }

    private function service(string $tab): ProductImportService|CustomerImportService|SupplierImportService|DebtOpeningImportService
    {
        return match ($tab) {
            'products' => app(ProductImportService::class),
            'customers' => app(CustomerImportService::class),
            'suppliers' => app(SupplierImportService::class),
            'debt' => app(DebtOpeningImportService::class),
            default => throw new \InvalidArgumentException("No service for tab {$tab}"),
        };
    }

    private function safeImportFilename(string $original): string
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($original)) ?? 'import.csv';

        return mb_substr($safe, 0, 120);
    }
}
