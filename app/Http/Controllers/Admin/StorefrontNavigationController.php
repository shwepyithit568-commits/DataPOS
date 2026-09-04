<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StorefrontNavigationItem;
use App\Models\StorefrontPage;
use App\Services\StoreContext;
use App\Services\StorefrontNavigationDefaultsService;
use App\Services\StorefrontNavigationRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorefrontNavigationController extends Controller
{
    public function __construct(
        protected StorefrontNavigationDefaultsService $defaultsService
    ) {}

    /**
     * Display a listing of storefront navigation items.
     */
    public function index(Request $request, string $store_slug, StoreContext $context): View
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $query = StorefrontNavigationItem::where('store_id', $store->id)->with('storefrontPage');

        // Placement filter
        $placement = $request->get('placement', 'all');
        if ($placement === 'desktop') {
            $query->where('show_desktop', true);
        } elseif ($placement === 'mobile_drawer') {
            $query->where('show_mobile_drawer', true);
        } elseif ($placement === 'mobile_bottom') {
            $query->where('show_mobile_bottom', true);
        }

        // Search query
        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('label_my', 'like', "%{$search}%")
                  ->orWhere('label_en', 'like', "%{$search}%")
                  ->orWhere('label_zh_cn', 'like', "%{$search}%")
                  ->orWhere('menu_key', 'like', "%{$search}%");
            });
        }

        $items = $query->ordered()->get();

        // Calculate KPI Statistics
        $allItems = StorefrontNavigationItem::where('store_id', $store->id)->get();
        $stats = [
            'total'          => $allItems->count(),
            'desktop_count'  => $allItems->where('show_desktop', true)->where('is_enabled', true)->count(),
            'bottom_count'   => $allItems->where('show_mobile_bottom', true)->where('is_enabled', true)->count(),
            'drawer_count'   => $allItems->where('show_mobile_drawer', true)->where('is_enabled', true)->count(),
            'disabled_count' => $allItems->where('is_enabled', false)->count(),
        ];

        return view('admin.navigation.index', compact('store', 'items', 'stats', 'placement', 'search'));
    }

    /**
     * Show the form for creating a new navigation item.
     */
    public function create(Request $request, string $store_slug, StoreContext $context): View
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $item = new StorefrontNavigationItem([
            'show_desktop'       => true,
            'show_mobile_drawer' => true,
            'show_mobile_bottom' => false,
            'is_enabled'         => true,
            'destination_type'   => 'system',
            'icon_key'           => 'home',
        ]);

        $systemDestinations = StorefrontNavigationRegistry::getSystemDestinations();
        $iconKeys = StorefrontNavigationRegistry::getIconKeys();
        $pages = StorefrontPage::where('store_id', $store->id)->published()->orderBy('title_en')->get();

        return view('admin.navigation.form', compact('store', 'item', 'systemDestinations', 'iconKeys', 'pages'));
    }

    /**
     * Store a newly created navigation item in storage.
     */
    public function store(Request $request, string $store_slug, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $validated = $this->validateItem($request, $store->id);

        // Enforce placement constraints
        $this->enforcePlacementLimits($store->id, $validated);

        if (empty($validated['menu_key'])) {
            $validated['menu_key'] = Str::slug($validated['label_en'] ?: $validated['label_my'], '_') . '_' . Str::random(4);
        }

        // Set sort_order to max + 10
        $maxOrder = StorefrontNavigationItem::where('store_id', $store->id)->max('sort_order') ?? 0;
        $validated['sort_order'] = $maxOrder + 10;
        $validated['store_id'] = $store->id;

        StorefrontNavigationItem::create($validated);

        return redirect()->route('admin.navigation.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.saved_successfully'));
    }

    /**
     * Show the form for editing the specified navigation item.
     */
    public function edit(Request $request, string $store_slug, int $id, StoreContext $context): View
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $item = StorefrontNavigationItem::where('store_id', $store->id)->findOrFail($id);
        $systemDestinations = StorefrontNavigationRegistry::getSystemDestinations();
        $iconKeys = StorefrontNavigationRegistry::getIconKeys();
        $pages = StorefrontPage::where('store_id', $store->id)->published()->orderBy('title_en')->get();

        return view('admin.navigation.form', compact('store', 'item', 'systemDestinations', 'iconKeys', 'pages'));
    }

    /**
     * Update the specified navigation item in storage.
     */
    public function update(Request $request, string $store_slug, int $id, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $item = StorefrontNavigationItem::where('store_id', $store->id)->findOrFail($id);
        $validated = $this->validateItem($request, $store->id, $item->id);

        $this->enforcePlacementLimits($store->id, $validated, $item->id);

        $item->update($validated);

        return redirect()->route('admin.navigation.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.saved_successfully'));
    }

    /**
     * Remove the specified navigation item from storage.
     */
    public function destroy(Request $request, string $store_slug, int $id, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $item = StorefrontNavigationItem::where('store_id', $store->id)->findOrFail($id);
        $item->delete();

        return redirect()->route('admin.navigation.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.deleted_successfully'));
    }

    /**
     * Move an item up or down in sort order.
     */
    public function reorder(Request $request, string $store_slug, int $id, string $direction, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $item = StorefrontNavigationItem::where('store_id', $store->id)->findOrFail($id);

        $adjacent = $direction === 'up'
            ? StorefrontNavigationItem::where('store_id', $store->id)->where('sort_order', '<', $item->sort_order)->orderBy('sort_order', 'desc')->first()
            : StorefrontNavigationItem::where('store_id', $store->id)->where('sort_order', '>', $item->sort_order)->orderBy('sort_order', 'asc')->first();

        if ($adjacent) {
            $tempOrder = $item->sort_order;
            $item->sort_order = $adjacent->sort_order;
            $adjacent->sort_order = $tempOrder;

            // Handle edge case where both had identical order numbers
            if ($item->sort_order === $adjacent->sort_order) {
                if ($direction === 'up') {
                    $item->sort_order -= 1;
                } else {
                    $item->sort_order += 1;
                }
            }

            $item->save();
            $adjacent->save();
        }

        return redirect()->route('admin.navigation.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.reordered_successfully'));
    }

    /**
     * Quick toggle enabled status.
     */
    public function toggleStatus(Request $request, string $store_slug, int $id, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $item = StorefrontNavigationItem::where('store_id', $store->id)->findOrFail($id);
        $item->is_enabled = !$item->is_enabled;
        $item->save();

        return back()->with('success', __('messages.saved_successfully'));
    }

    /**
     * Reset store navigation items to default system items.
     */
    public function resetDefaults(Request $request, string $store_slug, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $this->defaultsService->seedDefaultsForStore($store, true);

        return redirect()->route('admin.navigation.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.reset_to_defaults_successfully'));
    }

    /**
     * Export navigation items to Excel (.xlsx) or CSV (.csv).
     */
    public function export(Request $request, string $store_slug, string $format, StoreContext $context): StreamedResponse|Response
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $items = StorefrontNavigationItem::where('store_id', $store->id)->with('storefrontPage')->ordered()->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Navigation Items');

        // Headers
        $headers = [
            'ID',
            'Menu Key',
            'Label (MY)',
            'Label (EN)',
            'Label (ZH)',
            'Icon Key',
            'Destination Type',
            'Destination / Target',
            'Desktop',
            'Mobile Drawer',
            'Mobile Bottom',
            'Requires Auth',
            'Enabled',
            'Sort Order',
        ];
        $sheet->fromArray([$headers], null, 'A1');

        // Rows
        $rowNum = 2;
        foreach ($items as $item) {
            $target = match ($item->destination_type) {
                'system'     => $item->destination_key,
                'page'       => $item->storefrontPage?->title_en ?: ('Page #' . $item->storefront_page_id),
                'custom_url' => $item->custom_url,
                default      => '-',
            };

            $data = [
                $item->id,
                $item->menu_key,
                $item->label_my,
                $item->label_en,
                $item->label_zh_cn,
                $item->icon_key,
                $item->destination_type,
                $target,
                $item->show_desktop ? 'Yes' : 'No',
                $item->show_mobile_drawer ? 'Yes' : 'No',
                $item->show_mobile_bottom ? 'Yes' : 'No',
                $item->requires_auth ? 'Yes' : 'No',
                $item->is_enabled ? 'Active' : 'Disabled',
                $item->sort_order,
            ];
            $sheet->fromArray([$data], null, 'A' . $rowNum);
            $rowNum++;
        }

        $filename = 'Navigation_Items_' . $store->slug . '_' . date('Y-m-d_His');

        if ($format === 'csv') {
            $writer = new Csv($spreadsheet);
            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $filename . '.csv', [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
            ]);
        }

        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename . '.xlsx', [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.xlsx"',
        ]);
    }

    /**
     * Validate incoming item payload.
     */
    protected function validateItem(Request $request, int $storeId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'label_my'            => 'required|string|max:255',
            'label_en'            => 'required|string|max:255',
            'label_zh_cn'         => 'nullable|string|max:255',
            'menu_key'            => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('storefront_navigation_items', 'menu_key')
                    ->where('store_id', $storeId)
                    ->ignore($ignoreId),
            ],
            'icon_key'            => 'required|string|in:' . implode(',', StorefrontNavigationRegistry::getIconKeys()),
            'destination_type'    => 'required|string|in:system,page,custom_url',
            'destination_key'     => 'nullable|required_if:destination_type,system|string|in:' . implode(',', array_keys(StorefrontNavigationRegistry::getSystemDestinations())),
            'storefront_page_id'  => [
                'nullable',
                'required_if:destination_type,page',
                Rule::exists('storefront_pages', 'id')->where('store_id', $storeId),
            ],
            'custom_url'          => 'nullable|required_if:destination_type,custom_url|string|max:2000',
            'show_desktop'        => 'boolean',
            'show_mobile_drawer'  => 'boolean',
            'show_mobile_bottom'  => 'boolean',
            'requires_auth'       => 'boolean',
            'required_capability' => 'nullable|string|max:100',
            'is_enabled'          => 'boolean',
        ]);
    }

    /**
     * Enforce maximum placement limits (Desktop Max 10, Mobile Bottom Max 5).
     */
    protected function enforcePlacementLimits(int $storeId, array $validated, ?int $ignoreId = null): void
    {
        if (!empty($validated['show_desktop']) && !empty($validated['is_enabled'])) {
            $desktopCount = StorefrontNavigationItem::where('store_id', $storeId)
                ->where('show_desktop', true)
                ->where('is_enabled', true)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->count();

            if ($desktopCount >= 10) {
                // Desktop maximum 10 items
                abort(422, __('messages.nav_desktop_limit_exceeded', ['max' => 10]));
            }
        }

        if (!empty($validated['show_mobile_bottom']) && !empty($validated['is_enabled'])) {
            $bottomCount = StorefrontNavigationItem::where('store_id', $storeId)
                ->where('show_mobile_bottom', true)
                ->where('is_enabled', true)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->count();

            if ($bottomCount >= 5) {
                // Mobile bottom maximum 5 items
                abort(422, __('messages.nav_bottom_limit_exceeded', ['max' => 5]));
            }
        }
    }
}
