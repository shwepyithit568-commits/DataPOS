<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StorefrontPage;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorefrontPageController extends Controller
{
    /**
     * Reserved slugs that cannot be used as custom page URLs.
     */
    public const RESERVED_SLUGS = [
        'home', 'admin', 'pos', 'products', 'browse', 'cart', 'checkout', 'login', 'register',
        'logout', 'order-builder', 'glass-finder', 'service-tracking', 'how-to-order', 'blog',
        'account', 'api', 'dashboard', 'settings', 'reports', 'terms', 'privacy',
    ];

    /**
     * Display a listing of custom storefront pages.
     */
    public function index(Request $request, string $store_slug, StoreContext $context): View
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $query = StorefrontPage::where('store_id', $store->id)
            ->withCount('navigationItems')
            ->with(['creator', 'updater']);

        // Status filter
        $status = $request->get('status', 'all');
        if ($status === 'published') {
            $query->published();
        } elseif ($status === 'draft') {
            $query->where('status', 'draft');
        }

        // Search query
        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title_my', 'like', "%{$search}%")
                  ->orWhere('title_en', 'like', "%{$search}%")
                  ->orWhere('title_zh_cn', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $pages = $query->latest()->get();

        // Calculate KPI Statistics
        $allPages = StorefrontPage::where('store_id', $store->id)->get();
        $stats = [
            'total'          => $allPages->count(),
            'published_count'=> $allPages->filter(fn (StorefrontPage $p) => $p->isPublished())->count(),
            'draft_count'    => $allPages->where('status', 'draft')->count(),
            'linked_count'   => StorefrontPage::where('store_id', $store->id)->has('navigationItems')->count(),
        ];

        return view('admin.pages.index', compact('store', 'pages', 'stats', 'status', 'search'));
    }

    /**
     * Show the form for creating a new custom page.
     */
    public function create(Request $request, string $store_slug, StoreContext $context): View
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $page = new StorefrontPage([
            'status'       => 'draft',
            'is_enabled'   => true,
            'published_at' => Carbon::now(),
        ]);

        return view('admin.pages.form', compact('store', 'page'));
    }

    /**
     * Store a newly created page in storage.
     */
    public function store(Request $request, string $store_slug, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $validated = $this->validatePage($request, $store->id);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title_en'] ?: $validated['title_my']);
        }

        // Handle image upload
        if ($request->hasFile('featured_image')) {
            $validated['featured_image_path'] = $request->file('featured_image')->store('storefront/pages', 'public');
        }

        $validated['store_id'] = $store->id;
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = Carbon::now();
        }

        StorefrontPage::create($validated);

        return redirect()->route('store.admin.pages.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.saved_successfully'));
    }

    /**
     * Show the form for editing the specified page.
     */
    public function edit(Request $request, string $store_slug, int $id, StoreContext $context): View
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $page = StorefrontPage::where('store_id', $store->id)->findOrFail($id);

        return view('admin.pages.form', compact('store', 'page'));
    }

    /**
     * Update the specified page in storage.
     */
    public function update(Request $request, string $store_slug, int $id, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $page = StorefrontPage::where('store_id', $store->id)->findOrFail($id);
        $validated = $this->validatePage($request, $store->id, $page->id);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title_en'] ?: $validated['title_my']);
        }

        // Handle image upload or removal
        if ($request->boolean('remove_featured_image')) {
            if ($page->featured_image_path) {
                Storage::disk('public')->delete($page->featured_image_path);
            }
            $validated['featured_image_path'] = null;
        } elseif ($request->hasFile('featured_image')) {
            if ($page->featured_image_path) {
                Storage::disk('public')->delete($page->featured_image_path);
            }
            $validated['featured_image_path'] = $request->file('featured_image')->store('storefront/pages', 'public');
        }

        $validated['updated_by'] = Auth::id();

        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = Carbon::now();
        }

        $page->update($validated);

        return redirect()->route('store.admin.pages.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.saved_successfully'));
    }

    /**
     * Remove the specified page from storage.
     */
    public function destroy(Request $request, string $store_slug, int $id, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $page = StorefrontPage::where('store_id', $store->id)->withCount('navigationItems')->findOrFail($id);

        if ($page->navigation_items_count > 0) {
            return back()->with('error', __('messages.page_linked_in_navigation_error', [
                'count' => $page->navigation_items_count,
            ]));
        }

        if ($page->featured_image_path) {
            Storage::disk('public')->delete($page->featured_image_path);
        }

        $page->delete();

        return redirect()->route('store.admin.pages.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.deleted_successfully'));
    }

    /**
     * Quick toggle draft/published status.
     */
    public function toggleStatus(Request $request, string $store_slug, int $id, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $page = StorefrontPage::where('store_id', $store->id)->findOrFail($id);
        if ($page->status === 'published') {
            $page->status = 'draft';
        } else {
            $page->status = 'published';
            if (!$page->published_at) {
                $page->published_at = Carbon::now();
            }
        }
        $page->save();

        return back()->with('success', __('messages.saved_successfully'));
    }

    /**
     * Export custom pages to Excel or CSV.
     */
    public function export(Request $request, string $store_slug, string $format, StoreContext $context): StreamedResponse|Response
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $pages = StorefrontPage::where('store_id', $store->id)->latest()->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Storefront Pages');

        $headers = [
            'ID',
            'Slug',
            'Title (MY)',
            'Title (EN)',
            'Title (ZH)',
            'Status',
            'Published At',
            'Enabled',
            'Created At',
        ];
        $sheet->fromArray([$headers], null, 'A1');

        $rowNum = 2;
        foreach ($pages as $p) {
            $data = [
                $p->id,
                $p->slug,
                $p->title_my,
                $p->title_en,
                $p->title_zh_cn,
                ucfirst($p->status),
                $p->published_at?->format('Y-m-d H:i:s') ?? '-',
                $p->is_enabled ? 'Yes' : 'No',
                $p->created_at?->format('Y-m-d H:i:s') ?? '-',
            ];
            $sheet->fromArray([$data], null, 'A' . $rowNum);
            $rowNum++;
        }

        $filename = 'Storefront_Pages_' . $store->slug . '_' . date('Y-m-d_His');

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
     * Validate incoming page payload.
     */
    protected function validatePage(Request $request, int $storeId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'title_my'               => 'required|string|max:255',
            'title_en'               => 'required|string|max:255',
            'title_zh_cn'            => 'nullable|string|max:255',
            'slug'                   => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'not_in:' . implode(',', self::RESERVED_SLUGS),
                Rule::unique('storefront_pages', 'slug')
                    ->where('store_id', $storeId)
                    ->ignore($ignoreId),
            ],
            'summary_my'             => 'nullable|string|max:500',
            'summary_en'             => 'nullable|string|max:500',
            'summary_zh_cn'          => 'nullable|string|max:500',
            'content_my'             => 'nullable|string',
            'content_en'             => 'nullable|string',
            'content_zh_cn'          => 'nullable|string',
            'featured_image'         => 'nullable|image|max:2048|mimes:jpeg,png,webp,jpg',
            'meta_title_my'          => 'nullable|string|max:255',
            'meta_title_en'          => 'nullable|string|max:255',
            'meta_title_zh_cn'       => 'nullable|string|max:255',
            'meta_description_my'    => 'nullable|string|max:500',
            'meta_description_en'    => 'nullable|string|max:500',
            'meta_description_zh_cn' => 'nullable|string|max:500',
            'status'                 => 'required|string|in:draft,published',
            'published_at'           => 'nullable|date',
            'is_enabled'             => 'boolean',
        ], [
            'slug.regex'  => __('messages.slug_format_hint'),
            'slug.not_in' => __('messages.slug_reserved_error'),
        ]);
    }
}
