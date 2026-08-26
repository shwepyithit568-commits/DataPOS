<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\ImportHistory;
use App\Services\BrandImportService;
use App\Support\AdminListReturn;
use App\Support\ImageOptimizer;
use App\Services\StoreContext;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BrandController extends Controller
{
    public const IMAGE_MAX_KB = 10240;

    /** Whitelist of allowed per-page values — never map 'all' to an unbounded fetch. */
    public const ALLOWED_PER_PAGE = [25, 50, 100];

    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();

        $query = Brand::where('store_id', $store->id)->withCount('products');

        // Search: name or slug
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        // Filter: has logo
        if ($request->filled('has_logo')) {
            if ($request->has_logo === 'with') {
                $query->whereNotNull('logo_path');
            } elseif ($request->has_logo === 'without') {
                $query->whereNull('logo_path');
            }
        }

        // Sorting
        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest'        => $query->oldest(),
            'name_asc'      => $query->orderBy('name', 'asc'),
            'name_desc'     => $query->orderBy('name', 'desc'),
            'most_products' => $query->orderBy('products_count', 'desc'),
            default         => $query->latest(),
        };

        // Safe per-page: whitelist only; anything else (including 'all' or
        // absurd values) falls back to the default so the query can never
        // request an unbounded result set.
        $perPageRequested = (int) $request->query('per_page', 25);
        $perPage = in_array($perPageRequested, self::ALLOWED_PER_PAGE, true) ? $perPageRequested : 25;

        $brands = $query->paginate($perPage)->withQueryString();
        $totalCount = $brands->total();
        $imageMaxMb = self::IMAGE_MAX_KB / 1024;

        // Remember this filtered list URL so an edit round-trip can return
        // the user to the exact same search/filter state.
        AdminListReturn::capture($request, 'admin_brands_return');

        return view('admin.brands.index', compact('store', 'brands', 'totalCount', 'imageMaxMb'));
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'name' => ['bail', 'required', 'string', 'max:255', $this->uniqueNameRule($store->id)],
            'code' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:' . self::IMAGE_MAX_KB],
        ]);

        $logoPath = $request->hasFile('logo') ? ImageOptimizer::store($request->file('logo'), 'brands', 800) : null;

        $brand = Brand::create([
            'store_id' => $store->id,
            'name' => trim($validated['name']),
            'code' => ! empty($validated['code']) ? strtoupper(trim($validated['code'])) : null,
            'slug' => $this->uniqueSlug($store->id, Str::slug($validated['name'])),
            'logo_path' => $logoPath,
        ]);

        return back()
            ->with('success', __('messages.brand_created'))
            ->with('highlight_brand', $brand->id);
    }

    public function edit(string $store_slug, Brand $brand, StoreContext $context): View
    {
        $store = $context->getStore();

        if ($brand->store_id !== $store->id) {
            abort(403, 'Unauthorized brand access.');
        }

        $imageMaxMb = self::IMAGE_MAX_KB / 1024;

        $returnTo = AdminListReturn::peek('admin_brands_return', '/store/' . $store->slug . '/admin/brands');

        return view('admin.brands.edit', compact('store', 'brand', 'imageMaxMb', 'returnTo'));
    }

    public function update(Request $request, string $store_slug, Brand $brand, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($brand->store_id !== $store->id) {
            abort(403, 'Unauthorized brand access.');
        }

        $validated = $request->validate([
            'name' => ['bail', 'required', 'string', 'max:255', $this->uniqueNameRule($store->id, $brand->id)],
            'code' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:' . self::IMAGE_MAX_KB],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        $data = [
            'name' => trim($validated['name']),
            'code' => ! empty($validated['code']) ? strtoupper(trim($validated['code'])) : null,
            'slug' => $this->uniqueSlug($store->id, Str::slug($validated['name']), $brand->id),
        ];

        if ($request->hasFile('logo')) {
            if ($brand->logo_path) {
                Storage::disk('public')->delete($brand->logo_path);
            }
            $data['logo_path'] = ImageOptimizer::store($request->file('logo'), 'brands', 800);
        } elseif ($request->boolean('remove_logo')) {
            if ($brand->logo_path) {
                Storage::disk('public')->delete($brand->logo_path);
            }
            $data['logo_path'] = null;
        }

        $brand->update($data);

        return redirect(AdminListReturn::resolve('admin_brands_return', '/store/' . $store->slug . '/admin/brands'))
            ->with('success', __('messages.brand_updated'));
    }

    public function destroy(string $store_slug, Brand $brand, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($brand->store_id !== $store->id) {
            abort(403, 'Unauthorized brand access.');
        }

        // Safety guard: a brand that is still assigned to products must never
        // be deleted — doing so would silently null every product's brand_id.
        // The UI blocks this too, but the backend is authoritative.
        $productCount = $brand->products()->count();
        if ($productCount > 0) {
            return back()->withErrors([
                'brand' => __('messages.brand_delete_blocked', ['count' => $productCount]),
            ]);
        }

        if ($brand->logo_path) {
            Storage::disk('public')->delete($brand->logo_path);
        }

        $brand->delete();

        return back()->with('success', __('messages.brand_deleted'));
    }

    /**
     * Stream the store's brand list as an Excel-friendly CSV (round-trips
     * through the brand importer — edit offline, re-import).
     */
    public function export(StoreContext $context): StreamedResponse
    {
        $store = $context->getStore();

        $brands = Brand::where('store_id', $store->id)
            ->orderBy('name')
            ->get(['name', 'slug']);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="brands-' . now()->format('Ymd-His') . '.csv"',
        ];

        return response()->streamDownload(function () use ($brands) {
            $stream = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens the file with correct encoding
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Name', 'Slug']);

            foreach ($brands as $brand) {
                fputcsv($stream, [
                    $this->csvCell($brand->name),
                    $this->csvCell($brand->slug),
                ]);
            }

            fclose($stream);
        }, 'brands-' . now()->format('Ymd-His') . '.csv', $headers);
    }

    public function importForm(StoreContext $context): View
    {
        $store = $context->getStore();
        $histories = ImportHistory::where('store_id', $store->id)
            ->where('type', 'brands')
            ->with('user')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.brands.import', compact('store', 'histories'));
    }

    public function import(Request $request, StoreContext $context, BrandImportService $importer): RedirectResponse
    {
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
            $preview = $importer->preview(Storage::disk('local')->path($storedPath), $store, $duplicateStrategy);
            $token = Str::random(40);

            session()->put("imports.brands.{$token}", [
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

    public function confirmImport(Request $request, StoreContext $context, BrandImportService $importer): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'token' => ['required', 'string'],
            'duplicate_strategy' => ['required', 'in:skip,update'],
        ]);

        $sessionKey = "imports.brands.{$validated['token']}";
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
                $pendingImport['filename'] ?? 'brands-import.csv',
                $validated['duplicate_strategy']
            );

            $summary = "Import Completed - Total: {$result['total']} | Imported: {$result['imported']} | Updated: {$result['updated']} | Skipped Duplicate: {$result['skipped_duplicate']} | Failed: {$result['failed']}";

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
            'Content-Disposition' => 'attachment; filename="brand-import-template.xlsx"',
        ];

        return response()->streamDownload(function () {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Brands');
            $sheet->fromArray([
                ['name', 'slug'],
                ['Xiaomi', 'xiaomi'],
            ]);

            $instructionSheet = $spreadsheet->createSheet();
            $instructionSheet->setTitle('Instructions');
            $instructionSheet->fromArray([
                ['Instruction', 'Value'],
                ['Required columns', 'name'],
                ['Optional columns', 'slug'],
                ['Duplicate rule', 'Brands are matched by slug, then by name (case-insensitive). Existing brands are skipped or updated depending on the chosen strategy.'],
                ['Slug format', 'Lowercase letters, numbers and dashes only. Leave blank to auto-generate from name.'],
                ['Store assignment', 'The system always uses the current admin store. Do not add store_id.'],
            ]);

            $spreadsheet->setActiveSheetIndex(0);
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'brand-import-template.xlsx', $headers);
    }

    public function quickStore(Request $request, StoreContext $context): \Illuminate\Http\JsonResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'name' => ['bail', 'required', 'string', 'max:255', $this->uniqueNameRule($store->id)],
            'code' => ['nullable', 'string', 'max:50'],
        ]);

        $brand = Brand::create([
            'store_id' => $store->id,
            'name'     => trim($validated['name']),
            'code'     => ! empty($validated['code']) ? strtoupper(trim($validated['code'])) : null,
            'slug'     => $this->uniqueSlug($store->id, Str::slug($validated['name'])),
        ]);

        return response()->json([
            'success'  => true,
            'id'       => $brand->id,
            'name'     => $brand->name,
            'code'     => $brand->code,
        ]);
    }

    /**
     * Validation rule: brand name must be non-empty after trimming and unique
     * within the store using a normalized (lowercased + trimmed) comparison.
     */
    private function uniqueNameRule(int $storeId, ?int $ignoreId = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($storeId, $ignoreId) {
            $normalized = mb_strtolower(trim((string) $value));

            if ($normalized === '') {
                $fail(__('messages.brand_name_required'));

                return;
            }

            $query = Brand::where('store_id', $storeId)
                ->whereRaw('LOWER(TRIM(name)) = ?', [$normalized]);

            if ($ignoreId !== null) {
                $query->where('id', '!=', $ignoreId);
            }

            if ($query->exists()) {
                $fail(__('messages.brand_name_taken'));
            }
        };
    }

    /**
     * Build a slug that never collides with an existing brand in the store
     * (the brands table has a unique (store_id, slug) constraint).
     */
    private function uniqueSlug(int $storeId, string $base, ?int $ignoreId = null): string
    {
        $slug = $base;
        $suffix = 2;

        while (Brand::where('store_id', $storeId)
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }

    private function safeImportFilename(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $name = Str::slug(pathinfo($filename, PATHINFO_FILENAME)) ?: 'brands-import';

        return $name . '-' . now()->format('YmdHis') . '.' . $extension;
    }

    /**
     * Neutralize spreadsheet formula injection in exported CSV cells:
     * cells starting with =, +, - or @ get a leading apostrophe so Excel /
     * Google Sheets treat them as text instead of formulas.
     */
    private function csvCell(mixed $value): string
    {
        $value = (string) $value;
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }

        return $value;
    }
}
