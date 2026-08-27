<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GlassFinderItem;
use App\Models\ImportHistory;
use App\Support\AdminListReturn;
use App\Services\GlassCodeNormalizer;
use App\Services\GlassFinderImportService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GlassFinderAdminController extends Controller
{
    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        $baseQuery = GlassFinderItem::where('store_id', $store->id);

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'unique_codes' => (clone $baseQuery)->distinct('glass_code')->count('glass_code'),
            'in_stock' => (clone $baseQuery)->where('stock_status', 'in_stock')->count(),
            'out_of_stock' => (clone $baseQuery)->where('stock_status', 'out_of_stock')->count(),
            'brands_count' => (clone $baseQuery)->distinct('brand')->count('brand'),
        ];

        $query = clone $baseQuery;

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('phone_model', 'like', '%' . $search . '%')
                    ->orWhere('glass_code', 'like', '%' . $search . '%')
                    ->orWhere('brand', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        if ($request->filled('stock_status')) {
            $query->where('stock_status', $request->stock_status);
        }

        $sort = $request->input('sort', 'newest');
        if ($sort === 'oldest') {
            $query->oldest();
        } elseif ($sort === 'code_asc') {
            $query->orderBy('glass_code', 'asc');
        } elseif ($sort === 'code_desc') {
            $query->orderBy('glass_code', 'desc');
        } else {
            $query->latest();
        }

        $allItems = $query->get();
        $items = $allItems
            ->groupBy('glass_code')
            ->sortKeys(SORT_STRING | SORT_FLAG_CASE);
        $totalCount = $allItems->count();
        $autoOpen = $request->filled('search') || $request->filled('stock_status') || $request->filled('brand');

        $brands = GlassFinderItem::where('store_id', $store->id)
            ->whereNotNull('brand')
            ->distinct()
            ->pluck('brand')
            ->sort()
            ->values();

        $histories = ImportHistory::where('store_id', $store->id)
            ->where('type', 'glass_finder')
            ->with('user')
            ->latest()
            ->take(10)
            ->get();

        // Remember this filtered list URL so an edit round-trip can return
        // the user to the exact same search/filter state.
        AdminListReturn::capture($request, 'admin_glass_finder_return');

        return view('admin.glass_finder.index', compact('store', 'items', 'allItems', 'totalCount', 'autoOpen', 'histories', 'stats', 'brands'));
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'brand' => ['required', 'string', 'max:255'],
            'phone_model' => ['required', 'string', 'max:255'],
            'glass_code' => ['required', 'string', 'max:100'],
            'stock_status' => ['required', 'in:in_stock,out_of_stock'],
        ]);

        GlassFinderItem::create([
            'store_id' => $store->id,
            'brand' => $validated['brand'],
            'phone_model' => $validated['phone_model'],
            'glass_code' => $validated['glass_code'],
            'normalized_glass_code' => GlassCodeNormalizer::normalize($validated['glass_code']),
            'stock_status' => $validated['stock_status'],
        ]);

        return back()->with('success', 'Glass item created successfully.');
    }

    public function import(Request $request, StoreContext $context, GlassFinderImportService $importer): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
        ]);

        $file = $request->file('file');
        $safeFilename = $this->safeImportFilename($file->getClientOriginalName());
        $storedPath = $file->storeAs(
            'imports/tmp',
            Str::uuid()->toString() . '-' . $safeFilename,
            'local'
        );

        try {
            $preview = $importer->preview(Storage::disk('local')->path($storedPath), $store);
            $token = Str::random(40);

            session()->put("imports.glass_finder.{$token}", [
                'path' => $storedPath,
                'filename' => $safeFilename,
            ]);

            return back()->with('import_preview', $preview + [
                'token' => $token,
                'filename' => $safeFilename,
            ]);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            Storage::disk('local')->delete($storedPath);
            return back()->withErrors(['file' => $e->getMessage()]);
        }
    }

    public function confirmImport(Request $request, StoreContext $context, GlassFinderImportService $importer): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $sessionKey = "imports.glass_finder.{$validated['token']}";
        $pendingImport = session()->pull($sessionKey);

        if (!$pendingImport || empty($pendingImport['path'])) {
            return back()->withErrors(['file' => 'Import preview expired. Please upload the file again.']);
        }

        $storedPath = $pendingImport['path'];

        try {
            $result = $importer->import(
                Storage::disk('local')->path($storedPath),
                $store,
                $request->user(),
                $pendingImport['filename'] ?? 'glass-finder-import.xlsx'
            );

            $summary = "Import Completed - Total: {$result['total']} | Imported: {$result['imported']} | Skipped Duplicate: {$result['skipped_duplicate']} | Failed: {$result['failed']}";

            return back()->with('import_result', $result)->with('success', $summary);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        } finally {
            Storage::disk('local')->delete($storedPath);
        }
    }

    public function downloadImportTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="glass-finder-import-template.xlsx"',
        ];

        return response()->streamDownload(function () {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Glass Finder');
            $sheet->fromArray([
                ['brand', 'phone_model', 'glass_code', 'stock_status'],
                ['Apple', 'iPhone 15 Pro Max', 'GX-015', 'in_stock'],
            ]);

            $instructionSheet = $spreadsheet->createSheet();
            $instructionSheet->setTitle('Instructions');
            $instructionSheet->fromArray([
                ['Instruction', 'Value'],
                ['Required columns', 'brand, phone_model, glass_code'],
                ['Optional columns', 'stock_status'],
                ['Allowed stock_status', 'in_stock, out_of_stock'],
                ['Duplicate rule', 'Same phone_model and normalized glass_code in the current store will be skipped.'],
                ['Store assignment', 'The system always uses the current admin store. Do not add store_id.'],
            ]);

            $spreadsheet->setActiveSheetIndex(0);
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'glass-finder-import-template.xlsx', $headers);
    }

    public function edit(string $store_slug, GlassFinderItem $item, StoreContext $context): View
    {
        $store = $context->getStore();

        if ($item->store_id !== $store->id) {
            abort(403, 'Unauthorized action on store glass item.');
        }

        $returnTo = AdminListReturn::peek('admin_glass_finder_return', '/store/' . $store->slug . '/admin/glass-finder');

        return view('admin.glass_finder.edit', compact('store', 'item', 'returnTo'));
    }

    private function safeImportFilename(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $name = Str::slug(pathinfo($filename, PATHINFO_FILENAME)) ?: 'glass-finder-import';

        return $name . '-' . now()->format('YmdHis') . '.' . $extension;
    }

    public function update(Request $request, string $store_slug, GlassFinderItem $item, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($item->store_id !== $store->id) {
            abort(403, 'Unauthorized action on store glass item.');
        }

        $validated = $request->validate([
            'brand' => ['required', 'string', 'max:255'],
            'phone_model' => ['required', 'string', 'max:255'],
            'glass_code' => ['required', 'string', 'max:100'],
            'stock_status' => ['required', 'in:in_stock,out_of_stock'],
        ]);

        $item->update([
            'brand' => $validated['brand'],
            'phone_model' => $validated['phone_model'],
            'glass_code' => $validated['glass_code'],
            'normalized_glass_code' => GlassCodeNormalizer::normalize($validated['glass_code']),
            'stock_status' => $validated['stock_status'],
        ]);

        return redirect(AdminListReturn::resolve('admin_glass_finder_return', '/store/' . $store->slug . '/admin/glass-finder'))
            ->with('success', 'Glass item updated successfully.');
    }

    public function destroy(string $store_slug, GlassFinderItem $item, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($item->store_id !== $store->id) {
            abort(403, 'Unauthorized action on store glass item.');
        }

        $item->delete();

        return back()->with('success', 'Glass item deleted successfully.');
    }
}
