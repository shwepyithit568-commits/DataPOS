<?php

use App\Http\Controllers\Admin\AdminAlertController;
use App\Http\Controllers\Admin\AdminBlogController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GlassFinderAdminController;
use App\Http\Controllers\Admin\HomeBannerController;
use App\Http\Controllers\Admin\ImportHistoryController;
use App\Http\Controllers\Admin\OrderAdminController;
use App\Http\Controllers\Admin\PilotImportController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\StoreManagementController;
use App\Http\Controllers\Admin\StoreSettingController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\VariantPresetController;
use App\Http\Controllers\Admin\WholesaleAdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Customer\AccountController;
use App\Http\Controllers\GlassFinderController;
use App\Http\Controllers\HowToOrderController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PushNotificationController;
use App\Http\Controllers\Storefront\BlogController;
use App\Http\Controllers\Storefront\BrowseController;
use App\Http\Controllers\Storefront\CatalogController;
use App\Http\Controllers\Storefront\ReviewController;
use App\Http\Controllers\WholesaleController;
use App\Http\Middleware\EnsureStoreAccess;
use App\Http\Middleware\ResolveStoreContext;
use App\Http\Middleware\SetLocale;
use App\Services\StoreContext;
use Illuminate\Support\Facades\Route;

// Public Storefront Home & Catalog Routes
Route::get('/', function (StoreContext $context) {
    $store = $context->getStore();
    $setting = $store?->setting;
    $banners = $store?->homeBanners()->where('page', 'home')->where('is_active', true)->get() ?? collect();
    // Only categories with products show on the storefront (empty ones are hidden).
    $allCategories = $store ? \App\Models\Category::where('store_id', $store->id)->withCount('products')->get() : collect();
    $categories = $allCategories->filter(fn ($category) => $category->products_count > 0)->values();
    // Main → Sub tree for the homepage "Most Popular Category" strip: a main is
    // listed when it (or any sub) has products; children = subs with products.
    $mainCategoryIds = $allCategories->whereNull('parent_id')->pluck('id');
    // Representative cover photo per main category: the newest featured (or
    // latest) product's image — product image, else its default variant's image.
    $coverByCategory = $mainCategoryIds->isNotEmpty()
        ? \App\Models\Product::whereIn('category_id', $mainCategoryIds)
            ->select('id', 'category_id', 'image_path')
            ->with(['variants' => fn ($v) => $v->whereNotNull('image_path')->where('image_path', '!=', '')])
            ->where(fn ($q) => $q->whereNotNull('image_path')->where('image_path', '!=', '')
                ->orWhereHas('variants', fn ($v) => $v->whereNotNull('image_path')->where('image_path', '!=', '')))
            ->orderByDesc('is_featured')
            ->orderByDesc('id')
            ->get()
            ->unique('category_id')
            ->mapWithKeys(fn ($p) => [$p->category_id => $p->variants->first()?->image_path ?: $p->image_path])
            ->all()
        : [];
    $categoryTree = $allCategories
        ->whereNull('parent_id')
        ->map(function ($main) use ($categories, $coverByCategory) {
            $children = $categories
                ->where('parent_id', $main->id)
                ->sortByDesc('products_count')
                ->values();
            return (object) [
                'category' => $main,
                'children' => $children,
                'total' => $main->products_count + $children->sum('products_count'),
                'cover' => $coverByCategory[$main->id] ?? null,
            ];
        })
        ->filter(fn ($row) => $row->category->products_count > 0 || $row->children->isNotEmpty())
        ->sortByDesc('total')
        ->values();
    $featuredProducts = $store
        ? \App\Models\Product::where('store_id', $store->id)
            ->where('is_featured', true)
            ->where('stock_status', 'in_stock')
            ->with(['category', 'brand', 'variants'])
            ->take(10)
            ->get()
        : collect();
    $newArrivals = $store
        ? \App\Models\Product::where('store_id', $store->id)
            ->where('stock_status', 'in_stock')
            ->with(['category', 'brand', 'variants'])
            ->latest()
            ->take(10)
            ->get()
        : collect();

    // Flash-sale deals: active windows first (countdown to the end), then
    // scheduled ones ("starting soon" — countdown to the start).
    $now = now();
    $flashSales = $store
        ? \App\Models\Product::where('store_id', $store->id)
            ->whereNotNull('old_price')
            ->whereColumn('old_price', '>', 'retail_price')
            ->where('stock_status', 'in_stock')
            ->where(fn ($q) => $q->whereNull('sale_starts_at')->orWhere('sale_starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('sale_ends_at')->orWhere('sale_ends_at', '>=', $now))
            ->with(['category', 'brand', 'variants'])
            ->orderByRaw('sale_ends_at IS NULL, sale_ends_at')
            ->orderBy('retail_price')
            ->get()
        : collect();
    $upcomingSales = $store
        ? \App\Models\Product::where('store_id', $store->id)
            ->whereNotNull('old_price')
            ->whereColumn('old_price', '>', 'retail_price')
            ->where('stock_status', 'in_stock')
            ->where('sale_starts_at', '>', $now)
            ->with(['category', 'brand', 'variants'])
            ->orderBy('sale_starts_at')
            ->get()
        : collect();
    // Earliest relevant moment drives the countdown — either the soonest
    // active-sale end or the soonest scheduled start; the label follows.
    $activeTarget = $flashSales->pluck('sale_ends_at')->filter()->min();
    $upcomingTarget = $upcomingSales->pluck('sale_starts_at')->filter()->min();
    if ($upcomingTarget && (! $activeTarget || $upcomingTarget->lt($activeTarget))) {
        $flashTarget = $upcomingTarget;
        $flashTargetStarts = true;
    } else {
        $flashTarget = $activeTarget;
        $flashTargetStarts = false;
    }

    return view('welcome', compact('store', 'setting', 'banners', 'categories', 'categoryTree', 'featuredProducts', 'newArrivals', 'flashSales', 'upcomingSales', 'flashTarget', 'flashTargetStarts'));
})->middleware([ResolveStoreContext::class, SetLocale::class])->middleware('cache.public_page')->name('storefront.home');

// Store-scoped storefront home (e.g. /store/datapos-mobile)
// Resolves the store from the slug and renders the same storefront home.
Route::get('/store/{store_slug}', function (StoreContext $context) {
    $store = $context->getStore();
    $setting = $store?->setting;
    $banners = $store?->homeBanners()->where('page', 'home')->where('is_active', true)->get() ?? collect();
    // Only categories with products show on the storefront (empty ones are hidden).
    $allCategories = $store ? \App\Models\Category::where('store_id', $store->id)->withCount('products')->get() : collect();
    $categories = $allCategories->filter(fn ($category) => $category->products_count > 0)->values();
    // Main → Sub tree for the homepage "Most Popular Category" strip: a main is
    // listed when it (or any sub) has products; children = subs with products.
    $mainCategoryIds = $allCategories->whereNull('parent_id')->pluck('id');
    // Representative cover photo per main category: the newest featured (or
    // latest) product's image — product image, else its default variant's image.
    $coverByCategory = $mainCategoryIds->isNotEmpty()
        ? \App\Models\Product::whereIn('category_id', $mainCategoryIds)
            ->select('id', 'category_id', 'image_path')
            ->with(['variants' => fn ($v) => $v->whereNotNull('image_path')->where('image_path', '!=', '')])
            ->where(fn ($q) => $q->whereNotNull('image_path')->where('image_path', '!=', '')
                ->orWhereHas('variants', fn ($v) => $v->whereNotNull('image_path')->where('image_path', '!=', '')))
            ->orderByDesc('is_featured')
            ->orderByDesc('id')
            ->get()
            ->unique('category_id')
            ->mapWithKeys(fn ($p) => [$p->category_id => $p->variants->first()?->image_path ?: $p->image_path])
            ->all()
        : [];
    $categoryTree = $allCategories
        ->whereNull('parent_id')
        ->map(function ($main) use ($categories, $coverByCategory) {
            $children = $categories
                ->where('parent_id', $main->id)
                ->sortByDesc('products_count')
                ->values();
            return (object) [
                'category' => $main,
                'children' => $children,
                'total' => $main->products_count + $children->sum('products_count'),
                'cover' => $coverByCategory[$main->id] ?? null,
            ];
        })
        ->filter(fn ($row) => $row->category->products_count > 0 || $row->children->isNotEmpty())
        ->sortByDesc('total')
        ->values();
    $featuredProducts = $store
        ? \App\Models\Product::where('store_id', $store->id)
            ->where('is_featured', true)
            ->where('stock_status', 'in_stock')
            ->with(['category', 'brand', 'variants'])
            ->take(10)
            ->get()
        : collect();
    $newArrivals = $store
        ? \App\Models\Product::where('store_id', $store->id)
            ->where('stock_status', 'in_stock')
            ->with(['category', 'brand', 'variants'])
            ->latest()
            ->take(10)
            ->get()
        : collect();

    // Flash-sale deals: active windows first (countdown to the end), then
    // scheduled ones ("starting soon" — countdown to the start).
    $now = now();
    $flashSales = $store
        ? \App\Models\Product::where('store_id', $store->id)
            ->whereNotNull('old_price')
            ->whereColumn('old_price', '>', 'retail_price')
            ->where('stock_status', 'in_stock')
            ->where(fn ($q) => $q->whereNull('sale_starts_at')->orWhere('sale_starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('sale_ends_at')->orWhere('sale_ends_at', '>=', $now))
            ->with(['category', 'brand', 'variants'])
            ->orderByRaw('sale_ends_at IS NULL, sale_ends_at')
            ->orderBy('retail_price')
            ->get()
        : collect();
    $upcomingSales = $store
        ? \App\Models\Product::where('store_id', $store->id)
            ->whereNotNull('old_price')
            ->whereColumn('old_price', '>', 'retail_price')
            ->where('stock_status', 'in_stock')
            ->where('sale_starts_at', '>', $now)
            ->with(['category', 'brand', 'variants'])
            ->orderBy('sale_starts_at')
            ->get()
        : collect();
    // Earliest relevant moment drives the countdown — either the soonest
    // active-sale end or the soonest scheduled start; the label follows.
    $activeTarget = $flashSales->pluck('sale_ends_at')->filter()->min();
    $upcomingTarget = $upcomingSales->pluck('sale_starts_at')->filter()->min();
    if ($upcomingTarget && (! $activeTarget || $upcomingTarget->lt($activeTarget))) {
        $flashTarget = $upcomingTarget;
        $flashTargetStarts = true;
    } else {
        $flashTarget = $activeTarget;
        $flashTargetStarts = false;
    }

    return view('welcome', compact('store', 'setting', 'banners', 'categories', 'categoryTree', 'featuredProducts', 'newArrivals', 'flashSales', 'upcomingSales', 'flashTarget', 'flashTargetStarts'));
})->middleware([ResolveStoreContext::class, SetLocale::class])->middleware('cache.public_page')->name('storefront.store.home');


Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::get('/products', [CatalogController::class, 'index'])->middleware([ResolveStoreContext::class, SetLocale::class])->middleware('cache.public_page');
Route::get('/products/suggestions', [CatalogController::class, 'suggestions'])->middleware([ResolveStoreContext::class, SetLocale::class, 'throttle:60,1']);
Route::get('/store/{store_slug}/product/{slug}', [CatalogController::class, 'show'])->middleware([ResolveStoreContext::class, SetLocale::class])->middleware('cache.public_page');

// AliExpress-style two-pane category browser (left rail + brands/sub-categories panel)
Route::get('/browse', [BrowseController::class, 'index'])->middleware([ResolveStoreContext::class, SetLocale::class])->middleware('cache.public_page');

// Customer product review submission (guest friendly — name + optional phone)
Route::post('/store/{store_slug}/product/{slug}/reviews', [ReviewController::class, 'store'])
    ->middleware([ResolveStoreContext::class, SetLocale::class, 'throttle:reviews']);

// Customer Account System Routes (Protected by Auth)
Route::middleware(['auth', ResolveStoreContext::class, SetLocale::class])->group(function () {
    Route::get('/account', [AccountController::class, 'index']);
    Route::get('/account/orders', [AccountController::class, 'orders']);
    Route::get('/account/orders/{order}', [AccountController::class, 'showOrder']);
    Route::get('/account/favorites', [AccountController::class, 'favorites']);
});

// Customer Order Request Route (Supports Guest & Authenticated Users)
Route::get('/order-builder', [OrderController::class, 'builder'])->middleware([ResolveStoreContext::class, SetLocale::class]);
Route::post('/store/{store_slug}/orders', [OrderController::class, 'store'])->middleware([ResolveStoreContext::class, SetLocale::class, 'throttle:orders']);

// Customer "How to Order / Contact" static guide page
Route::get('/how-to-order', [HowToOrderController::class, 'index'])->middleware([ResolveStoreContext::class, SetLocale::class]);

// Order Confirmation Page (after successful order placement)
Route::get('/store/{store_slug}/orders/{order}/confirmation', [OrderController::class, 'confirmation'])
    ->middleware([ResolveStoreContext::class, SetLocale::class])
    ->name('orders.confirmation');

// Customer Glass Finder Routes
Route::get('/glass-finder', [GlassFinderController::class, 'index'])->middleware([ResolveStoreContext::class, SetLocale::class]);
Route::post('/glass-finder/favorite', [GlassFinderController::class, 'toggleFavorite'])->middleware('throttle:glass_finder_favorite');

// Public Blog Routes
Route::get('/blog', [BlogController::class, 'index'])->middleware([ResolveStoreContext::class, SetLocale::class]);
Route::get('/blog/{slug}', [BlogController::class, 'show'])->middleware([ResolveStoreContext::class, SetLocale::class]);

// Customer Wholesale Application Routes
Route::prefix('store/{store_slug}')
    ->middleware([ResolveStoreContext::class, SetLocale::class, 'auth'])
    ->group(function () {
        Route::get('/wholesale/apply', [WholesaleController::class, 'create']);
        Route::post('/wholesale/apply', [WholesaleController::class, 'store'])->middleware('throttle:5,1');
    });

// Authentication Routes (Guest) — store context resolved so registration
// enrolls the shopper in the store they are signing up at (primary store by
// default; ?store_slug=… or an X-Store-Slug header overrides for multi-store).
Route::middleware(['guest', ResolveStoreContext::class, SetLocale::class])->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:register');
});

// Logout Route (Auth)
Route::post('/logout', [LoginController::class, 'destroy'])->middleware(['auth', SetLocale::class])->name('logout');

// SEO: Dynamic robots.txt (served via route so it uses config('app.url'))
Route::get('/robots.txt', function () {
    $sitemapUrl = url('/sitemap.xml');

    $content = "User-agent: *\n"
        . "Allow: /\n"
        . "Disallow: /admin\n"
        . "Disallow: /store/*/admin\n"
        . "Disallow: /login\n"
        . "Disallow: /register\n"
        . "Disallow: /account\n"
        . "\n"
        . "Sitemap: {$sitemapUrl}\n";

    return response($content, 200, ['Content-Type' => 'text/plain']);
});

// SEO: Sitemap
Route::get('/sitemap.xml', function () {
    $url = config('app.url');

    $pages = [
        ['loc' => $url . '/', 'priority' => '1.0'],
        ['loc' => $url . '/products', 'priority' => '0.9'],
        ['loc' => $url . '/glass-finder', 'priority' => '0.9'],
        ['loc' => $url . '/how-to-order', 'priority' => '0.6'],
        ['loc' => $url . '/blog', 'priority' => '0.7'],
    ];

    // Per-store dynamic URLs (products + blog posts) for every active store.
    $stores = \App\Models\Store::where('is_active', true)->get();

    $storeUrls = [];
    foreach ($stores as $store) {
        \App\Models\Product::where('store_id', $store->id)
            ->where('stock_status', 'in_stock')
            ->pluck('slug')
            ->each(function (string $slug) use (&$storeUrls, $store, $url) {
                $storeUrls[] = ['loc' => $url . '/store/' . $store->slug . '/product/' . $slug, 'priority' => '0.8'];
            });

        \App\Models\Post::published()
            ->where('store_id', $store->id)
            ->pluck('slug')
            ->each(function (string $slug) use (&$storeUrls, $store, $url) {
                $storeUrls[] = ['loc' => $url . '/blog/' . $slug . '?store_slug=' . $store->slug, 'priority' => '0.7'];
            });
    }

    $all = array_merge($pages, $storeUrls);

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    foreach ($all as $page) {
        $xml .= '  <url>' . "\n";
        $xml .= '    <loc>' . e($page['loc']) . '</loc>' . "\n";
        $xml .= '    <priority>' . $page['priority'] . '</priority>' . "\n";
        $xml .= '  </url>' . "\n";
    }

    $xml .= '</urlset>';

    return response($xml, 200, ['Content-Type' => 'application/xml']);
});

// Admin Platform Owner global routes
Route::middleware(['auth', SetLocale::class])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Platform-level Store Management — deliberately NOT store-scoped (no
    // store context needed) and platform_owner only (multi-store-ready plan §6.2).
    Route::middleware('platform_owner')->group(function () {
        Route::get('/stores', [StoreManagementController::class, 'index'])->name('admin.stores.index');
        Route::get('/stores/create', [StoreManagementController::class, 'create'])->name('admin.stores.create');
        Route::post('/stores', [StoreManagementController::class, 'store'])->name('admin.stores.store');
        Route::get('/stores/{store}/edit', [StoreManagementController::class, 'edit'])->name('admin.stores.edit');
        Route::put('/stores/{store}', [StoreManagementController::class, 'update'])->name('admin.stores.update');
        Route::delete('/stores/{store}', [StoreManagementController::class, 'destroy'])->name('admin.stores.destroy');
        Route::post('/stores/{store}/activate', [StoreManagementController::class, 'activate'])->name('admin.stores.activate');
    });
});

// Store scoped admin routes protected by context & access middleware
Route::prefix('store/{store_slug}')
    ->middleware([ResolveStoreContext::class, SetLocale::class, 'auth'])
    ->group(function () {
        // Store Admin Dashboard
        Route::get('/dashboard', function (StoreContext $context) {
            $store = $context->getStore();
            return response()->json([
                'message' => 'Store Dashboard for ' . $store->name,
                'store_id' => $store->id,
            ]);
        })->middleware(EnsureStoreAccess::class . ':store_manager,staff');

        Route::get('/admin/dashboard', [DashboardController::class, 'index'])
            ->name('store.admin.dashboard')
            ->middleware(EnsureStoreAccess::class . ':store_manager,staff');

        // Admin order/wholesale arrival alerts (polled by the admin layout)
        Route::get('/admin/alerts/check', [AdminAlertController::class, 'check'])
            ->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/', function (string $store_slug) {
            return redirect()->route('store.admin.dashboard', ['store_slug' => $store_slug]);
        })
            ->name('store.admin.root')
            ->middleware(EnsureStoreAccess::class . ':store_manager,staff');

        // Admin Store Settings CRUD (split into sidebar sections: general /
        // contact / delivery / how-to-order — see StoreSettingController)
        Route::get('/admin/settings', [StoreSettingController::class, 'edit'])->name('store.admin.settings.edit')->middleware(EnsureStoreAccess::class . ':store_manager');
        Route::get('/admin/settings/{section}', [StoreSettingController::class, 'edit'])->name('store.admin.settings.section')->middleware(EnsureStoreAccess::class . ':store_manager')->whereIn('section', ['general', 'contact', 'delivery', 'how-to-order', 'footer', 'pos']);
        Route::post('/admin/settings', [StoreSettingController::class, 'update'])->middleware(EnsureStoreAccess::class . ':store_manager');

        // Structured payment / delivery method CRUD (store-scoped; managed from
        // the Delivery & Payment settings page)
        Route::post('/admin/settings/payment-methods', [StoreSettingController::class, 'storePaymentMethod'])->name('store.admin.settings.payment-methods.store')->middleware(EnsureStoreAccess::class . ':store_manager');
        Route::put('/admin/settings/payment-methods/{method}', [StoreSettingController::class, 'updatePaymentMethod'])->name('store.admin.settings.payment-methods.update')->middleware(EnsureStoreAccess::class . ':store_manager');
        Route::delete('/admin/settings/payment-methods/{method}', [StoreSettingController::class, 'destroyPaymentMethod'])->name('store.admin.settings.payment-methods.destroy')->middleware(EnsureStoreAccess::class . ':store_manager');
        Route::post('/admin/settings/delivery-methods', [StoreSettingController::class, 'storeDeliveryMethod'])->name('store.admin.settings.delivery-methods.store')->middleware(EnsureStoreAccess::class . ':store_manager');
        Route::put('/admin/settings/delivery-methods/{method}', [StoreSettingController::class, 'updateDeliveryMethod'])->name('store.admin.settings.delivery-methods.update')->middleware(EnsureStoreAccess::class . ':store_manager');
        Route::delete('/admin/settings/delivery-methods/{method}', [StoreSettingController::class, 'destroyDeliveryMethod'])->name('store.admin.settings.delivery-methods.destroy')->middleware(EnsureStoreAccess::class . ':store_manager');

        // Admin Blog CRUD (storefront blog posts)
        Route::get('/admin/blog', [AdminBlogController::class, 'index'])->name('store.admin.blog.index')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/blog/create', [AdminBlogController::class, 'create'])->name('store.admin.blog.create')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::post('/admin/blog', [AdminBlogController::class, 'store'])->name('store.admin.blog.store')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/blog/{post}/edit', [AdminBlogController::class, 'edit'])->name('store.admin.blog.edit')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::put('/admin/blog/{post}', [AdminBlogController::class, 'update'])->name('store.admin.blog.update')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::delete('/admin/blog/{post}', [AdminBlogController::class, 'destroy'])->name('store.admin.blog.destroy')->middleware(EnsureStoreAccess::class . ':store_manager,staff');

        // Admin Product Reviews (moderation)
        Route::get('/admin/reviews', [AdminReviewController::class, 'index'])->name('store.admin.reviews.index')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::patch('/admin/reviews/{review}/approve', [AdminReviewController::class, 'toggleApprove'])->name('store.admin.reviews.approve')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::delete('/admin/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('store.admin.reviews.destroy')->middleware(EnsureStoreAccess::class . ':store_manager,staff');

        // Platform Owner User Management
        Route::get('/admin/users', [UserManagementController::class, 'index'])->name('store.admin.users.index')->middleware(EnsureStoreAccess::class . ':store_manager');
        Route::post('/admin/users', [UserManagementController::class, 'store'])->name('store.admin.users.store')->middleware(EnsureStoreAccess::class . ':store_manager');
        Route::get('/admin/users/{user}/edit', [UserManagementController::class, 'edit'])->name('store.admin.users.edit')->middleware(EnsureStoreAccess::class . ':store_manager');
        Route::put('/admin/users/{user}', [UserManagementController::class, 'update'])->name('store.admin.users.update')->middleware(EnsureStoreAccess::class . ':store_manager');
        Route::patch('/admin/users/{user}/suspend', [UserManagementController::class, 'suspend'])->name('store.admin.users.suspend')->middleware(EnsureStoreAccess::class . ':store_manager');

        // Admin Home Banners CRUD
        Route::get('/admin/banners', [HomeBannerController::class, 'index'])->name('store.admin.banners.index')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::post('/admin/banners', [HomeBannerController::class, 'store'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/banners/{banner}/edit', [HomeBannerController::class, 'edit'])->name('store.admin.banners.edit')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::put('/admin/banners/{banner}', [HomeBannerController::class, 'update'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::delete('/admin/banners/{banner}', [HomeBannerController::class, 'destroy'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');

        // Admin Categories CRUD & Quick Store
        Route::get('/admin/categories', [CategoryController::class, 'index'])->name('store.admin.categories.index')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::post('/admin/categories', [CategoryController::class, 'store'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::post('/admin/categories/quick-store', [CategoryController::class, 'quickStore'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/categories/{category}/edit', [CategoryController::class, 'edit'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::put('/admin/categories/{category}', [CategoryController::class, 'update'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::delete('/admin/categories/{category}', [CategoryController::class, 'destroy'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');

        // Category Excel import / export
        Route::get('/admin/categories/export', [CategoryController::class, 'export'])->name('store.admin.categories.export')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/categories/import', [CategoryController::class, 'importForm'])->name('store.admin.categories.import')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::post('/admin/categories/import', [CategoryController::class, 'import'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::post('/admin/categories/import/confirm', [CategoryController::class, 'confirmImport'])->name('store.admin.categories.import.confirm')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/categories/import-template', [CategoryController::class, 'downloadImportTemplate'])->name('store.admin.categories.import.template')->middleware(EnsureStoreAccess::class . ':store_manager,staff');

        // Admin Brands CRUD & Quick Store
        Route::get('/admin/brands', [BrandController::class, 'index'])->name('store.admin.brands.index')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::post('/admin/brands', [BrandController::class, 'store'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::post('/admin/brands/quick-store', [BrandController::class, 'quickStore'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/brands/{brand}/edit', [BrandController::class, 'edit'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::put('/admin/brands/{brand}', [BrandController::class, 'update'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::delete('/admin/brands/{brand}', [BrandController::class, 'destroy'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');

        // Brand Excel import / export
        Route::get('/admin/brands/export', [BrandController::class, 'export'])->name('store.admin.brands.export')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/brands/import', [BrandController::class, 'importForm'])->name('store.admin.brands.import')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::post('/admin/brands/import', [BrandController::class, 'import'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::post('/admin/brands/import/confirm', [BrandController::class, 'confirmImport'])->name('store.admin.brands.import.confirm')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/brands/import-template', [BrandController::class, 'downloadImportTemplate'])->name('store.admin.brands.import.template')->middleware(EnsureStoreAccess::class . ':store_manager,staff');

        // Admin Variant Presets
        Route::get('/admin/variant-presets', [VariantPresetController::class, 'index'])->name('store.admin.variant-presets.index')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::post('/admin/variant-presets', [VariantPresetController::class, 'store'])->name('store.admin.variant-presets.store')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/variant-presets/{variantPreset}/edit', [VariantPresetController::class, 'edit'])->name('store.admin.variant-presets.edit')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::put('/admin/variant-presets/{variantPreset}', [VariantPresetController::class, 'update'])->name('store.admin.variant-presets.update')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::post('/admin/variant-presets/{variantPreset}/duplicate', [VariantPresetController::class, 'duplicate'])->name('store.admin.variant-presets.duplicate')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::patch('/admin/variant-presets/{variantPreset}/move', [VariantPresetController::class, 'move'])->name('store.admin.variant-presets.move')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::delete('/admin/variant-presets/{variantPreset}', [VariantPresetController::class, 'destroy'])->name('store.admin.variant-presets.destroy')->middleware(EnsureStoreAccess::class . ':store_manager,staff');

        // Admin Products CRUD & Bulk Actions
        Route::get('/admin/products', [ProductController::class, 'index'])->name('store.admin.products.index')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/products/create', [ProductController::class, 'create'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::post('/admin/products', [ProductController::class, 'store'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::post('/admin/products/bulk-stock', [ProductController::class, 'bulkStock'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::post('/admin/products/bulk-prices', [ProductController::class, 'bulkAdjustPrices'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::post('/admin/products/bulk-delete', [ProductController::class, 'bulkDelete'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/products/{product}/edit', [ProductController::class, 'edit'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::put('/admin/products/{product}', [ProductController::class, 'update'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::delete('/admin/products/{product}', [ProductController::class, 'destroy'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::post('/admin/products/{product}/toggle-featured', [ProductController::class, 'toggleFeatured'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::post('/admin/products/{product}/duplicate', [ProductController::class, 'duplicate'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');

        // Admin Product Multiple Image Gallery
        Route::post('/admin/products/{product}/images', [ProductController::class, 'uploadImages'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::post('/admin/products/{product}/images/{image}/primary', [ProductController::class, 'setPrimaryImage'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::delete('/admin/products/{product}/images/{image}', [ProductController::class, 'deleteImage'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');

        // Admin Product Import
        Route::get('/admin/products/import', [ProductController::class, 'importForm'])->name('store.admin.products.import')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/products/export', [ProductController::class, 'export'])->name('store.admin.products.export')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/products/{product}/details', [ProductController::class, 'details'])->name('store.admin.products.details')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/products/import/template', [ProductController::class, 'downloadImportTemplate'])->name('store.admin.products.import.template')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::post('/admin/products/import', [ProductController::class, 'import'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'throttle:imports']);
        Route::post('/admin/products/import/confirm', [ProductController::class, 'confirmImport'])->name('store.admin.products.import.confirm')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'throttle:imports']);

        // Pilot Data Import hub (products / customers / suppliers)
        Route::get('/admin/pilot-import/{tab?}', [PilotImportController::class, 'index'])->name('store.admin.pilot-import.index')->middleware(EnsureStoreAccess::class . ':store_manager,staff')->whereIn('tab', ['products', 'customers', 'suppliers']);
        Route::post('/admin/pilot-import/{tab}', [PilotImportController::class, 'import'])->name('store.admin.pilot-import.import')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'throttle:imports'])->whereIn('tab', ['products', 'customers', 'suppliers']);
        Route::post('/admin/pilot-import/{tab}/confirm', [PilotImportController::class, 'confirmImport'])->name('store.admin.pilot-import.confirm')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'throttle:imports'])->whereIn('tab', ['products', 'customers', 'suppliers']);
        Route::get('/admin/pilot-import/{tab}/template', [PilotImportController::class, 'downloadTemplate'])->name('store.admin.pilot-import.template')->middleware(EnsureStoreAccess::class . ':store_manager,staff')->whereIn('tab', ['products', 'customers', 'suppliers']);

        // Admin Import History
        Route::get('/admin/import-history', [ImportHistoryController::class, 'index'])->name('store.admin.import-history.index')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/import-history/{history}', [ImportHistoryController::class, 'show'])->name('store.admin.import-history.show')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/import-history/{history}/errors', [ImportHistoryController::class, 'downloadErrors'])->name('store.admin.import-history.errors')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::delete('/admin/import-history/{history}', [ImportHistoryController::class, 'destroy'])->name('store.admin.import-history.destroy')->middleware(EnsureStoreAccess::class . ':store_manager,staff');

        // Database backups
        Route::get('/admin/backups', [BackupController::class, 'index'])->name('store.admin.backups.index')->middleware(EnsureStoreAccess::class . ':store_manager');
        Route::post('/admin/backups', [BackupController::class, 'store'])->name('store.admin.backups.store')->middleware(EnsureStoreAccess::class . ':store_manager');
        Route::get('/admin/backups/{file}/download', [BackupController::class, 'download'])->name('store.admin.backups.download')->middleware(EnsureStoreAccess::class . ':store_manager');
        Route::delete('/admin/backups/{file}', [BackupController::class, 'destroy'])->name('store.admin.backups.destroy')->middleware(EnsureStoreAccess::class . ':store_manager');

        // Admin Wholesale Applications Management
        Route::get('/admin/wholesale/applications', [WholesaleAdminController::class, 'index'])->name('store.admin.wholesale.applications.index')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::patch('/admin/wholesale/applications/{application}', [WholesaleAdminController::class, 'updateStatus'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');

        // Admin Glass Finder Management
        Route::get('/admin/glass-finder', [GlassFinderAdminController::class, 'index'])->name('store.admin.glass-finder.index')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::post('/admin/glass-finder', [GlassFinderAdminController::class, 'store'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/glass-finder/{item}/edit', [GlassFinderAdminController::class, 'edit'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::put('/admin/glass-finder/{item}', [GlassFinderAdminController::class, 'update'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/glass-finder/import/template', [GlassFinderAdminController::class, 'downloadImportTemplate'])->name('store.admin.glass-finder.import.template')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::post('/admin/glass-finder/import', [GlassFinderAdminController::class, 'import'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'throttle:imports']);
        Route::post('/admin/glass-finder/import/confirm', [GlassFinderAdminController::class, 'confirmImport'])->name('store.admin.glass-finder.import.confirm')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'throttle:imports']);
        Route::delete('/admin/glass-finder/{item}', [GlassFinderAdminController::class, 'destroy'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');

        // Admin Order Requests Management
        Route::get('/admin/orders', [OrderAdminController::class, 'index'])->name('store.admin.orders.index')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/orders/export', [OrderAdminController::class, 'export'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/orders/{order}', [OrderAdminController::class, 'show'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/orders/{order}/invoice', [OrderAdminController::class, 'invoice'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::patch('/admin/orders/{order}/status', [OrderAdminController::class, 'updateStatus'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::patch('/admin/orders/{order}/finances', [OrderAdminController::class, 'updateFinances'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::patch('/admin/orders/{order}/note', [OrderAdminController::class, 'updateNote'])->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::delete('/admin/orders/{order}', [OrderAdminController::class, 'destroy'])->middleware(EnsureStoreAccess::class . ':store_manager');

        // Admin Web Push management (subscriber count, test/custom send, history)
        Route::get('/admin/push', [PushNotificationController::class, 'index'])->name('store.admin.push.index')->middleware(EnsureStoreAccess::class . ':store_manager,staff');
        Route::get('/admin/push/history', [PushNotificationController::class, 'history'])->name('store.admin.push.history')->middleware(EnsureStoreAccess::class . ':store_manager,staff');

        // POS module — cashier shifts + opening cash (target-design §2.10).
        // Statically registered, store-scoped, staff/store_manager only.
        Route::prefix('/pos')->middleware(EnsureStoreAccess::class . ':store_manager,staff')->group(function () {
            Route::get('/', [\App\POS\Http\Controllers\CashierShiftController::class, 'index'])->name('pos.index');
            Route::post('/shifts', [\App\POS\Http\Controllers\CashierShiftController::class, 'open'])->name('pos.shifts.open');
            Route::post('/shifts/{shift}/cash-events', [\App\POS\Http\Controllers\CashierShiftController::class, 'cashEvent'])->name('pos.shifts.cash-event');
            Route::post('/shifts/{shift}/close', [\App\POS\Http\Controllers\CashierShiftController::class, 'close'])->name('pos.shifts.close');

            // POS cart + sale posting (target-design §2.8).
            Route::get('/products', [\App\POS\Http\Controllers\PosSaleController::class, 'search'])->name('pos.products.search');
            Route::get('/products-grid', [\App\POS\Http\Controllers\PosSaleController::class, 'grid'])->name('pos.products.grid');
            Route::get('/cart-state', [\App\POS\Http\Controllers\PosSaleController::class, 'cartState'])->name('pos.cart-state');

            // POS customer credit/debt (SoT §17) — search, quick-add, collect debt.
            Route::get('/customers', [\App\POS\Http\Controllers\PosSaleController::class, 'customers'])->name('pos.customers.search');
            Route::post('/customers', [\App\POS\Http\Controllers\PosSaleController::class, 'addCustomer'])->name('pos.customers.add');
            Route::post('/customers/{customer}/attach', [\App\POS\Http\Controllers\PosSaleController::class, 'attachCustomer'])->name('pos.customers.attach');
            Route::post('/customers/detach', [\App\POS\Http\Controllers\PosSaleController::class, 'detachCustomer'])->name('pos.customers.detach');
            Route::post('/customers/{customer}/collect', [\App\POS\Http\Controllers\PosSaleController::class, 'collect'])->name('pos.customers.collect');
            Route::post('/cart', [\App\POS\Http\Controllers\PosSaleController::class, 'addItem'])->name('pos.cart.add');
            // /cart/clear must be registered before /cart/{line} (route order).
            Route::post('/cart/clear', [\App\POS\Http\Controllers\PosSaleController::class, 'clearCart'])->name('pos.cart.clear');
            Route::post('/cart/{line}', [\App\POS\Http\Controllers\PosSaleController::class, 'updateLine'])->name('pos.cart.update');
            Route::delete('/cart/{line}', [\App\POS\Http\Controllers\PosSaleController::class, 'removeLine'])->name('pos.cart.remove');
            Route::post('/hold', [\App\POS\Http\Controllers\PosSaleController::class, 'hold'])->name('pos.hold');
            Route::post('/resume/{sale}', [\App\POS\Http\Controllers\PosSaleController::class, 'resume'])->name('pos.resume');
            Route::post('/void/{sale}', [\App\POS\Http\Controllers\PosSaleController::class, 'void'])->name('pos.void');
            Route::post('/post/{sale?}', [\App\POS\Http\Controllers\PosSaleController::class, 'post'])->name('pos.post');
            Route::get('/sales/{sale}/receipt', [\App\POS\Http\Controllers\PosSaleController::class, 'receipt'])->name('pos.receipt');

            // POS returns / refunds (target-design §2.9, SoT §15.1).
            Route::get('/sales/{sale}/refund', [\App\POS\Http\Controllers\PosReturnController::class, 'create'])->name('pos.refund.create');
            Route::post('/sales/{sale}/refunds', [\App\POS\Http\Controllers\PosReturnController::class, 'store'])->name('pos.refund.store');

            // Branch daily closing (SoT §18) — view/create by staff, approve by manager.
            Route::get('/closing', [\App\POS\Http\Controllers\DailyClosingController::class, 'index'])->name('pos.closing.index');
            Route::post('/closing', [\App\POS\Http\Controllers\DailyClosingController::class, 'store'])->name('pos.closing.store');
            Route::post('/closing/{closing}/approve', [\App\POS\Http\Controllers\DailyClosingController::class, 'approve'])->name('pos.closing.approve')
                ->middleware(EnsureStoreAccess::class . ':store_manager');

            // Minimal reports (target-design §2.10) — sales / cash drawer / stock.
            Route::get('/reports/sales', [\App\POS\Http\Controllers\PosReportController::class, 'sales'])->name('pos.reports.sales');
            Route::get('/reports/cash', [\App\POS\Http\Controllers\PosReportController::class, 'cash'])->name('pos.reports.cash');
            Route::get('/reports/stock', [\App\POS\Http\Controllers\PosReportController::class, 'stock'])->name('pos.reports.stock');

            // Simple stock receiving (MVP Phase 2) — goods receipt → purchase_received ledger.
            Route::get('/receiving', [\App\POS\Http\Controllers\GoodsReceiptController::class, 'index'])->name('pos.receiving.index');
            Route::post('/receiving', [\App\POS\Http\Controllers\GoodsReceiptController::class, 'store'])->name('pos.receiving.store');

            // Opening stock (MVP Phase 2) — staff submits, manager approves → opening_balance ledger.
            Route::get('/opening-stock', [\App\POS\Http\Controllers\OpeningStockController::class, 'index'])->name('pos.opening-stock.index');
            Route::post('/opening-stock', [\App\POS\Http\Controllers\OpeningStockController::class, 'store'])->name('pos.opening-stock.store');
            Route::post('/opening-stock/{openingStockRequest}/approve', [\App\POS\Http\Controllers\OpeningStockController::class, 'approve'])->name('pos.opening-stock.approve')
                ->middleware(EnsureStoreAccess::class . ':store_manager');
            Route::post('/opening-stock/{openingStockRequest}/reject', [\App\POS\Http\Controllers\OpeningStockController::class, 'reject'])->name('pos.opening-stock.reject')
                ->middleware(EnsureStoreAccess::class . ':store_manager');

            // Inventory adjustments (MVP Phase 2, final) — staff submits, manager approves → adjustment_in/out ledger.
            Route::get('/adjustments', [\App\POS\Http\Controllers\InventoryAdjustmentController::class, 'index'])->name('pos.adjustments.index');
            Route::post('/adjustments', [\App\POS\Http\Controllers\InventoryAdjustmentController::class, 'store'])->name('pos.adjustments.store');
            Route::post('/adjustments/{inventoryAdjustment}/approve', [\App\POS\Http\Controllers\InventoryAdjustmentController::class, 'approve'])->name('pos.adjustments.approve')
                ->middleware(EnsureStoreAccess::class . ':store_manager');
            Route::post('/adjustments/{inventoryAdjustment}/reject', [\App\POS\Http\Controllers\InventoryAdjustmentController::class, 'reject'])->name('pos.adjustments.reject')
                ->middleware(EnsureStoreAccess::class . ':store_manager');
        });
    });
