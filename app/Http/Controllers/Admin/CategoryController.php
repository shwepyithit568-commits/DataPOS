<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ImportHistory;
use App\Services\CategoryImportService;
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

class CategoryController extends Controller
{
    public const IMAGE_MAX_KB = 10240;

    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();

        // Load every store category once with DB-computed counts. The tree is
        // small (a few dozen rows) so a single eager-loaded query plus an
        // in-memory inclusion pass keeps counts authoritative and cheap.
        $full = Category::where('store_id', $store->id)
            ->with('parent')
            ->withCount(['products', 'children'])
            ->get();

        // True per-parent totals (own products + every child's products) —
        // computed from the full set so summaries never show a false zero
        // when a search/filter excludes part of a parent's children.
        $parentProductTotals = [];
        foreach ($full as $category) {
            $parentProductTotals[$category->id] = ($parentProductTotals[$category->id] ?? 0) + $category->products_count;
            if ($category->parent_id) {
                $parentProductTotals[$category->parent_id] = ($parentProductTotals[$category->parent_id] ?? 0) + $category->products_count;
            }
        }

        $all = $full;
        $matchingCount = null;

        // Search: name or slug. When a Main Category matches, include ALL of
        // its direct Sub-categories so the section shows true counts. When a
        // Sub-category matches, include its parent as a context container.
        if ($request->filled('search')) {
            $needle = mb_strtolower(trim((string) $request->search));
            $matched = $full->filter(fn (Category $category) => str_contains(mb_strtolower($category->name), $needle)
                || str_contains(mb_strtolower($category->slug), $needle));

            $includedIds = $matched->pluck('id')->toBase();
            foreach ($matched as $category) {
                if ($category->parent_id === null) {
                    $includedIds = $includedIds->merge($full->where('parent_id', $category->id)->pluck('id'));
                } else {
                    $includedIds->push($category->parent_id);
                }
            }

            $all = $full->whereIn('id', $includedIds->unique()->values())->values();
            $matchingCount = $matched->count();
        }

        // Filter: has image (same inclusion model — matched parents bring
        // their children so counts stay true, matched children bring their
        // parent as a container).
        if ($request->filled('has_image')) {
            $matched = $full->filter(fn (Category $category) => $request->has_image === 'with'
                ? $category->image_path !== null
                : $category->image_path === null);

            $includedIds = $matched->pluck('id')->toBase();
            foreach ($matched as $category) {
                if ($category->parent_id === null) {
                    $includedIds = $includedIds->merge($full->where('parent_id', $category->id)->pluck('id'));
                } else {
                    $includedIds->push($category->parent_id);
                }
            }

            $all = $full->whereIn('id', $includedIds->unique()->values())->values();
        }

        $parents = $all->whereNull('parent_id')->sortBy('name')->values();
        $children = $all->whereNotNull('parent_id')->sortBy('name')->groupBy('parent_id');

        $totalCount = $all->count();
        $hasNoCategories = $full->count() === 0;
        $imageMaxMb = self::IMAGE_MAX_KB / 1024;

        // The section that contains the newly created category auto-expands
        // on load so the highlighted row is actually visible.
        $highlightId = session('highlight_category');
        $highlightParentId = null;
        if ($highlightId) {
            $highlighted = Category::find((int) $highlightId);
            $highlightParentId = $highlighted ? ($highlighted->parent_id ?? $highlighted->id) : null;
        }

        // With a search/image filter active, auto-expand every section so
        // matching rows are visible instead of hidden behind a click.
        $autoOpen = $request->filled('search') || $request->filled('has_image');

        // Remember this filtered list URL so an edit round-trip can return
        // the user to the exact same search/filter state.
        AdminListReturn::capture($request, 'admin_categories_return');

        return view('admin.categories.index', compact(
            'store',
            'parents',
            'children',
            'parentProductTotals',
            'totalCount',
            'matchingCount',
            'hasNoCategories',
            'autoOpen',
            'imageMaxMb',
            'highlightParentId'
        ));
    }

    /**
     * Stream the store's category tree as an Excel-friendly CSV (round-trips
     * through the category importer — the parent column references a Main
     * category by name or slug).
     */
    public function export(Request $request, StoreContext $context): \Symfony\Component\HttpFoundation\BinaryFileResponse|StreamedResponse
    {
        $store = $context->getStore();
        $format = strtolower((string) $request->query('format', 'csv'));

        $categories = Category::where('store_id', $store->id)
            ->with('parent')
            ->orderBy('name')
            ->get();

        if ($format === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="categories-' . now()->format('Ymd-His') . '.csv"',
            ];

            return response()->streamDownload(function () use ($categories) {
                $stream = fopen('php://output', 'w');
                fwrite($stream, "\xEF\xBB\xBF");
                fputcsv($stream, ['Name', 'Slug', 'Parent', 'Description', 'Icon']);

                foreach ($categories as $category) {
                    fputcsv($stream, [
                        $this->csvCell($category->name),
                        $this->csvCell($category->slug),
                        $this->csvCell($category->parent?->name ?? ''),
                        $this->csvCell($category->description ?? ''),
                        $this->csvCell($category->icon ?? ''),
                    ]);
                }

                fclose($stream);
            }, 'categories-' . now()->format('Ymd-His') . '.csv', $headers);
        }

        $filename = 'Categories_' . $store->slug . '_' . now()->format('Ymd_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_cat_');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Categories');

        $sheet->setCellValue('A1', $store->name . ' - Categories Export');
        $sheet->setCellValue('A2', 'Export Date: ' . now()->format('d/m/Y h:i A') . ' | Total Count: ' . $categories->count());
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('4C1D95');
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('64748B');

        $row = 4;
        $headers = ['A' => 'Name', 'B' => 'Slug', 'C' => 'Parent', 'D' => 'Description', 'E' => 'Icon'];
        foreach ($headers as $col => $title) {
            $sheet->setCellValue("{$col}{$row}", $title);
        }
        $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '6D28D9']],
        ]);

        $row++;
        foreach ($categories as $cat) {
            $sheet->setCellValue("A{$row}", $cat->name);
            $sheet->setCellValue("B{$row}", $cat->slug);
            $sheet->setCellValue("C{$row}", $cat->parent?->name ?? '');
            $sheet->setCellValue("D{$row}", $cat->description ?? '');
            $sheet->setCellValue("E{$row}", $cat->icon ?? '');
            $row++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function importForm(StoreContext $context): View
    {
        $store = $context->getStore();
        $histories = ImportHistory::where('store_id', $store->id)
            ->where('type', 'categories')
            ->with('user')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.categories.import', compact('store', 'histories'));
    }

    public function import(Request $request, StoreContext $context, CategoryImportService $importer): RedirectResponse
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

            session()->put("imports.categories.{$token}", [
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

    public function confirmImport(Request $request, StoreContext $context, CategoryImportService $importer): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'token' => ['required', 'string'],
            'duplicate_strategy' => ['required', 'in:skip,update'],
        ]);

        $sessionKey = "imports.categories.{$validated['token']}";
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
                $pendingImport['filename'] ?? 'categories-import.csv',
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
            'Content-Disposition' => 'attachment; filename="category-import-template.xlsx"',
        ];

        return response()->streamDownload(function () {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Categories');
            $sheet->fromArray([
                ['name', 'slug', 'parent', 'description', 'icon'],
                ['Mobile Phones', 'mobile-phones', '', 'Smartphones and accessories', '📱'],
                ['iPhone Cases', 'iphone-cases', 'Mobile Phones', 'Cases for iPhone models', ''],
            ]);

            $instructionSheet = $spreadsheet->createSheet();
            $instructionSheet->setTitle('Instructions');
            $instructionSheet->fromArray([
                ['Instruction', 'Value'],
                ['Required columns', 'name'],
                ['Optional columns', 'slug, parent, description, icon'],
                ['parent column', 'Name or slug of an existing (or same-file) Main category. Leave blank for a Main category.'],
                ['Tree depth', 'Only two levels: Main categories and their Sub-categories.'],
                ['Duplicate rule', 'Categories are matched by slug, then by name (case-insensitive). Existing categories are skipped or updated depending on the chosen strategy.'],
                ['Slug format', 'Lowercase letters, numbers and dashes only. Leave blank to auto-generate from name.'],
                ['icon column', 'Up to 8 characters (emoji or short label).'],
                ['Store assignment', 'The system always uses the current admin store. Do not add store_id.'],
            ]);

            $spreadsheet->setActiveSheetIndex(0);
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'category-import-template.xlsx', $headers);
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'name' => ['bail', 'required', 'string', 'max:255', $this->uniqueNameRule($store->id)],
            'code' => ['nullable', 'string', 'max:50'],
            'parent_id' => ['nullable', $this->parentRule($store->id)],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:8'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:' . self::IMAGE_MAX_KB],
        ]);

        $imagePath = $request->hasFile('image') ? ImageOptimizer::store($request->file('image'), 'categories', 1200) : null;

        $category = Category::create([
            'store_id' => $store->id,
            'parent_id' => ! empty($validated['parent_id']) ? (int) $validated['parent_id'] : null,
            'name' => trim($validated['name']),
            'code' => ! empty($validated['code']) ? strtoupper(trim($validated['code'])) : null,
            'slug' => $this->uniqueSlug($store->id, Str::slug($validated['name'])),
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'image_path' => $imagePath,
        ]);

        return back()
            ->with('success', __('messages.category_created'))
            ->with('highlight_category', $category->id);
    }

    public function edit(string $store_slug, Category $category, StoreContext $context): View
    {
        $store = $context->getStore();

        if ($category->store_id !== $store->id) {
            abort(403, 'Unauthorized category access.');
        }

        $imageMaxMb = self::IMAGE_MAX_KB / 1024;

        // Only Main (top-level) categories of this store can be parents —
        // the tree is strictly two levels.
        $parentOptions = Category::where('store_id', $store->id)
            ->whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $childrenCount = $category->children()->count();

        $returnTo = AdminListReturn::peek('admin_categories_return', '/store/' . $store->slug . '/admin/categories');

        return view('admin.categories.edit', compact('store', 'category', 'imageMaxMb', 'parentOptions', 'childrenCount', 'returnTo'));
    }

    public function update(Request $request, string $store_slug, Category $category, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($category->store_id !== $store->id) {
            abort(403, 'Unauthorized category access.');
        }

        $validated = $request->validate([
            'name' => ['bail', 'required', 'string', 'max:255', $this->uniqueNameRule($store->id, $category->id)],
            'code' => ['nullable', 'string', 'max:50'],
            'parent_id' => ['nullable', $this->parentRule($store->id, $category)],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:8'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:' . self::IMAGE_MAX_KB],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        $data = [
            'name' => trim($validated['name']),
            'code' => ! empty($validated['code']) ? strtoupper(trim($validated['code'])) : null,
            'slug' => $this->uniqueSlug($store->id, Str::slug($validated['name']), $category->id),
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'parent_id' => ! empty($validated['parent_id']) ? (int) $validated['parent_id'] : null,
        ];

        if ($request->hasFile('image')) {
            // Replace: delete old image, store optimized new one.
            if ($category->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }
            $data['image_path'] = ImageOptimizer::store($request->file('image'), 'categories', 1200);
        } elseif ($request->boolean('remove_image')) {
            if ($category->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }
            $data['image_path'] = null;
        }

        $category->update($data);

        return redirect(AdminListReturn::resolve('admin_categories_return', '/store/' . $store->slug . '/admin/categories'))
            ->with('success', __('messages.category_updated'));
    }

    public function destroy(string $store_slug, Category $category, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($category->store_id !== $store->id) {
            abort(403, 'Unauthorized category access.');
        }

        // Safety guards (UI blocks these too, but the backend is authoritative):
        // deleting a category with products would silently null product
        // category_id, and deleting a parent would silently promote children
        // to top-level — both destructive consequences must not happen by
        // accident. Only an unused leaf category may be deleted.
        $productsCount = $category->products()->count();
        if ($productsCount > 0) {
            return back()->withErrors([
                'category' => __('messages.category_delete_blocked_products', ['count' => $productsCount]),
            ]);
        }

        $childrenCount = $category->children()->count();
        if ($childrenCount > 0) {
            return back()->withErrors([
                'category' => __('messages.category_delete_blocked_children', ['count' => $childrenCount]),
            ]);
        }

        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }

        $category->delete();

        return back()->with('success', __('messages.category_deleted'));
    }

    public function quickStore(Request $request, StoreContext $context): \Illuminate\Http\JsonResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'name'      => ['bail', 'required', 'string', 'max:255', $this->uniqueNameRule($store->id)],
            'code'      => ['nullable', 'string', 'max:50'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        // Parent (when given) must be a MAIN category of THIS store — sub-of-sub
        // is not allowed (same rule as the Master Data page).
        $parent = null;
        if ($validated['parent_id'] ?? null) {
            $parent = Category::where('store_id', $store->id)
                ->whereNull('parent_id')
                ->find((int) $validated['parent_id']);

            if (! $parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent category not found in this store.',
                ], 422);
            }
        }

        $category = Category::create([
            'store_id'  => $store->id,
            'name'      => trim($validated['name']),
            'code'      => ! empty($validated['code']) ? strtoupper(trim($validated['code'])) : null,
            'slug'      => $this->uniqueSlug($store->id, Str::slug($validated['name'])),
            'parent_id' => $parent?->id,
        ]);

        return response()->json([
            'success'   => true,
            'id'        => $category->id,
            'name'      => $category->name,
            'code'      => $category->code,
            'parent_id' => $category->parent_id,
            'parent'    => $parent?->name,
        ]);
    }

    /**
     * Validation rule: category name must be non-empty after trimming and
     * unique within the store using a normalized (lowercased + trimmed)
     * comparison. Uniqueness is global per store — a Main and a Sub-category
     * may not share the same display name.
     */
    private function uniqueNameRule(int $storeId, ?int $ignoreId = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($storeId, $ignoreId) {
            $normalized = mb_strtolower(trim((string) $value));

            if ($normalized === '') {
                $fail(__('messages.category_name_required'));

                return;
            }

            $query = Category::where('store_id', $storeId)
                ->whereRaw('LOWER(TRIM(name)) = ?', [$normalized]);

            if ($ignoreId !== null) {
                $query->where('id', '!=', $ignoreId);
            }

            if ($query->exists()) {
                $fail(__('messages.category_name_taken'));
            }
        };
    }

    /**
     * Validation rule for parent_id:
     *  - the parent must exist and belong to the active store (the plain
     *    `exists:categories,id` rule alone is not enough for store isolation);
     *  - the parent must itself be a Main (top-level) category — the tree is
     *    strictly two levels;
     *  - when editing, cycles are rejected and a Main with Sub-categories can
     *    never be converted into a Sub-category (that would create a third
     *    hierarchy level).
     */
    private function parentRule(int $storeId, ?Category $editing = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($storeId, $editing) {
            if ($value === null || $value === '') {
                return;
            }

            $parent = Category::where('store_id', $storeId)->find((int) $value);
            if (! $parent) {
                $fail(__('messages.category_parent_invalid'));

                return;
            }

            if ($parent->parent_id !== null) {
                $fail(__('messages.category_parent_must_be_main'));

                return;
            }

            if ($editing) {
                if ((int) $parent->id === (int) $editing->id) {
                    $fail(__('messages.category_self_parent'));

                    return;
                }

                // Walk up the chain — if we ever reach the category being
                // edited, this parent would create a cycle.
                $cursor = $parent;
                $visited = [];
                while ($cursor) {
                    if ((int) $cursor->id === (int) $editing->id || isset($visited[$cursor->id])) {
                        $fail(__('messages.category_cycle'));

                        return;
                    }
                    $visited[$cursor->id] = true;
                    $cursor = $cursor->parent;
                }

                // A Main Category that already has Sub-categories must not be
                // demoted to a Sub-category (would create a third level and
                // hide its children from the two-level tree).
                if ($editing->children()->exists()) {
                    $fail(__('messages.category_convert_blocked'));
                }
            }
        };
    }

    /**
     * Build a slug that never collides with an existing category in the store
     * (the categories table has a unique (store_id, slug) constraint).
     */
    private function uniqueSlug(int $storeId, string $base, ?int $ignoreId = null): string
    {
        $slug = $base;
        $suffix = 2;

        while (Category::where('store_id', $storeId)
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
        $name = Str::slug(pathinfo($filename, PATHINFO_FILENAME)) ?: 'categories-import';

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
