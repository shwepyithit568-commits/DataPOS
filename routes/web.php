<?php

use App\Http\Controllers\Admin\AdminAlertController;
use App\Http\Controllers\Admin\AdminBlogController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ComingSoonController;
use App\Http\Controllers\Admin\CustomerDirectoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExpenseCategoryController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\GlassFinderAdminController;
use App\Http\Controllers\Admin\HomeBannerController;
use App\Http\Controllers\Admin\ImportHistoryController;
use App\Http\Controllers\Admin\OrderAdminController;
use App\Http\Controllers\Admin\PilotImportController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductMasterDataController;
use App\Http\Controllers\Admin\RepairController;
use App\Http\Controllers\Admin\ServiceSettingController;
use App\Http\Controllers\Admin\SparePartController;
use App\Http\Controllers\Admin\StoreManagementController;
use App\Http\Controllers\Admin\ThemeGovernanceController;
use App\Http\Controllers\Admin\StoreSettingController;
use App\Http\Controllers\Admin\StoreModuleController;
use App\Http\Controllers\Admin\StoreChannelController;
use App\Http\Controllers\Admin\AppearanceDraftController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\WarehouseController;
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
use App\Http\Controllers\Admin\StorefrontNavigationController;
use App\Http\Controllers\Admin\StorefrontPageController;
use App\Http\Controllers\Storefront\BlogController;
use App\Http\Controllers\Storefront\BrowseController;
use App\Http\Controllers\Storefront\CatalogController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\ReviewController;
use App\Http\Controllers\WholesaleController;
use App\Http\Middleware\EnsureStoreAccess;
use App\Http\Middleware\ResolveStoreContext;
use App\Http\Middleware\SetLocale;
use App\Services\StoreContext;
use Illuminate\Support\Facades\Route;

// Public Storefront Home & Catalog Routes
Route::get('/', [HomeController::class, 'index'])
    ->middleware([ResolveStoreContext::class, SetLocale::class])
    ->middleware('cache.public_page')
    ->name('storefront.home');

// Store-scoped storefront home (e.g. /store/datapos-mobile)
// Resolves the store from the slug and renders the same storefront home.
Route::get('/store/{store_slug}', [HomeController::class, 'index'])
    ->middleware([ResolveStoreContext::class, SetLocale::class])
    ->middleware('cache.public_page')
    ->name('storefront.store.home');


Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::get('/products', [CatalogController::class, 'index'])->middleware([ResolveStoreContext::class, SetLocale::class, 'store.capability:storefront.ecommerce'])->middleware('cache.public_page');
Route::get('/products/suggestions', [CatalogController::class, 'suggestions'])->middleware([ResolveStoreContext::class, SetLocale::class, 'throttle:60,1']);
Route::get('/store/{store_slug}/product/{slug}', [CatalogController::class, 'show'])->name('storefront.product')->middleware([ResolveStoreContext::class, SetLocale::class, 'store.capability:storefront.ecommerce'])->middleware('cache.public_page');

// AliExpress-style two-pane category browser (left rail + brands/sub-categories panel)
Route::get('/browse', [BrowseController::class, 'index'])->middleware([ResolveStoreContext::class, SetLocale::class, 'store.capability:storefront.ecommerce'])->middleware('cache.public_page');

// Customer product review submission (guest friendly — name + optional phone)
Route::post('/store/{store_slug}/product/{slug}/reviews', [ReviewController::class, 'store'])
    ->middleware([ResolveStoreContext::class, SetLocale::class, 'store.capability:storefront.reviews', 'throttle:reviews']);

// Customer Account System Routes (Protected by Auth)
Route::middleware(['auth', ResolveStoreContext::class, SetLocale::class, 'store.capability:storefront.customer_portal'])->group(function () {
    Route::get('/account', [AccountController::class, 'index']);
    Route::get('/account/orders', [AccountController::class, 'orders']);
    Route::get('/account/orders/{order}', [AccountController::class, 'showOrder']);
    Route::get('/account/favorites', [AccountController::class, 'favorites']);
});

// Customer Order Request Route (Supports Guest & Authenticated Users)
Route::get('/order-builder', [OrderController::class, 'builder'])->middleware([ResolveStoreContext::class, SetLocale::class, 'store.capability:storefront.online_ordering']);
Route::post('/store/{store_slug}/orders', [OrderController::class, 'store'])->middleware([ResolveStoreContext::class, SetLocale::class, 'store.capability:storefront.online_ordering', 'throttle:orders']);

// Customer "How to Order / Contact" static guide page
Route::get('/how-to-order', [HowToOrderController::class, 'index'])->middleware([ResolveStoreContext::class, SetLocale::class, 'store.capability:storefront.online_ordering']);

// Order Confirmation Page (after successful order placement)
Route::get('/store/{store_slug}/orders/{order}/confirmation', [OrderController::class, 'confirmation'])
    ->middleware([ResolveStoreContext::class, SetLocale::class, 'store.capability:storefront.online_ordering'])
    ->name('orders.confirmation');

// Customer Glass Finder Routes
Route::get('/glass-finder', [GlassFinderController::class, 'index'])->middleware([ResolveStoreContext::class, SetLocale::class, 'store.capability:storefront.glass_finder']);
Route::post('/glass-finder/favorite', [GlassFinderController::class, 'toggleFavorite'])->middleware([ResolveStoreContext::class, SetLocale::class, 'store.capability:storefront.glass_finder', 'throttle:glass_finder_favorite']);

// Customer Service Job Live Tracking Routes (Login-free status tracking via token or lookup)
Route::get('/service-tracking', [\App\Http\Controllers\Storefront\ServiceTrackingController::class, 'index'])
    ->middleware([ResolveStoreContext::class, SetLocale::class, 'store.capability:service.repair_jobs'])
    ->name('storefront.service.track.index');
Route::get('/store/{store_slug}/track/service', [\App\Http\Controllers\Storefront\ServiceTrackingController::class, 'index'])
    ->middleware([ResolveStoreContext::class, SetLocale::class, 'store.capability:service.repair_jobs'])
    ->name('storefront.service.track.store');
Route::get('/store/{store_slug}/track/service/{token}', [\App\Http\Controllers\Storefront\ServiceTrackingController::class, 'show'])
    ->middleware([ResolveStoreContext::class, SetLocale::class, 'store.capability:service.repair_jobs'])
    ->name('storefront.service.track.token');

// Public Blog Routes
Route::get('/blog', [BlogController::class, 'index'])->middleware([ResolveStoreContext::class, SetLocale::class, 'store.capability:storefront.blog']);
Route::get('/blog/{slug}', [BlogController::class, 'show'])->middleware([ResolveStoreContext::class, SetLocale::class, 'store.capability:storefront.blog']);

// Public Custom Pages Route
Route::get('/store/{store_slug}/page/{slug}', [PageController::class, 'show'])
    ->middleware([ResolveStoreContext::class, SetLocale::class])
    ->middleware('cache.public_page')
    ->name('storefront.page');

// Customer Wholesale Application Routes
Route::prefix('store/{store_slug}')
    ->middleware([ResolveStoreContext::class, SetLocale::class, 'auth', 'store.capability:commerce.wholesale_pricing'])
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

// Quick Login — passwordless dev-only route (env-gated, outside guest group)
Route::match(['GET', 'POST'], '/quick-login', [LoginController::class, 'quickLogin'])
    ->middleware([ResolveStoreContext::class, SetLocale::class])
    ->name('quick-login');

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

// Admin Platform Owner global routes (Strictly Platform Owner Only)
Route::middleware(['auth', SetLocale::class, 'platform_owner'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Platform-level Store Management
    Route::get('/stores', [StoreManagementController::class, 'index'])->name('admin.stores.index');
    Route::get('/stores/create', [StoreManagementController::class, 'create'])->name('admin.stores.create');
    Route::post('/stores', [StoreManagementController::class, 'store'])->name('admin.stores.store');
    Route::get('/stores/{store}/edit', [StoreManagementController::class, 'edit'])->name('admin.stores.edit');
    Route::put('/stores/{store}', [StoreManagementController::class, 'update'])->name('admin.stores.update');
    Route::delete('/stores/{store}', [StoreManagementController::class, 'destroy'])->name('admin.stores.destroy');
    Route::delete('/stores/{store}/force', [StoreManagementController::class, 'forceDestroy'])->name('admin.stores.force-destroy');
    Route::post('/stores/{store}/activate', [StoreManagementController::class, 'activate'])->name('admin.stores.activate');

    // Platform-level Theme Governance (T7): theme lifecycle management
    Route::get('/theme-governance', [ThemeGovernanceController::class, 'index'])->name('admin.theme-governance.index');
    Route::post('/theme-governance', [ThemeGovernanceController::class, 'update'])->name('admin.theme-governance.update');

    // Support Mode for Platform Owners
    Route::post('/support-mode/enter', [\App\Http\Controllers\Admin\SupportModeController::class, 'enter'])->name('admin.support-mode.enter');
    Route::post('/support-mode/exit', [\App\Http\Controllers\Admin\SupportModeController::class, 'exit'])->name('admin.support-mode.exit');
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

        // Business Modules & Sales Channels Settings (Store Owner & Platform Owner Only)
        Route::get('/admin/settings/modules', [StoreModuleController::class, 'index'])->name('store.admin.modules.index')->middleware([EnsureStoreAccess::class . ':store_owner', 'store.permission:modules.manage']);
        Route::post('/admin/settings/modules/toggle', [StoreModuleController::class, 'toggle'])->name('store.admin.modules.toggle')->middleware([EnsureStoreAccess::class . ':store_owner', 'store.permission:modules.manage']);
        Route::get('/admin/settings/channels', [StoreChannelController::class, 'index'])->name('store.admin.channels.index')->middleware([EnsureStoreAccess::class . ':store_owner', 'store.permission:channels.manage']);
        Route::post('/admin/settings/channels/toggle', [StoreChannelController::class, 'toggle'])->name('store.admin.channels.toggle')->middleware([EnsureStoreAccess::class . ':store_owner', 'store.permission:channels.manage']);

        // Admin Store Settings CRUD (split into sidebar sections: general /
        // contact / delivery / how-to-order — see StoreSettingController)
        Route::get('/admin/settings', [StoreSettingController::class, 'edit'])->name('store.admin.settings.edit')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.view']);
        Route::get('/admin/settings/{section}', [StoreSettingController::class, 'edit'])->name('store.admin.settings.section')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.view'])->whereIn('section', ['general', 'currency', 'appearance', 'theme', 'contact', 'delivery', 'how-to-order', 'footer', 'pos']);
        Route::get('/admin/theme', fn (string $store_slug) => redirect()->route('store.admin.settings.section', ['store_slug' => $store_slug, 'section' => 'appearance']))->name('store.admin.theme.index')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.view']);
        Route::get('/admin/export-data', [StoreSettingController::class, 'exportData'])->name('store.admin.settings.export-data')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.view']);
        Route::post('/admin/settings', [StoreSettingController::class, 'update'])->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.update']);
        Route::post('/admin/settings/appearance/revisions/{revision}/rollback', [StoreSettingController::class, 'rollbackTheme'])
            ->name('store.admin.settings.appearance.rollback')
            ->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.update']);

        // Theme Draft API — draft save, publish, and discard (JSON endpoints)
        // These are deliberately separate from the form-based settings routes so
        // the draft path and the published-settings path never share a code route.
        Route::middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.update'])->group(function () {
            Route::get('/admin/appearance/draft',    [AppearanceDraftController::class, 'show'])
                ->name('store.admin.appearance.draft.show');
            Route::post('/admin/appearance/draft',   [AppearanceDraftController::class, 'save'])
                ->name('store.admin.appearance.draft.save');
            Route::post('/admin/appearance/publish', [AppearanceDraftController::class, 'publish'])
                ->name('store.admin.appearance.publish');
            Route::delete('/admin/appearance/draft', [AppearanceDraftController::class, 'discard'])
                ->name('store.admin.appearance.draft.discard');
            // Isolated preview — renders the production storefront with the draft
            // config (no-store/private/noindex), never touching the live storefront.
            Route::get('/admin/appearance/preview', [AppearanceDraftController::class, 'preview'])
                ->name('store.admin.appearance.preview');
        });

        // Structured payment / delivery method CRUD (store-scoped; managed from
        // the Delivery & Payment settings page)
        Route::post('/admin/settings/payment-methods', [StoreSettingController::class, 'storePaymentMethod'])->name('store.admin.settings.payment-methods.store')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.update']);
        Route::put('/admin/settings/payment-methods/{method}', [StoreSettingController::class, 'updatePaymentMethod'])->name('store.admin.settings.payment-methods.update')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.update']);
        Route::delete('/admin/settings/payment-methods/{method}', [StoreSettingController::class, 'destroyPaymentMethod'])->name('store.admin.settings.payment-methods.destroy')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.delete']);
        Route::post('/admin/settings/delivery-methods', [StoreSettingController::class, 'storeDeliveryMethod'])->name('store.admin.settings.delivery-methods.store')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.update']);
        Route::put('/admin/settings/delivery-methods/{method}', [StoreSettingController::class, 'updateDeliveryMethod'])->name('store.admin.settings.delivery-methods.update')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.update']);
        Route::delete('/admin/settings/delivery-methods/{method}', [StoreSettingController::class, 'destroyDeliveryMethod'])->name('store.admin.settings.delivery-methods.destroy')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.delete']);

        // Admin Storefront Navigation Management (tabs, mobile drawer, mobile bottom bar)
        Route::middleware(EnsureStoreAccess::class . ':store_manager')->group(function () {
            Route::get('/admin/navigation', [StorefrontNavigationController::class, 'index'])->name('store.admin.navigation.index')->middleware('store.permission:navigation.view');
            Route::get('/admin/navigation/create', [StorefrontNavigationController::class, 'create'])->name('store.admin.navigation.create')->middleware('store.permission:navigation.create');
            Route::post('/admin/navigation', [StorefrontNavigationController::class, 'store'])->name('store.admin.navigation.store')->middleware('store.permission:navigation.create');
            Route::get('/admin/navigation/{id}/edit', [StorefrontNavigationController::class, 'edit'])->name('store.admin.navigation.edit')->middleware('store.permission:navigation.update');
            Route::put('/admin/navigation/{id}', [StorefrontNavigationController::class, 'update'])->name('store.admin.navigation.update')->middleware('store.permission:navigation.update');
            Route::delete('/admin/navigation/{id}', [StorefrontNavigationController::class, 'destroy'])->name('store.admin.navigation.destroy')->middleware('store.permission:navigation.delete');
            Route::post('/admin/navigation/{id}/reorder/{direction}', [StorefrontNavigationController::class, 'reorder'])->name('store.admin.navigation.reorder')->whereIn('direction', ['up', 'down'])->middleware('store.permission:navigation.update');
            Route::post('/admin/navigation/{id}/toggle', [StorefrontNavigationController::class, 'toggleStatus'])->name('store.admin.navigation.toggle')->middleware('store.permission:navigation.update');
            Route::post('/admin/navigation/reset-defaults', [StorefrontNavigationController::class, 'resetDefaults'])->name('store.admin.navigation.reset_defaults')->middleware('store.permission:navigation.update');
            Route::get('/admin/navigation/export/{format}', [StorefrontNavigationController::class, 'export'])->name('store.admin.navigation.export')->whereIn('format', ['xlsx', 'csv'])->middleware('store.permission:navigation.export');
        });

        // Admin Custom Storefront Pages CRUD
        Route::middleware(EnsureStoreAccess::class . ':store_manager,staff')->group(function () {
            Route::get('/admin/pages', [StorefrontPageController::class, 'index'])->name('store.admin.pages.index')->middleware('store.permission:pages.view');
            Route::get('/admin/pages/create', [StorefrontPageController::class, 'create'])->name('store.admin.pages.create')->middleware('store.permission:pages.create');
            Route::post('/admin/pages', [StorefrontPageController::class, 'store'])->name('store.admin.pages.store')->middleware('store.permission:pages.create');
            Route::get('/admin/pages/{id}/edit', [StorefrontPageController::class, 'edit'])->name('store.admin.pages.edit')->middleware('store.permission:pages.update');
            Route::put('/admin/pages/{id}', [StorefrontPageController::class, 'update'])->name('store.admin.pages.update')->middleware('store.permission:pages.update');
            Route::delete('/admin/pages/{id}', [StorefrontPageController::class, 'destroy'])->name('store.admin.pages.destroy')->middleware('store.permission:pages.delete');
            Route::post('/admin/pages/{id}/toggle', [StorefrontPageController::class, 'toggleStatus'])->name('store.admin.pages.toggle')->middleware('store.permission:pages.update');
            Route::get('/admin/pages/export/{format}', [StorefrontPageController::class, 'export'])->name('store.admin.pages.export')->whereIn('format', ['xlsx', 'csv'])->middleware('store.permission:pages.export');
        });

        // Admin Blog CRUD (storefront blog posts)
        Route::middleware(['store.capability:storefront.blog', 'store.channel:online_store'])->group(function () {
            Route::get('/admin/blog', [AdminBlogController::class, 'index'])->name('store.admin.blog.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:blog.view']);
            Route::get('/admin/blog/create', [AdminBlogController::class, 'create'])->name('store.admin.blog.create')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:blog.create']);
            Route::post('/admin/blog', [AdminBlogController::class, 'store'])->name('store.admin.blog.store')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:blog.create']);
            Route::get('/admin/blog/{post}/edit', [AdminBlogController::class, 'edit'])->name('store.admin.blog.edit')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:blog.update']);
            Route::put('/admin/blog/{post}', [AdminBlogController::class, 'update'])->name('store.admin.blog.update')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:blog.update']);
            Route::delete('/admin/blog/{post}', [AdminBlogController::class, 'destroy'])->name('store.admin.blog.destroy')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:blog.delete']);
        });

        // Customer Directory
        Route::get('/admin/customers', [CustomerDirectoryController::class, 'index'])->name('store.admin.customers.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:customers.view']);
        Route::post('/admin/customers', [CustomerDirectoryController::class, 'store'])->name('store.admin.customers.store')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:customers.create']);
        Route::get('/admin/customers/export', [CustomerDirectoryController::class, 'exportCsv'])->name('store.admin.customers.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:customers.export']);
        Route::get('/admin/customers/{customer}', [CustomerDirectoryController::class, 'show'])->name('store.admin.customers.show')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:customers.view']);
        Route::put('/admin/customers/{customer}', [CustomerDirectoryController::class, 'update'])->name('store.admin.customers.update')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:customers.update']);

        // Customer Receivables & Debt Ledger Management (SoT §17)
        Route::middleware('store.capability:commerce.customer_debt')->group(function () {
            Route::get('/admin/receivables', [\App\Http\Controllers\Admin\CustomerReceivableController::class, 'index'])->name('store.admin.receivables.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:receivables.view']);
            Route::get('/admin/receivables/export', [\App\Http\Controllers\Admin\CustomerReceivableController::class, 'exportCsv'])->name('store.admin.receivables.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:receivables.view']);
            Route::get('/admin/receivables/{customer}', [\App\Http\Controllers\Admin\CustomerReceivableController::class, 'show'])->name('store.admin.receivables.show')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:receivables.view']);
            Route::post('/admin/receivables/{customer}/collect', [\App\Http\Controllers\Admin\CustomerReceivableController::class, 'collect'])->name('store.admin.receivables.collect')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:receivables.update']);
            Route::get('/admin/receivables/{customer}/statement', [\App\Http\Controllers\Admin\CustomerReceivableController::class, 'statement'])->name('store.admin.receivables.statement')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:receivables.view']);
        });

        // Supplier Payables (Accounts Payable)
        Route::get('/admin/payables', fn (StoreContext $context) => redirect()->route('pos.purchases.payables', $context->getRouteParams()))->name('store.admin.payables.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:payables.view']);

        // Profit & Loss Financial Statement (SoT §18)
        Route::get('/admin/profit-loss', [\App\Http\Controllers\Admin\ProfitLossController::class, 'index'])->name('store.admin.profit_loss.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:profit_loss.view']);
        Route::get('/admin/profit-loss/statement', [\App\Http\Controllers\Admin\ProfitLossController::class, 'statement'])->name('store.admin.profit_loss.statement')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:profit_loss.view']);
        Route::get('/admin/profit-loss/export', [\App\Http\Controllers\Admin\ProfitLossController::class, 'export'])->name('store.admin.profit_loss.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:profit_loss.export']);

        // Repair Center / Service Jobs (SoT §16)
        Route::middleware('store.capability:service.repair_jobs')->group(function () {
            Route::get('/admin/repairs', [RepairController::class, 'index'])->name('store.admin.repairs.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.view']);
            Route::get('/admin/repairs/export', [RepairController::class, 'export'])->name('store.admin.repairs.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.view']);
            Route::get('/admin/repairs/create', [RepairController::class, 'create'])->name('store.admin.repairs.create')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.create']);
            Route::post('/admin/repairs', [RepairController::class, 'store'])->name('store.admin.repairs.store')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.create']);
            Route::get('/admin/repairs/{repair}', [RepairController::class, 'show'])->name('store.admin.repairs.show')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.view']);
            Route::get('/admin/repairs/{repair}/print', [RepairController::class, 'printTicket'])->name('store.admin.repairs.print')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.view']);
            Route::get('/admin/repairs/{repair}/edit', [RepairController::class, 'edit'])->name('store.admin.repairs.edit')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.update']);
            Route::put('/admin/repairs/{repair}', [RepairController::class, 'update'])->name('store.admin.repairs.update')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.update']);
            Route::post('/admin/repairs/{repair}/status', [RepairController::class, 'updateStatus'])->name('store.admin.repairs.status')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.update']);
            Route::post('/admin/repairs/quick-add-technician', [RepairController::class, 'quickAddTechnician'])->name('store.admin.repairs.quick_add_technician')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.update']);
            Route::post('/admin/repairs/{repair}/payments', [RepairController::class, 'addPayment'])->name('store.admin.repairs.payments.store')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.update']);
            Route::post('/admin/repairs/{repair}/items/{item}/deduct', [RepairController::class, 'deductItem'])->name('store.admin.repairs.items.deduct')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.update']);

            // Service Settings / Repair Master Data (Tabs for statuses, brands, categories, models, colors, storage, defects, accessories)
            Route::get('/admin/service-settings', [ServiceSettingController::class, 'index'])->name('store.admin.service_settings.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:service_settings.view']);
            Route::get('/admin/service-settings/export', [ServiceSettingController::class, 'export'])->name('store.admin.service_settings.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:service_settings.view']);
            Route::post('/admin/service-settings/import', [ServiceSettingController::class, 'import'])->name('store.admin.service_settings.import')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:service_settings.create']);
            Route::get('/admin/service-settings/template', [ServiceSettingController::class, 'downloadTemplate'])->name('store.admin.service_settings.template')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:service_settings.view']);
            Route::post('/admin/service-settings', [ServiceSettingController::class, 'store'])->name('store.admin.service_settings.store')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:service_settings.create']);
            Route::post('/admin/service-settings/quick-add', [ServiceSettingController::class, 'quickAdd'])->name('store.admin.service_settings.quick_add')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:service_settings.create']);
            Route::put('/admin/service-settings/{service_setting}', [ServiceSettingController::class, 'update'])->name('store.admin.service_settings.update')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:service_settings.update']);
            Route::delete('/admin/service-settings/{service_setting}', [ServiceSettingController::class, 'destroy'])->name('store.admin.service_settings.destroy')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:service_settings.delete']);

            // Spare Parts Used in Repairs (Service Consumption & Stock Tracking)
            Route::get('/admin/spare-parts', [SparePartController::class, 'index'])->name('store.admin.spare_parts.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:spare_parts.view']);
            Route::get('/admin/spare-parts/export', [SparePartController::class, 'export'])->name('store.admin.spare_parts.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:spare_parts.view']);
            Route::post('/admin/spare-parts/{item}/deduct', [SparePartController::class, 'deductItem'])->name('store.admin.spare_parts.deduct')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:spare_parts.update']);

            // Service Jobs (Computer / CCTV / Network — SoT §16-B)
            // SVC-YYYYMMDD-#### numbering, tracking_token for customer page.
            Route::get('/admin/service-jobs', [\App\Http\Controllers\Admin\ServiceJobController::class, 'index'])->name('store.admin.service_jobs.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.view']);
            Route::get('/admin/service-jobs/export', [\App\Http\Controllers\Admin\ServiceJobController::class, 'export'])->name('store.admin.service_jobs.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.view']);
            Route::get('/admin/service-jobs/create', [\App\Http\Controllers\Admin\ServiceJobController::class, 'create'])->name('store.admin.service_jobs.create')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.create']);
            Route::post('/admin/service-jobs', [\App\Http\Controllers\Admin\ServiceJobController::class, 'store'])->name('store.admin.service_jobs.store')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.create']);
            Route::get('/admin/service-jobs/{job}', [\App\Http\Controllers\Admin\ServiceJobController::class, 'show'])->name('store.admin.service_jobs.show')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.view']);
            Route::get('/admin/service-jobs/{job}/print', [\App\Http\Controllers\Admin\ServiceJobController::class, 'printTicket'])->name('store.admin.service_jobs.print')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.view']);
            Route::get('/admin/service-jobs/{job}/edit', [\App\Http\Controllers\Admin\ServiceJobController::class, 'edit'])->name('store.admin.service_jobs.edit')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.update']);
            Route::put('/admin/service-jobs/{job}', [\App\Http\Controllers\Admin\ServiceJobController::class, 'update'])->name('store.admin.service_jobs.update')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.update']);
            Route::post('/admin/service-jobs/{job}/status', [\App\Http\Controllers\Admin\ServiceJobController::class, 'updateStatus'])->name('store.admin.service_jobs.status')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.update']);
            Route::post('/admin/service-jobs/quick-add-technician', [\App\Http\Controllers\Admin\ServiceJobController::class, 'quickAddTechnician'])->name('store.admin.service_jobs.quick_add_technician')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.update']);
            Route::post('/admin/service-jobs/{job}/payments', [\App\Http\Controllers\Admin\ServiceJobController::class, 'addPayment'])->name('store.admin.service_jobs.payments.store')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.update']);
            Route::post('/admin/service-jobs/{job}/items/{item}/deduct', [\App\Http\Controllers\Admin\ServiceJobController::class, 'deductItem'])->name('store.admin.service_jobs.items.deduct')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:repairs.update']);
        });

        // Expense Categories CRUD
        Route::get('/admin/expense-categories', [ExpenseCategoryController::class, 'index'])->name('store.admin.expense_categories.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:expense_categories.view']);
        Route::get('/admin/expense-categories/export', [ExpenseCategoryController::class, 'export'])->name('store.admin.expense_categories.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:expense_categories.view']);
        Route::post('/admin/expense-categories', [ExpenseCategoryController::class, 'store'])->name('store.admin.expense_categories.store')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:expense_categories.create']);
        Route::put('/admin/expense-categories/{category}', [ExpenseCategoryController::class, 'update'])->name('store.admin.expense_categories.update')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:expense_categories.update']);
        Route::patch('/admin/expense-categories/{category}/toggle', [ExpenseCategoryController::class, 'toggle'])->name('store.admin.expense_categories.toggle')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:expense_categories.update']);
        Route::delete('/admin/expense-categories/{category}', [ExpenseCategoryController::class, 'destroy'])->name('store.admin.expense_categories.destroy')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:expense_categories.delete']);

        // Expenses Management (Daily Expenses CRUD & Export)
        Route::get('/admin/expenses', [ExpenseController::class, 'index'])->name('store.admin.expenses.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:expenses.view']);
        Route::get('/admin/expenses/export', [ExpenseController::class, 'export'])->name('store.admin.expenses.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:expenses.view']);
        Route::post('/admin/expenses', [ExpenseController::class, 'store'])->name('store.admin.expenses.store')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:expenses.create']);
        Route::put('/admin/expenses/{expense}', [ExpenseController::class, 'update'])->name('store.admin.expenses.update')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:expenses.update']);
        Route::delete('/admin/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('store.admin.expenses.destroy')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:expenses.delete']);

        // Admin Product Reviews (moderation)
        Route::middleware('store.channel:online_store')->group(function () {
            Route::get('/admin/reviews', [AdminReviewController::class, 'index'])->name('store.admin.reviews.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:reviews.view']);
            Route::patch('/admin/reviews/{review}/approve', [AdminReviewController::class, 'toggleApprove'])->name('store.admin.reviews.approve')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:reviews.update']);
            Route::delete('/admin/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('store.admin.reviews.destroy')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:reviews.delete']);
        });

        // Store User & Staff Management (Store Owner only)
        Route::get('/admin/users', [UserManagementController::class, 'index'])->name('store.admin.users.index')->middleware([EnsureStoreAccess::class . ':store_owner', 'store.permission:staff.manage']);
        Route::post('/admin/users', [UserManagementController::class, 'store'])->name('store.admin.users.store')->middleware([EnsureStoreAccess::class . ':store_owner', 'store.permission:staff.manage']);
        Route::get('/admin/users/{user}/edit', [UserManagementController::class, 'edit'])->name('store.admin.users.edit')->middleware([EnsureStoreAccess::class . ':store_owner', 'store.permission:staff.manage']);
        Route::put('/admin/users/{user}', [UserManagementController::class, 'update'])->name('store.admin.users.update')->middleware([EnsureStoreAccess::class . ':store_owner', 'store.permission:staff.manage']);
        Route::patch('/admin/users/{user}/suspend', [UserManagementController::class, 'suspend'])->name('store.admin.users.suspend')->middleware([EnsureStoreAccess::class . ':store_owner', 'store.permission:staff.manage']);
        Route::delete('/admin/users/{user}', [UserManagementController::class, 'destroy'])->name('store.admin.users.destroy')->middleware([EnsureStoreAccess::class . ':store_owner', 'store.permission:staff.manage']);

        // Admin Home Banners CRUD
        Route::middleware('store.channel:online_store')->group(function () {
            Route::get('/admin/banners', [HomeBannerController::class, 'index'])->name('store.admin.banners.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:banners.view']);
            Route::get('/admin/banners/export', [HomeBannerController::class, 'export'])->name('store.admin.banners.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:banners.view']);
            Route::post('/admin/banners', [HomeBannerController::class, 'store'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:banners.create']);
            Route::get('/admin/banners/{banner}/edit', [HomeBannerController::class, 'edit'])->name('store.admin.banners.edit')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:banners.update']);
            Route::put('/admin/banners/{banner}', [HomeBannerController::class, 'update'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:banners.update']);
            Route::delete('/admin/banners/{banner}', [HomeBannerController::class, 'destroy'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:banners.delete']);
        });

        // Admin Categories CRUD & Quick Store
        Route::get('/admin/categories', [CategoryController::class, 'index'])->name('store.admin.categories.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.view']);
        Route::post('/admin/categories', [CategoryController::class, 'store'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.create']);
        Route::post('/admin/categories/quick-store', [CategoryController::class, 'quickStore'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.create']);
        Route::get('/admin/categories/{category}/edit', [CategoryController::class, 'edit'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.update']);
        Route::put('/admin/categories/{category}', [CategoryController::class, 'update'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.update']);
        Route::delete('/admin/categories/{category}', [CategoryController::class, 'destroy'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.delete']);

        // Category Excel import / export
        Route::get('/admin/categories/export', [CategoryController::class, 'export'])->name('store.admin.categories.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.view']);
        Route::get('/admin/categories/import', [CategoryController::class, 'importForm'])->name('store.admin.categories.import')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.create']);
        Route::post('/admin/categories/import', [CategoryController::class, 'import'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.create']);
        Route::post('/admin/categories/import/confirm', [CategoryController::class, 'confirmImport'])->name('store.admin.categories.import.confirm')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.create']);
        Route::get('/admin/categories/import-template', [CategoryController::class, 'downloadImportTemplate'])->name('store.admin.categories.import.template')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.view']);

        // Admin Brands CRUD & Quick Store
        Route::get('/admin/brands', [BrandController::class, 'index'])->name('store.admin.brands.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.view']);
        Route::post('/admin/brands', [BrandController::class, 'store'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.create']);
        Route::post('/admin/brands/quick-store', [BrandController::class, 'quickStore'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.create']);
        Route::get('/admin/brands/{brand}/edit', [BrandController::class, 'edit'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.update']);
        Route::put('/admin/brands/{brand}', [BrandController::class, 'update'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.update']);
        Route::delete('/admin/brands/{brand}', [BrandController::class, 'destroy'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.delete']);

        // Brand Excel import / export
        Route::get('/admin/brands/export', [BrandController::class, 'export'])->name('store.admin.brands.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.view']);
        Route::get('/admin/brands/import', [BrandController::class, 'importForm'])->name('store.admin.brands.import')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.create']);
        Route::post('/admin/brands/import', [BrandController::class, 'import'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.create']);
        Route::post('/admin/brands/import/confirm', [BrandController::class, 'confirmImport'])->name('store.admin.brands.import.confirm')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.create']);
        Route::get('/admin/brands/import-template', [BrandController::class, 'downloadImportTemplate'])->name('store.admin.brands.import.template')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.view']);

        // Admin Variant Presets
        Route::get('/admin/variant-presets/export', [VariantPresetController::class, 'export'])->name('store.admin.variant-presets.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.view']);
        Route::get('/admin/variant-presets/import', [VariantPresetController::class, 'importForm'])->name('store.admin.variant-presets.import')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.create']);
        Route::post('/admin/variant-presets/import', [VariantPresetController::class, 'import'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.create']);
        Route::post('/admin/variant-presets/import/confirm', [VariantPresetController::class, 'confirmImport'])->name('store.admin.variant-presets.import.confirm')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.create']);
        Route::get('/admin/variant-presets/import-template', [VariantPresetController::class, 'downloadImportTemplate'])->name('store.admin.variant-presets.import.template')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.view']);
        Route::get('/admin/variant-presets', [VariantPresetController::class, 'index'])->name('store.admin.variant-presets.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.view']);
        Route::post('/admin/variant-presets', [VariantPresetController::class, 'store'])->name('store.admin.variant-presets.store')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.create']);
        Route::get('/admin/variant-presets/{variantPreset}/edit', [VariantPresetController::class, 'edit'])->name('store.admin.variant-presets.edit')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.update']);
        Route::put('/admin/variant-presets/{variantPreset}', [VariantPresetController::class, 'update'])->name('store.admin.variant-presets.update')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.update']);
        Route::post('/admin/variant-presets/{variantPreset}/duplicate', [VariantPresetController::class, 'duplicate'])->name('store.admin.variant-presets.duplicate')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.create']);
        Route::patch('/admin/variant-presets/{variantPreset}/move', [VariantPresetController::class, 'move'])->name('store.admin.variant-presets.move')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.update']);
        Route::delete('/admin/variant-presets/{variantPreset}', [VariantPresetController::class, 'destroy'])->name('store.admin.variant-presets.destroy')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.delete']);

        // Admin Master Presets (Connectors, Colors, Shelf Locations, Warranties, Return Policies)
        Route::get('/admin/product-master-presets/export', [\App\Http\Controllers\Admin\ProductMasterPresetController::class, 'export'])->name('store.admin.product-master-presets.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.view']);
        Route::get('/admin/product-master-presets/import', [\App\Http\Controllers\Admin\ProductMasterPresetController::class, 'importForm'])->name('store.admin.product-master-presets.import')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.create']);
        Route::post('/admin/product-master-presets/import', [\App\Http\Controllers\Admin\ProductMasterPresetController::class, 'import'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.create']);
        Route::post('/admin/product-master-presets/import/confirm', [\App\Http\Controllers\Admin\ProductMasterPresetController::class, 'confirmImport'])->name('store.admin.product-master-presets.import.confirm')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.create']);
        Route::get('/admin/product-master-presets/import-template', [\App\Http\Controllers\Admin\ProductMasterPresetController::class, 'downloadImportTemplate'])->name('store.admin.product-master-presets.import.template')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.view']);
        Route::post('/admin/product-master-presets', [\App\Http\Controllers\Admin\ProductMasterPresetController::class, 'store'])->name('store.admin.product-master-presets.store')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.create']);
        Route::put('/admin/product-master-presets/{masterPreset}', [\App\Http\Controllers\Admin\ProductMasterPresetController::class, 'update'])->name('store.admin.product-master-presets.update')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.update']);
        Route::delete('/admin/product-master-presets/{masterPreset}', [\App\Http\Controllers\Admin\ProductMasterPresetController::class, 'destroy'])->name('store.admin.product-master-presets.destroy')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.delete']);

        // Admin Products CRUD & Bulk Actions
        Route::get('/admin/products', [ProductController::class, 'index'])->name('store.admin.products.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:products.view']);
        // Products Master Data hub — horizontal scroll tabs (categories /
        // brands / variant settings). The tab lives in ?tab= and each tab
        // embeds the same content partial as the standalone index page.
        Route::get('/admin/products/master-data', [ProductMasterDataController::class, 'index'])
            ->name('store.admin.products.master-data')
            ->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:master_data.view']);
        Route::post('/admin/products/master-data/seed', [ProductMasterDataController::class, 'seedImport'])
            ->name('store.admin.products.master-data.seed')
            ->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:master_data.create']);
        // Supplier management (full CRUD).
        Route::get('/admin/suppliers', [SupplierController::class, 'index'])
            ->name('store.admin.suppliers.index')
            ->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:suppliers.view']);
        Route::post('/admin/suppliers', [SupplierController::class, 'store'])
            ->name('store.admin.suppliers.store')
            ->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:suppliers.create']);
        Route::get('/admin/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])
            ->name('store.admin.suppliers.edit')
            ->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:suppliers.update']);
        Route::put('/admin/suppliers/{supplier}', [SupplierController::class, 'update'])
            ->name('store.admin.suppliers.update')
            ->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:suppliers.update']);
        Route::delete('/admin/suppliers/{supplier}', [SupplierController::class, 'destroy'])
            ->name('store.admin.suppliers.destroy')
            ->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:suppliers.delete']);

        // Supplier quick-add (product form "Supplier & Purchase" section).
        // Supplier import/export.
        Route::get('/admin/suppliers/export', [SupplierController::class, 'export'])
            ->name('store.admin.suppliers.export')
            ->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:suppliers.view']);
        Route::get('/admin/suppliers/aging', [SupplierController::class, 'agingReport'])
            ->name('store.admin.suppliers.aging')
            ->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:suppliers.view']);

        // Warehouses (store-scoped, manager/staff only)
        Route::get('/admin/warehouses', [WarehouseController::class, 'index'])
            ->name('store.admin.warehouses.index')
            ->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:warehouses.view']);
        Route::post('/admin/warehouses', [WarehouseController::class, 'store'])
            ->name('store.admin.warehouses.store')
            ->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:warehouses.create']);
        Route::put('/admin/warehouses/{warehouse}', [WarehouseController::class, 'update'])
            ->name('store.admin.warehouses.update')
            ->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:warehouses.update']);
        Route::delete('/admin/warehouses/{warehouse}', [WarehouseController::class, 'destroy'])
            ->name('store.admin.warehouses.destroy')
            ->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:warehouses.delete']);
        Route::get('/admin/suppliers/import', [SupplierController::class, 'importForm'])
            ->name('store.admin.suppliers.import')
            ->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:suppliers.create']);
        Route::post('/admin/suppliers/import', [SupplierController::class, 'import'])
            ->name('store.admin.suppliers.import.do')
            ->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:suppliers.create']);
        Route::post('/admin/suppliers/import/confirm', [SupplierController::class, 'confirmImport'])
            ->name('store.admin.suppliers.import.confirm')
            ->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:suppliers.create']);
        Route::get('/admin/suppliers/import-template', [SupplierController::class, 'downloadImportTemplate'])
            ->name('store.admin.suppliers.import.template')
            ->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:suppliers.view']);
        Route::post('/admin/suppliers/quick-store', [SupplierController::class, 'quickStore'])
            ->name('store.admin.suppliers.quick-store')
            ->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:suppliers.create', 'throttle:60,1']);
        Route::get('/admin/products/create', [ProductController::class, 'create'])->name('store.admin.products.create')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:products.create']);
        Route::post('/admin/products', [ProductController::class, 'store'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:products.create']);
        Route::post('/admin/products/bulk-stock', [ProductController::class, 'bulkStock'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:products.update']);
        Route::post('/admin/products/bulk-prices', [ProductController::class, 'bulkAdjustPrices'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:products.update']);
        Route::post('/admin/products/bulk-delete', [ProductController::class, 'bulkDelete'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:products.delete']);
        Route::get('/admin/products/{product}/edit', [ProductController::class, 'edit'])->name('store.admin.products.edit')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:products.update']);
        Route::put('/admin/products/{product}', [ProductController::class, 'update'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:products.update']);
        Route::delete('/admin/products/{product}', [ProductController::class, 'destroy'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:products.delete']);
        Route::post('/admin/products/{product}/toggle-featured', [ProductController::class, 'toggleFeatured'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:products.update']);
        // Per-row + bulk "Sell Online" toggles (is_ecommerce).
        Route::post('/admin/products/{product}/toggle-ecommerce', [ProductController::class, 'toggleEcommerce'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:products.update']);
        Route::post('/admin/products/bulk-ecommerce', [ProductController::class, 'bulkSetEcommerce'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:products.update']);
        Route::post('/admin/products/{product}/duplicate', [ProductController::class, 'duplicate'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:products.create']);

        // Admin Product Multiple Image Gallery
        Route::post('/admin/products/{product}/images', [ProductController::class, 'uploadImages'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:products.update']);
        Route::post('/admin/products/{product}/images/{image}/primary', [ProductController::class, 'setPrimaryImage'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:products.update']);
        Route::delete('/admin/products/{product}/images/{image}', [ProductController::class, 'deleteImage'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:products.update']);

        // Admin Product Import
        Route::get('/admin/products/import', [ProductController::class, 'importForm'])->name('store.admin.products.import')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:product_import.view']);
        Route::get('/admin/products/export', [ProductController::class, 'export'])->name('store.admin.products.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:products.export']);
        Route::get('/admin/products/{product}/details', [ProductController::class, 'details'])->name('store.admin.products.details')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:products.view']);
        Route::get('/admin/products/import/template', [ProductController::class, 'downloadImportTemplate'])->name('store.admin.products.import.template')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:product_import.view']);
        Route::post('/admin/products/import', [ProductController::class, 'import'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:product_import.create', 'throttle:imports']);
        Route::post('/admin/products/import/confirm', [ProductController::class, 'confirmImport'])->name('store.admin.products.import.confirm')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:product_import.create', 'throttle:imports']);

        // Pilot Data Import hub (products / customers / suppliers / debt / scenarios)
        Route::get('/admin/pilot-import/{tab?}', [PilotImportController::class, 'index'])->name('store.admin.pilot-import.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:product_import.view'])->whereIn('tab', ['products', 'customers', 'suppliers', 'debt', 'scenarios']);
        Route::post('/admin/pilot-import/seed-store', [PilotImportController::class, 'seedStore'])->name('store.admin.pilot-import.seed-store')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:product_import.create', 'throttle:imports']);
        Route::post('/admin/pilot-import/clean-store-data', [PilotImportController::class, 'cleanStoreData'])->name('store.admin.pilot-import.clean-store-data')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:product_import.delete', 'throttle:imports']);
        Route::post('/admin/pilot-import/demo-scenarios/{scenario}', [PilotImportController::class, 'createDemoScenario'])->name('store.admin.pilot-import.demo-scenarios.store')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:product_import.create', 'throttle:imports']);
        Route::post('/admin/pilot-import/{tab}', [PilotImportController::class, 'import'])->name('store.admin.pilot-import.import')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:product_import.create', 'throttle:imports'])->whereIn('tab', ['products', 'customers', 'suppliers', 'debt']);
        Route::post('/admin/pilot-import/{tab}/confirm', [PilotImportController::class, 'confirmImport'])->name('store.admin.pilot-import.confirm')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:product_import.create', 'throttle:imports'])->whereIn('tab', ['products', 'customers', 'suppliers', 'debt']);
        Route::get('/admin/pilot-import/{tab}/template', [PilotImportController::class, 'downloadTemplate'])->name('store.admin.pilot-import.template')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:product_import.view'])->whereIn('tab', ['products', 'customers', 'suppliers', 'debt']);

        // Barcode & QR Label Printing Management
        Route::middleware(['store.capability:catalog.barcode_printing'])->group(function () {
            Route::get('/admin/barcode', [\App\Http\Controllers\Admin\BarcodeLabelController::class, 'index'])->name('store.admin.barcode.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:barcode.view']);
            Route::get('/admin/barcode/export', [\App\Http\Controllers\Admin\BarcodeLabelController::class, 'export'])->name('store.admin.barcode.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:barcode.view']);
            Route::get('/admin/barcode/search', [\App\Http\Controllers\Admin\BarcodeLabelController::class, 'search'])->name('store.admin.barcode.search')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:barcode.view']);
            Route::post('/admin/barcode/print', [\App\Http\Controllers\Admin\BarcodeLabelController::class, 'print'])->name('store.admin.barcode.print')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:barcode.view']);
            Route::post('/admin/barcode/templates', [\App\Http\Controllers\Admin\BarcodeLabelController::class, 'saveTemplate'])->name('store.admin.barcode.templates.save')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:barcode.create']);
            Route::delete('/admin/barcode/templates/{id}', [\App\Http\Controllers\Admin\BarcodeLabelController::class, 'deleteTemplate'])->name('store.admin.barcode.templates.delete')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:barcode.delete']);
        });

        // Warranty & Serial / IMEI Tracker (SoT §19)
        Route::middleware(['store.capability:service.warranty_tracking'])->group(function () {
            Route::get('/admin/warranty', [\App\Http\Controllers\Admin\WarrantyTrackerController::class, 'index'])->name('store.admin.warranty.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:warranty.view']);
            Route::get('/admin/warranty/export', [\App\Http\Controllers\Admin\WarrantyTrackerController::class, 'export'])->name('store.admin.warranty.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:warranty.view']);
            Route::get('/admin/warranty/quick-scan', [\App\Http\Controllers\Admin\WarrantyTrackerController::class, 'quickScan'])->name('store.admin.warranty.quick_scan')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:warranty.view']);
            Route::get('/admin/warranty/create', [\App\Http\Controllers\Admin\WarrantyTrackerController::class, 'create'])->name('store.admin.warranty.create')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:warranty.create']);
            Route::post('/admin/warranty', [\App\Http\Controllers\Admin\WarrantyTrackerController::class, 'store'])->name('store.admin.warranty.store')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:warranty.create']);
            Route::get('/admin/warranty/{warranty}', [\App\Http\Controllers\Admin\WarrantyTrackerController::class, 'show'])->name('store.admin.warranty.show')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:warranty.view']);
            Route::get('/admin/warranty/{warranty}/edit', [\App\Http\Controllers\Admin\WarrantyTrackerController::class, 'edit'])->name('store.admin.warranty.edit')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:warranty.update']);
            Route::put('/admin/warranty/{warranty}', [\App\Http\Controllers\Admin\WarrantyTrackerController::class, 'update'])->name('store.admin.warranty.update')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:warranty.update']);
            Route::post('/admin/warranty/{warranty}/claim', [\App\Http\Controllers\Admin\WarrantyTrackerController::class, 'claim'])->name('store.admin.warranty.claim')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:warranty.update']);
            Route::get('/admin/warranty/{warranty}/certificate', [\App\Http\Controllers\Admin\WarrantyTrackerController::class, 'certificate'])->name('store.admin.warranty.certificate')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:warranty.view']);
        });

        // Physical Stock Count & Inventory Audit (sidebar_stock_count)
        Route::get('/admin/stock-count', [\App\Http\Controllers\Admin\StockCountController::class, 'index'])->name('store.admin.stock_count.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:stock_count.view']);
        Route::get('/admin/stock-count/create', [\App\Http\Controllers\Admin\StockCountController::class, 'create'])->name('store.admin.stock_count.create')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:stock_count.create']);
        Route::post('/admin/stock-count', [\App\Http\Controllers\Admin\StockCountController::class, 'store'])->name('store.admin.stock_count.store')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:stock_count.create']);
        Route::get('/admin/stock-count/export', [\App\Http\Controllers\Admin\StockCountController::class, 'export'])->name('store.admin.stock_count.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:stock_count.view']);
        Route::get('/admin/stock-count/{stock_count}/export', [\App\Http\Controllers\Admin\StockCountController::class, 'exportSession'])->name('store.admin.stock_count.export_session')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:stock_count.view']);
        Route::get('/admin/stock-count/{stock_count}', [\App\Http\Controllers\Admin\StockCountController::class, 'show'])->name('store.admin.stock_count.show')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:stock_count.view']);
        Route::post('/admin/stock-count/{stock_count}/line/{line}', [\App\Http\Controllers\Admin\StockCountController::class, 'updateLine'])->name('store.admin.stock_count.update_line')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:stock_count.update']);
        Route::post('/admin/stock-count/{stock_count}/bulk-update', [\App\Http\Controllers\Admin\StockCountController::class, 'bulkUpdate'])->name('store.admin.stock_count.bulk_update')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:stock_count.update']);
        Route::get('/admin/stock-count/{stock_count}/quick-scan', [\App\Http\Controllers\Admin\StockCountController::class, 'quickScan'])->name('store.admin.stock_count.quick_scan')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:stock_count.view']);
        Route::post('/admin/stock-count/{stock_count}/approve', [\App\Http\Controllers\Admin\StockCountController::class, 'approve'])->name('store.admin.stock_count.approve')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:stock_count.update']);
        Route::post('/admin/stock-count/{stock_count}/cancel', [\App\Http\Controllers\Admin\StockCountController::class, 'cancel'])->name('store.admin.stock_count.cancel')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:stock_count.update']);
        Route::get('/admin/stock-count/{stock_count}/print', [\App\Http\Controllers\Admin\StockCountController::class, 'printSheet'])->name('store.admin.stock_count.print')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:stock_count.view']);

        // Stock Movement Ledger & Bin Cards (sidebar_stock_ledger)
        Route::get('/admin/stock-ledger', [\App\Http\Controllers\Admin\StockLedgerController::class, 'index'])->name('store.admin.stock_ledger.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:stock_ledger.view']);
        Route::get('/admin/stock-ledger/bin-card/{product?}', [\App\Http\Controllers\Admin\StockLedgerController::class, 'binCard'])->name('store.admin.stock_ledger.bin_card')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:stock_ledger.view']);
        Route::get('/admin/stock-ledger/export', [\App\Http\Controllers\Admin\StockLedgerController::class, 'export'])->name('store.admin.stock_ledger.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:stock_ledger.export']);
        Route::get('/admin/stock-ledger/print-bin-card/{product}', [\App\Http\Controllers\Admin\StockLedgerController::class, 'printBinCard'])->name('store.admin.stock_ledger.print_bin_card')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:stock_ledger.view']);

        // Bulk Price Wizard (sidebar_price_wizard)
        Route::middleware(['store.capability:catalog.price_wizard'])->group(function () {
            Route::get('/admin/price-wizard', [\App\Http\Controllers\Admin\BulkPriceWizardController::class, 'index'])->name('store.admin.price_wizard.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:price_wizard.view']);
            Route::post('/admin/price-wizard/calculate', [\App\Http\Controllers\Admin\BulkPriceWizardController::class, 'calculate'])->name('store.admin.price_wizard.calculate')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:price_wizard.view']);
            Route::post('/admin/price-wizard/apply', [\App\Http\Controllers\Admin\BulkPriceWizardController::class, 'apply'])->name('store.admin.price_wizard.apply')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:price_wizard.update']);
            Route::get('/admin/price-wizard/export', [\App\Http\Controllers\Admin\BulkPriceWizardController::class, 'export'])->name('store.admin.price_wizard.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:price_wizard.view']);
        });

        // Cash & Bank Transactions Register (sidebar_transactions)
        Route::get('/admin/transactions', [\App\Http\Controllers\Admin\CashBankTransactionController::class, 'index'])->name('store.admin.transactions.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:transactions.view']);
        Route::post('/admin/transactions/deposit', [\App\Http\Controllers\Admin\CashBankTransactionController::class, 'deposit'])->name('store.admin.transactions.deposit')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:transactions.create']);
        Route::post('/admin/transactions/withdraw', [\App\Http\Controllers\Admin\CashBankTransactionController::class, 'withdraw'])->name('store.admin.transactions.withdraw')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:transactions.create']);
        Route::post('/admin/transactions/transfer', [\App\Http\Controllers\Admin\CashBankTransactionController::class, 'transfer'])->name('store.admin.transactions.transfer')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:transactions.create']);
        Route::post('/admin/transactions/account', [\App\Http\Controllers\Admin\CashBankTransactionController::class, 'storeAccount'])->name('store.admin.transactions.account.store')->middleware([EnsureStoreAccess::class . ':store_manager', 'finance_access', 'store.permission:transactions.create']);
        Route::get('/admin/transactions/export', [\App\Http\Controllers\Admin\CashBankTransactionController::class, 'export'])->name('store.admin.transactions.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:transactions.view']);
        Route::get('/admin/transactions/{transaction}/voucher', [\App\Http\Controllers\Admin\CashBankTransactionController::class, 'printVoucher'])->name('store.admin.transactions.voucher')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'finance_access', 'store.permission:transactions.view']);

        // Printer Setup & Direct Printing (sidebar_printers)
        Route::get('/admin/printers', [\App\Http\Controllers\Admin\PrinterController::class, 'index'])->name('store.admin.printers.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:settings.view']);
        Route::get('/admin/printers/create', [\App\Http\Controllers\Admin\PrinterController::class, 'create'])->name('store.admin.printers.create')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.update']);
        Route::post('/admin/printers', [\App\Http\Controllers\Admin\PrinterController::class, 'store'])->name('store.admin.printers.store')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.update']);
        Route::get('/admin/printers/{printer}/edit', [\App\Http\Controllers\Admin\PrinterController::class, 'edit'])->name('store.admin.printers.edit')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.update']);
        Route::put('/admin/printers/{printer}', [\App\Http\Controllers\Admin\PrinterController::class, 'update'])->name('store.admin.printers.update')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.update']);
        Route::delete('/admin/printers/{printer}', [\App\Http\Controllers\Admin\PrinterController::class, 'destroy'])->name('store.admin.printers.destroy')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.delete']);
        Route::post('/admin/printers/{printer}/set-default', [\App\Http\Controllers\Admin\PrinterController::class, 'setDefault'])->name('store.admin.printers.set_default')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.update']);
        Route::get('/admin/printers/{printer}/test-print', [\App\Http\Controllers\Admin\PrinterController::class, 'testPrint'])->name('store.admin.printers.test_print')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:settings.view']);

        // Voucher Customizer & Templates (sidebar_vouchers)
        Route::get('/admin/vouchers', [\App\Http\Controllers\Admin\VoucherCustomizerController::class, 'index'])->name('store.admin.vouchers.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:settings.view']);
        Route::post('/admin/vouchers', [\App\Http\Controllers\Admin\VoucherCustomizerController::class, 'store'])->name('store.admin.vouchers.store')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.update']);
        Route::put('/admin/vouchers/{voucher}', [\App\Http\Controllers\Admin\VoucherCustomizerController::class, 'update'])->name('store.admin.vouchers.update')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.update']);
        Route::delete('/admin/vouchers/{voucher}', [\App\Http\Controllers\Admin\VoucherCustomizerController::class, 'destroy'])->name('store.admin.vouchers.destroy')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.delete']);
        Route::post('/admin/vouchers/{voucher}/set-default', [\App\Http\Controllers\Admin\VoucherCustomizerController::class, 'setDefault'])->name('store.admin.vouchers.set_default')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.update']);
        Route::get('/admin/vouchers/{voucher}/preview', [\App\Http\Controllers\Admin\VoucherCustomizerController::class, 'preview'])->name('store.admin.vouchers.preview')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:settings.view']);

        // Multi-Branch Management (sidebar_branches)
        Route::middleware(['store.capability:operations.branches'])->group(function () {
            Route::get('/admin/branches', [\App\Http\Controllers\Admin\BranchManagementController::class, 'index'])->name('store.admin.branches.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:settings.view']);
            Route::get('/admin/branches/create', [\App\Http\Controllers\Admin\BranchManagementController::class, 'create'])->name('store.admin.branches.create')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.update']);
            Route::post('/admin/branches', [\App\Http\Controllers\Admin\BranchManagementController::class, 'store'])->name('store.admin.branches.store')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.update']);
            Route::get('/admin/branches/{branch}', [\App\Http\Controllers\Admin\BranchManagementController::class, 'show'])->name('store.admin.branches.show')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:settings.view']);
            Route::get('/admin/branches/{branch}/edit', [\App\Http\Controllers\Admin\BranchManagementController::class, 'edit'])->name('store.admin.branches.edit')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.update']);
            Route::put('/admin/branches/{branch}', [\App\Http\Controllers\Admin\BranchManagementController::class, 'update'])->name('store.admin.branches.update')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.update']);
            Route::delete('/admin/branches/{branch}', [\App\Http\Controllers\Admin\BranchManagementController::class, 'destroy'])->name('store.admin.branches.destroy')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.delete']);
            Route::post('/admin/branches/{branch}/set-default', [\App\Http\Controllers\Admin\BranchManagementController::class, 'setDefault'])->name('store.admin.branches.set_default')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.update']);
        });

        // Currency Exchange Rates (sidebar_exchange_rates)
        Route::get('/admin/exchange-rates', [\App\Http\Controllers\Admin\CurrencyExchangeController::class, 'index'])->name('store.admin.exchange_rates.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:settings.view']);
        Route::post('/admin/exchange-rates', [\App\Http\Controllers\Admin\CurrencyExchangeController::class, 'store'])->name('store.admin.exchange_rates.store')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.update']);
        Route::post('/admin/exchange-rates/bulk-update', [\App\Http\Controllers\Admin\CurrencyExchangeController::class, 'bulkUpdate'])->name('store.admin.exchange_rates.bulk_update')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.update']);
        Route::get('/admin/exchange-rates/convert', [\App\Http\Controllers\Admin\CurrencyExchangeController::class, 'convert'])->name('store.admin.exchange_rates.convert')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:settings.view']);
        Route::put('/admin/exchange-rates/{currency}', [\App\Http\Controllers\Admin\CurrencyExchangeController::class, 'update'])->name('store.admin.exchange_rates.update')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.update']);
        Route::delete('/admin/exchange-rates/{currency}', [\App\Http\Controllers\Admin\CurrencyExchangeController::class, 'destroy'])->name('store.admin.exchange_rates.destroy')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:settings.delete']);

        // Membership Tier & Loyalty Points (sidebar_membership)
        Route::middleware(['store.capability:commerce.loyalty_points'])->group(function () {
            Route::get('/admin/membership', [\App\Http\Controllers\Admin\MembershipLoyaltyController::class, 'index'])->name('store.admin.membership.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:membership.view']);
            Route::post('/admin/membership/tiers', [\App\Http\Controllers\Admin\MembershipLoyaltyController::class, 'storeTier'])->name('store.admin.membership.tiers.store')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:membership.create']);
            Route::put('/admin/membership/tiers/{tier}', [\App\Http\Controllers\Admin\MembershipLoyaltyController::class, 'updateTier'])->name('store.admin.membership.tiers.update')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:membership.update']);
            Route::delete('/admin/membership/tiers/{tier}', [\App\Http\Controllers\Admin\MembershipLoyaltyController::class, 'destroyTier'])->name('store.admin.membership.tiers.destroy')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:membership.delete']);
            Route::post('/admin/membership/adjust-points', [\App\Http\Controllers\Admin\MembershipLoyaltyController::class, 'adjustPoints'])->name('store.admin.membership.adjust_points')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:membership.update']);
            Route::post('/admin/membership/assign-tier', [\App\Http\Controllers\Admin\MembershipLoyaltyController::class, 'assignTier'])->name('store.admin.membership.assign_tier')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:membership.update']);
        });

        // Promotions & Coupon Engine (sidebar_promotions)
        Route::get('/admin/promotions', [\App\Http\Controllers\Admin\PromotionController::class, 'index'])->name('store.admin.promotions.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:promotions.view']);
        Route::post('/admin/promotions', [\App\Http\Controllers\Admin\PromotionController::class, 'store'])->name('store.admin.promotions.store')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:promotions.create']);
        Route::get('/admin/promotions/validate-coupon', [\App\Http\Controllers\Admin\PromotionController::class, 'validateCoupon'])->name('store.admin.promotions.validate_coupon')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:promotions.view']);
        Route::put('/admin/promotions/{promotion}', [\App\Http\Controllers\Admin\PromotionController::class, 'update'])->name('store.admin.promotions.update')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:promotions.update']);
        Route::post('/admin/promotions/{promotion}/toggle', [\App\Http\Controllers\Admin\PromotionController::class, 'toggle'])->name('store.admin.promotions.toggle')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:promotions.update']);
        Route::delete('/admin/promotions/{promotion}', [\App\Http\Controllers\Admin\PromotionController::class, 'destroy'])->name('store.admin.promotions.destroy')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:promotions.delete']);

        // Web Catalog Product Visibility (sidebar_web_products)
        Route::middleware(['store.channel:online_store'])->group(function () {
            Route::get('/admin/web-products/export', [\App\Http\Controllers\Admin\WebProductController::class, 'export'])->name('store.admin.web_products.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:web_products.view']);
            Route::get('/admin/web-products', [\App\Http\Controllers\Admin\WebProductController::class, 'index'])->name('store.admin.web_products.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:web_products.view']);
            Route::post('/admin/web-products/toggle-visibility', [\App\Http\Controllers\Admin\WebProductController::class, 'toggleVisibility'])->name('store.admin.web_products.toggle_visibility')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:web_products.update']);
            Route::post('/admin/web-products/toggle-featured', [\App\Http\Controllers\Admin\WebProductController::class, 'toggleFeatured'])->name('store.admin.web_products.toggle_featured')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:web_products.update']);
            Route::post('/admin/web-products/bulk-visibility', [\App\Http\Controllers\Admin\WebProductController::class, 'bulkVisibility'])->name('store.admin.web_products.bulk_visibility')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:web_products.update']);
            Route::post('/admin/web-products/bulk-featured', [\App\Http\Controllers\Admin\WebProductController::class, 'bulkFeatured'])->name('store.admin.web_products.bulk_featured')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:web_products.update']);
        });

        // Mobile E-Load & Bill Register (sidebar_eload)
        Route::middleware(['store.capability:operations.eload'])->group(function () {
            Route::get('/admin/eload', [\App\Http\Controllers\Admin\EloadController::class, 'index'])->name('store.admin.eload.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:pos_eload.view']);
            Route::get('/admin/eload/export', [\App\Http\Controllers\Admin\EloadController::class, 'export'])->name('store.admin.eload.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:pos_eload.view']);
            Route::post('/admin/eload', [\App\Http\Controllers\Admin\EloadController::class, 'store'])->name('store.admin.eload.store')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:pos_eload.create']);
            Route::post('/admin/eload/refill', [\App\Http\Controllers\Admin\EloadController::class, 'refill'])->name('store.admin.eload.refill')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:pos_eload.create']);
            Route::post('/admin/eload/accounts', [\App\Http\Controllers\Admin\EloadController::class, 'saveAccount'])->name('store.admin.eload.accounts.store')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:pos_eload.create']);
            Route::delete('/admin/eload/accounts/{id}', [\App\Http\Controllers\Admin\EloadController::class, 'deleteAccount'])->name('store.admin.eload.accounts.destroy')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:pos_eload.delete']);
            Route::patch('/admin/eload/transactions/{id}/status', [\App\Http\Controllers\Admin\EloadController::class, 'updateStatus'])->name('store.admin.eload.status')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:pos_eload.update']);
            Route::get('/admin/eload/transactions/{id}/slip', [\App\Http\Controllers\Admin\EloadController::class, 'printSlip'])->name('store.admin.eload.slip')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:pos_eload.view']);
        });

        // Sales Analytics & Deep Charts (sidebar_sales_analytics)
        Route::get('/admin/reports/sales-analytics', [\App\Http\Controllers\Admin\SalesAnalyticsController::class, 'index'])->name('store.admin.sales_analytics.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:sales_analytics.view']);
        Route::get('/admin/reports/sales-analytics/export', [\App\Http\Controllers\Admin\SalesAnalyticsController::class, 'exportCsv'])->name('store.admin.sales_analytics.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:sales_analytics.export']);

        // Inventory Valuation Report (sidebar_inventory_valuation)
        Route::get('/admin/reports/inventory-valuation', [\App\Http\Controllers\Admin\InventoryValuationController::class, 'index'])->name('store.admin.inventory_valuation.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:inventory_valuation.view']);
        Route::get('/admin/reports/inventory-valuation/export', [\App\Http\Controllers\Admin\InventoryValuationController::class, 'exportCsv'])->name('store.admin.inventory_valuation.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:inventory_valuation.export']);
        Route::get('/admin/reports/inventory-valuation/print', [\App\Http\Controllers\Admin\InventoryValuationController::class, 'printReport'])->name('store.admin.inventory_valuation.print')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:inventory_valuation.view']);

        // Debt Aging Analysis Report (sidebar_aging_report)
        Route::get('/admin/reports/debt-aging', [\App\Http\Controllers\Admin\DebtAgingController::class, 'index'])->name('store.admin.debt_aging.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:debt_aging.view']);
        Route::get('/admin/reports/debt-aging/export', [\App\Http\Controllers\Admin\DebtAgingController::class, 'exportCsv'])->name('store.admin.debt_aging.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:debt_aging.export']);
        Route::get('/admin/reports/debt-aging/print', [\App\Http\Controllers\Admin\DebtAgingController::class, 'printReport'])->name('store.admin.debt_aging.print')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:debt_aging.view']);

        // Staff Roles & Granular Permissions (sidebar_roles)
        Route::get('/admin/security/roles', [\App\Http\Controllers\Admin\StaffRoleController::class, 'index'])->name('store.admin.roles.index')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:roles.view']);
        Route::post('/admin/security/roles', [\App\Http\Controllers\Admin\StaffRoleController::class, 'store'])->name('store.admin.roles.store')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:roles.create']);
        Route::put('/admin/security/roles/{role}', [\App\Http\Controllers\Admin\StaffRoleController::class, 'update'])->name('store.admin.roles.update')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:roles.update']);
        Route::delete('/admin/security/roles/{role}', [\App\Http\Controllers\Admin\StaffRoleController::class, 'destroy'])->name('store.admin.roles.destroy')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:roles.delete']);
        Route::post('/admin/security/roles/assign-staff', [\App\Http\Controllers\Admin\StaffRoleController::class, 'assignStaff'])->name('store.admin.roles.assign_staff')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:roles.update']);
        Route::get('/admin/security/roles/export', [\App\Http\Controllers\Admin\StaffRoleController::class, 'exportCsv'])->name('store.admin.roles.export')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:roles.export']);

        // System Audit Trail Logs (sidebar_audit_logs)
        Route::get('/admin/security/audit-logs/export', [\App\Http\Controllers\Admin\AuditLogController::class, 'export'])->name('store.admin.audit-logs.export')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:audit_logs.export']);
        Route::get('/admin/security/audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('store.admin.audit-logs.index')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:audit_logs.view']);
        Route::get('/admin/security/audit-logs/{log}', [\App\Http\Controllers\Admin\AuditLogController::class, 'show'])->name('store.admin.audit-logs.show')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:audit_logs.view']);


        // Roadmap placeholder page — one route for every not-yet-built module
        // (sidebar "coming soon" links). The module registry (slug → label +
        // phase) lives in ComingSoonController; unknown slugs 404.
        Route::get('/admin/coming-soon/{module}', [ComingSoonController::class, 'index'])
            ->name('store.admin.coming-soon')
            ->middleware(EnsureStoreAccess::class . ':store_manager,staff');

        // Admin Import History
        Route::get('/admin/import-history', [ImportHistoryController::class, 'index'])->name('store.admin.import-history.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:product_import.view']);
        Route::get('/admin/import-history/{history}', [ImportHistoryController::class, 'show'])->name('store.admin.import-history.show')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:product_import.view']);
        Route::get('/admin/import-history/{history}/errors', [ImportHistoryController::class, 'downloadErrors'])->name('store.admin.import-history.errors')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:product_import.view']);
        Route::delete('/admin/import-history/{history}', [ImportHistoryController::class, 'destroy'])->name('store.admin.import-history.destroy')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:product_import.delete']);

        // Database backups & restore
        Route::get('/admin/backups', [BackupController::class, 'index'])->name('store.admin.backups.index')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:backups.view']);
        Route::post('/admin/backups', [BackupController::class, 'store'])->name('store.admin.backups.store')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:backups.create']);

        // Offline-to-Cloud Sync Manager & Outbox Queue
        Route::get('/admin/sync', [\App\Http\Controllers\Admin\SyncAdminController::class, 'index'])->name('store.admin.sync.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:settings.view']);
        Route::post('/admin/sync/retry/{id}', [\App\Http\Controllers\Admin\SyncAdminController::class, 'retry'])->name('store.admin.sync.retry')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:settings.update']);
        Route::post('/admin/sync/retry-all', [\App\Http\Controllers\Admin\SyncAdminController::class, 'retryAll'])->name('store.admin.sync.retry_all')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:settings.update']);
        Route::get('/admin/backups/{file}/download', [BackupController::class, 'download'])->name('store.admin.backups.download')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:backups.view']);
        Route::delete('/admin/backups/{file}', [BackupController::class, 'destroy'])->name('store.admin.backups.destroy')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:backups.delete']);
        Route::post('/admin/backups/restore', [BackupController::class, 'restore'])->name('store.admin.backups.restore')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:backups.create']);
        Route::post('/admin/backups/upload-restore', [BackupController::class, 'uploadRestore'])->name('store.admin.backups.upload_restore')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:backups.create']);

        // Database Tools & Optimizer (sidebar_database)
        Route::get('/admin/database', [\App\Http\Controllers\Admin\DatabaseToolController::class, 'index'])->name('store.admin.database.index')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:database.view']);
        Route::post('/admin/database/vacuum', [\App\Http\Controllers\Admin\DatabaseToolController::class, 'vacuum'])->name('store.admin.database.vacuum')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:database.update']);
        Route::post('/admin/database/optimize', [\App\Http\Controllers\Admin\DatabaseToolController::class, 'optimize'])->name('store.admin.database.optimize')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:database.update']);
        Route::post('/admin/database/integrity-check', [\App\Http\Controllers\Admin\DatabaseToolController::class, 'integrityCheck'])->name('store.admin.database.integrity')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:database.update']);
        Route::post('/admin/database/clear-cache', [\App\Http\Controllers\Admin\DatabaseToolController::class, 'clearCache'])->name('store.admin.database.clear_cache')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:database.update']);

        // System Alert Center & Notifications (sidebar_alerts)
        Route::get('/admin/alerts', [\App\Http\Controllers\Admin\SystemAlertCenterController::class, 'index'])->name('store.admin.alerts.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:alerts.view']);
        Route::post('/admin/alerts/test-ping', [\App\Http\Controllers\Admin\SystemAlertCenterController::class, 'testNotification'])->name('store.admin.alerts.test_ping')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:alerts.update']);
        Route::post('/admin/alerts/daily-summary', [\App\Http\Controllers\Admin\SystemAlertCenterController::class, 'sendDailySummary'])->name('store.admin.alerts.daily_summary')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:alerts.update']);

        // Admin Wholesale Applications Management
        Route::middleware(['store.capability:commerce.wholesale_pricing'])->group(function () {
            Route::get('/admin/wholesale/applications/export', [WholesaleAdminController::class, 'export'])->name('store.admin.wholesale.applications.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:wholesale.view']);
            Route::get('/admin/wholesale/applications', [WholesaleAdminController::class, 'index'])->name('store.admin.wholesale.applications.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:wholesale.view']);
            Route::get('/admin/wholesale/applications/{application}', [WholesaleAdminController::class, 'show'])->name('store.admin.wholesale.applications.show')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:wholesale.view']);
            Route::get('/admin/wholesale/applications/{application}/print', [WholesaleAdminController::class, 'print'])->name('store.admin.wholesale.applications.print')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:wholesale.view']);
            Route::patch('/admin/wholesale/applications/{application}', [WholesaleAdminController::class, 'updateStatus'])->name('store.admin.wholesale.applications.update')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:wholesale.update']);
            Route::delete('/admin/wholesale/applications/{application}', [WholesaleAdminController::class, 'destroy'])->name('store.admin.wholesale.applications.destroy')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:wholesale.delete']);
        });

        // Admin Glass Finder Management
        Route::middleware(['store.capability:storefront.glass_finder'])->group(function () {
            Route::get('/admin/glass-finder', [GlassFinderAdminController::class, 'index'])->name('store.admin.glass-finder.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:glass_finder.view']);
            Route::post('/admin/glass-finder', [GlassFinderAdminController::class, 'store'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:glass_finder.create']);
            Route::get('/admin/glass-finder/{item}/edit', [GlassFinderAdminController::class, 'edit'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:glass_finder.update']);
            Route::put('/admin/glass-finder/{item}', [GlassFinderAdminController::class, 'update'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:glass_finder.update']);
            Route::get('/admin/glass-finder/import/template', [GlassFinderAdminController::class, 'downloadImportTemplate'])->name('store.admin.glass-finder.import.template')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:glass_finder.view']);
            Route::post('/admin/glass-finder/import', [GlassFinderAdminController::class, 'import'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:glass_finder.create', 'throttle:imports']);
            Route::post('/admin/glass-finder/import/confirm', [GlassFinderAdminController::class, 'confirmImport'])->name('store.admin.glass-finder.import.confirm')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:glass_finder.create', 'throttle:imports']);
            Route::delete('/admin/glass-finder/{item}', [GlassFinderAdminController::class, 'destroy'])->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:glass_finder.delete']);
        });

        // Admin Order Requests Management
        Route::middleware(['store.channel:online_ordering'])->group(function () {
            Route::get('/admin/orders', [OrderAdminController::class, 'index'])->name('store.admin.orders.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:ecommerce_orders.view']);
            Route::get('/admin/orders/export', [OrderAdminController::class, 'export'])->name('store.admin.orders.export')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:ecommerce_orders.view']);
            Route::get('/admin/orders/{order}', [OrderAdminController::class, 'show'])->name('store.admin.orders.show')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:ecommerce_orders.view']);
            Route::get('/admin/orders/{order}/invoice', [OrderAdminController::class, 'invoice'])->name('store.admin.orders.invoice')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:ecommerce_orders.view']);
            Route::patch('/admin/orders/{order}/status', [OrderAdminController::class, 'updateStatus'])->name('store.admin.orders.update_status')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:ecommerce_orders.update']);
            Route::patch('/admin/orders/{order}/finances', [OrderAdminController::class, 'updateFinances'])->name('store.admin.orders.update_finances')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:ecommerce_orders.update']);
            Route::patch('/admin/orders/{order}/note', [OrderAdminController::class, 'updateNote'])->name('store.admin.orders.update_note')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:ecommerce_orders.update']);
            Route::delete('/admin/orders/{order}', [OrderAdminController::class, 'destroy'])->name('store.admin.orders.destroy')->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:ecommerce_orders.delete']);
        });

        // Admin Web Push management (subscriber count, test/custom send, history)
        Route::middleware(['store.channel:online_store'])->group(function () {
            Route::get('/admin/push', [PushNotificationController::class, 'index'])->name('store.admin.push.index')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:web_push.view']);
            Route::get('/admin/push/history', [PushNotificationController::class, 'history'])->name('store.admin.push.history')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.permission:web_push.view']);
        });

        // POS module — cashier shifts + opening cash (target-design §2.10).
        // Statically registered, store-scoped, staff/store_manager only.
        Route::prefix('/pos')->middleware([EnsureStoreAccess::class . ':store_manager,staff', 'store.channel:pos'])->group(function () {
            Route::get('/', [\App\POS\Http\Controllers\CashierShiftController::class, 'index'])->name('pos.index')->middleware('store.permission:pos_sales.view');
            Route::post('/shifts', [\App\POS\Http\Controllers\CashierShiftController::class, 'open'])->name('pos.shifts.open')->middleware(['store.capability:operations.cashier_shifts', 'store.permission:pos_closing.create']);
            Route::post('/shifts/{shift}/cash-events', [\App\POS\Http\Controllers\CashierShiftController::class, 'cashEvent'])->name('pos.shifts.cash-event')->middleware(['store.capability:operations.cashier_shifts', 'store.permission:pos_closing.update']);
            Route::post('/shifts/{shift}/close', [\App\POS\Http\Controllers\CashierShiftController::class, 'close'])->name('pos.shifts.close')->middleware(['store.capability:operations.cashier_shifts', 'store.permission:pos_closing.update']);

            // POS cart + sale posting (target-design §2.8).
            Route::get('/products', [\App\POS\Http\Controllers\PosSaleController::class, 'search'])->name('pos.products.search')->middleware('store.permission:pos_sales.view');
            Route::get('/products-grid', [\App\POS\Http\Controllers\PosSaleController::class, 'grid'])->name('pos.products.grid')->middleware('store.permission:pos_sales.view');
            Route::get('/cart-state', [\App\POS\Http\Controllers\PosSaleController::class, 'cartState'])->name('pos.cart-state')->middleware('store.permission:pos_sales.view');

            // POS customer credit/debt (SoT §17) — search, quick-add, collect debt.
            Route::get('/customers', [\App\POS\Http\Controllers\PosSaleController::class, 'customers'])->name('pos.customers.search')->middleware('store.permission:pos_sales.view');
            Route::post('/customers', [\App\POS\Http\Controllers\PosSaleController::class, 'addCustomer'])->name('pos.customers.add')->middleware('store.permission:pos_sales.create');
            Route::post('/customers/{customer}/attach', [\App\POS\Http\Controllers\PosSaleController::class, 'attachCustomer'])->name('pos.customers.attach')->middleware('store.permission:pos_sales.update');
            Route::post('/customers/detach', [\App\POS\Http\Controllers\PosSaleController::class, 'detachCustomer'])->name('pos.customers.detach')->middleware('store.permission:pos_sales.update');
            Route::post('/customers/{customer}/collect', [\App\POS\Http\Controllers\PosSaleController::class, 'collect'])->name('pos.customers.collect')->middleware('store.permission:pos_sales.update');
            Route::post('/cart', [\App\POS\Http\Controllers\PosSaleController::class, 'addItem'])->name('pos.cart.add')->middleware('store.permission:pos_sales.create');
            // /cart/clear must be registered before /cart/{line} (route order).
            Route::post('/cart/clear', [\App\POS\Http\Controllers\PosSaleController::class, 'clearCart'])->name('pos.cart.clear')->middleware('store.permission:pos_sales.update');
            Route::post('/cart/{line}', [\App\POS\Http\Controllers\PosSaleController::class, 'updateLine'])->name('pos.cart.update')->middleware('store.permission:pos_sales.update');
            Route::post('/cart/{line}/price', [\App\POS\Http\Controllers\PosSaleController::class, 'setLinePrice'])->name('pos.cart.price')->middleware('store.permission:pos_sales.update');
            Route::delete('/cart/{line}', [\App\POS\Http\Controllers\PosSaleController::class, 'removeLine'])->name('pos.cart.remove')->middleware('store.permission:pos_sales.update');
            Route::post('/hold', [\App\POS\Http\Controllers\PosSaleController::class, 'hold'])->name('pos.hold')->middleware('store.permission:pos_sales.create');
            Route::post('/resume/{sale}', [\App\POS\Http\Controllers\PosSaleController::class, 'resume'])->name('pos.resume')->middleware('store.permission:pos_sales.update');
            Route::post('/void/{sale}', [\App\POS\Http\Controllers\PosSaleController::class, 'void'])->name('pos.void')->middleware('store.permission:pos_sales.update');
            Route::post('/post/{sale?}', [\App\POS\Http\Controllers\PosSaleController::class, 'post'])->name('pos.post')->middleware('store.permission:pos_sales.create');
            Route::get('/sales/{sale}/receipt', [\App\POS\Http\Controllers\PosSaleController::class, 'receipt'])->name('pos.receipt')->middleware('store.permission:pos_sales.view');
            Route::get('/web-orders', [\App\POS\Http\Controllers\PosSaleController::class, 'webOrders'])->name('pos.web-orders')->middleware('store.permission:pos_sales.view');

            // POS returns / refunds (target-design §2.9, SoT §15.1).
            Route::get('/sales/{sale}/refund', [\App\POS\Http\Controllers\PosReturnController::class, 'create'])->name('pos.refund.create')->middleware('store.permission:pos_returns.view');
            Route::post('/sales/{sale}/refunds', [\App\POS\Http\Controllers\PosReturnController::class, 'store'])->name('pos.refund.store')->middleware('store.permission:pos_returns.create');

            // Branch daily closing (SoT §18) — view/create by staff, approve by manager.
            Route::get('/closing', [\App\POS\Http\Controllers\DailyClosingController::class, 'index'])->name('pos.closing.index')->middleware(['store.capability:operations.cashier_shifts', 'store.permission:pos_closing.view']);
            Route::post('/closing', [\App\POS\Http\Controllers\DailyClosingController::class, 'store'])->name('pos.closing.store')->middleware(['store.capability:operations.cashier_shifts', 'store.permission:pos_closing.create']);
            Route::post('/closing/{closing}/approve', [\App\POS\Http\Controllers\DailyClosingController::class, 'approve'])->name('pos.closing.approve')
                ->middleware([EnsureStoreAccess::class . ':store_manager', 'store.capability:operations.cashier_shifts', 'store.permission:pos_closing.update']);

            // POS Reports (Sales / Cash Drawer / Stock / Services & Repairs)
            Route::get('/reports/sales', [\App\POS\Http\Controllers\PosReportController::class, 'sales'])->name('pos.reports.sales')->middleware('store.permission:reports_sales.view');
            Route::get('/reports/sales/export', [\App\POS\Http\Controllers\PosReportController::class, 'exportSales'])->name('pos.reports.sales.export')->middleware('store.permission:reports_sales.export');
            Route::get('/reports/cash', [\App\POS\Http\Controllers\PosReportController::class, 'cash'])->name('pos.reports.cash')->middleware('store.permission:reports_cash.view');
            Route::get('/reports/cash/export', [\App\POS\Http\Controllers\PosReportController::class, 'exportCash'])->name('pos.reports.cash.export')->middleware('store.permission:reports_cash.export');
            Route::get('/reports/stock', [\App\POS\Http\Controllers\PosReportController::class, 'stock'])->name('pos.reports.stock')->middleware('store.permission:inventory_valuation.view');
            Route::get('/reports/stock/export', [\App\POS\Http\Controllers\PosReportController::class, 'exportStock'])->name('pos.reports.stock.export')->middleware('store.permission:inventory_valuation.export');
            Route::get('/reports/services', [\App\POS\Http\Controllers\PosReportController::class, 'services'])->name('pos.reports.services')->middleware('store.permission:reports_services.view');
            Route::get('/reports/services/export', [\App\POS\Http\Controllers\PosReportController::class, 'exportServices'])->name('pos.reports.services.export')->middleware('store.permission:reports_services.export');

            // POS product search (used by PO create form)
            Route::get('/purchases/products', [\App\POS\Http\Controllers\PurchaseOrderController::class, 'productSearch'])->name('pos.purchases.product-search')->middleware('store.permission:purchases.view');

            // Specific purchase routes — MUST come before {purchaseOrder} wildcard
            Route::get('/purchases/payables', [\App\POS\Http\Controllers\PurchaseOrderController::class, 'payablesIndex'])->name('pos.purchases.payables')->middleware('store.permission:payables.view');
            Route::get('/purchases/payables/export', [\App\POS\Http\Controllers\PurchaseOrderController::class, 'payablesExport'])->name('pos.purchases.payables.export')->middleware('store.permission:payables.view');
            Route::get('/purchases/payables/{supplier}', [\App\POS\Http\Controllers\PurchaseOrderController::class, 'payablesShow'])->name('pos.purchases.payables.show')->middleware('store.permission:payables.view');
            Route::post('/purchases/payables/{supplier}/pay', [\App\POS\Http\Controllers\PurchaseOrderController::class, 'payablesPay'])->name('pos.purchases.payables.pay')->middleware('store.permission:payables.update');
            Route::get('/purchases/export', [\App\POS\Http\Controllers\PurchaseOrderController::class, 'export'])->name('pos.purchases.export')->middleware('store.permission:purchases.view');

            // Purchase order lifecycle (alinthit_pos style) — pending → ordered → received | cancelled.
            Route::get('/purchases', [\App\POS\Http\Controllers\PurchaseOrderController::class, 'index'])->name('pos.purchases.index')->middleware('store.permission:purchases.view');
            Route::get('/purchases/create', [\App\POS\Http\Controllers\PurchaseOrderController::class, 'create'])->name('pos.purchases.create')->middleware('store.permission:purchases.create');
            Route::post('/purchases', [\App\POS\Http\Controllers\PurchaseOrderController::class, 'store'])->name('pos.purchases.store')->middleware('store.permission:purchases.create');
            Route::get('/purchases/returns', [\App\POS\Http\Controllers\PurchaseOrderController::class, 'returnsIndex'])->name('pos.purchases.returns')->middleware('store.permission:purchase_returns.view');
            Route::get('/purchases/returns/export', [\App\POS\Http\Controllers\PurchaseOrderController::class, 'returnsExport'])->name('pos.purchases.returns.export')->middleware('store.permission:purchase_returns.view');
            Route::get('/purchases/{purchaseOrder}', [\App\POS\Http\Controllers\PurchaseOrderController::class, 'show'])->name('pos.purchases.show')->middleware('store.permission:purchases.view');
            Route::get('/purchases/{purchaseOrder}/edit', [\App\POS\Http\Controllers\PurchaseOrderController::class, 'edit'])
                ->name('pos.purchases.edit')
                ->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:purchases.update']);
            Route::put('/purchases/{purchaseOrder}', [\App\POS\Http\Controllers\PurchaseOrderController::class, 'update'])
                ->name('pos.purchases.update')
                ->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:purchases.update']);
            Route::delete('/purchases/{purchaseOrder}', [\App\POS\Http\Controllers\PurchaseOrderController::class, 'destroy'])
                ->name('pos.purchases.destroy')
                ->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:purchases.delete']);
            Route::post('/purchases/{purchaseOrder}/order', [\App\POS\Http\Controllers\PurchaseOrderController::class, 'order'])->name('pos.purchases.order')->middleware('store.permission:purchases.update');
            Route::post('/purchases/{purchaseOrder}/receive', [\App\POS\Http\Controllers\PurchaseOrderController::class, 'receive'])->name('pos.purchases.receive')->middleware('store.permission:purchases.update');
            Route::post('/purchases/{purchaseOrder}/cancel', [\App\POS\Http\Controllers\PurchaseOrderController::class, 'cancel'])->name('pos.purchases.cancel')->middleware('store.permission:purchases.update');
            Route::post('/purchases/{purchaseOrder}/return', [\App\POS\Http\Controllers\PurchaseOrderController::class, 'returnItems'])->name('pos.purchases.return')->middleware('store.permission:purchase_returns.create');
            Route::post('/purchases/{purchaseOrder}/pay', [\App\POS\Http\Controllers\PurchaseOrderController::class, 'pay'])->name('pos.purchases.pay')->middleware('store.permission:purchases.update');
            Route::post('/purchases/{purchaseOrder}/vouchers', [\App\POS\Http\Controllers\PurchaseOrderController::class, 'uploadVouchers'])->name('pos.purchases.upload-vouchers')->middleware('store.permission:purchases.update');
            Route::delete('/purchases/{purchaseOrder}/vouchers/{index}', [\App\POS\Http\Controllers\PurchaseOrderController::class, 'deleteVoucher'])->name('pos.purchases.delete-voucher')->middleware('store.permission:purchases.update');

            // ── Stock Transfers
            Route::get('/transfers', [\App\Http\Controllers\POS\TransferController::class, 'index'])->name('pos.transfers.index')->middleware('store.permission:transfers.view');
            Route::get('/transfers/create', [\App\Http\Controllers\POS\TransferController::class, 'create'])->name('pos.transfers.create')->middleware('store.permission:transfers.create');
            Route::post('/transfers', [\App\Http\Controllers\POS\TransferController::class, 'store'])->name('pos.transfers.store')->middleware('store.permission:transfers.create');
            Route::get('/transfers/{transfer}', [\App\Http\Controllers\POS\TransferController::class, 'show'])->name('pos.transfers.show')->middleware('store.permission:transfers.view');
            Route::post('/transfers/{transfer}/ship', [\App\Http\Controllers\POS\TransferController::class, 'ship'])->name('pos.transfers.ship')->middleware('store.permission:transfers.update');
            Route::post('/transfers/{transfer}/receive', [\App\Http\Controllers\POS\TransferController::class, 'receive'])->name('pos.transfers.receive')->middleware('store.permission:transfers.update');
            Route::post('/transfers/{transfer}/cancel', [\App\Http\Controllers\POS\TransferController::class, 'cancel'])->name('pos.transfers.cancel')->middleware('store.permission:transfers.update');

            // ── Buy Back (Customer Returns)
            Route::get('/buy-back', [\App\Http\Controllers\POS\BuyBackController::class, 'index'])->name('pos.buybacks.index')->middleware('store.permission:pos_buyback.view');
            Route::get('/buy-back/export', [\App\Http\Controllers\POS\BuyBackController::class, 'export'])->name('pos.buybacks.export')->middleware('store.permission:pos_buyback.view');
            Route::get('/buy-back/create', [\App\Http\Controllers\POS\BuyBackController::class, 'create'])->name('pos.buybacks.create')->middleware('store.permission:pos_buyback.create');
            Route::post('/buy-back', [\App\Http\Controllers\POS\BuyBackController::class, 'store'])->name('pos.buybacks.store')->middleware('store.permission:pos_buyback.create');
            Route::get('/buy-back/{buyback}', [\App\Http\Controllers\POS\BuyBackController::class, 'show'])->name('pos.buybacks.show')->middleware('store.permission:pos_buyback.view');
            Route::post('/buy-back/{buyback}/complete', [\App\Http\Controllers\POS\BuyBackController::class, 'complete'])->name('pos.buybacks.complete')->middleware('store.permission:pos_buyback.update');
            Route::post('/buy-back/{buyback}/cancel', [\App\Http\Controllers\POS\BuyBackController::class, 'cancel'])->name('pos.buybacks.cancel')->middleware('store.permission:pos_buyback.update');

            // ── Sales Returns (roadmap Phase 2) — management layer over PosReturnService
            Route::get('/returns', [\App\Http\Controllers\POS\ReturnsController::class, 'index'])->name('pos.returns.index')->middleware('store.permission:pos_returns.view');
            Route::get('/returns/export', [\App\Http\Controllers\POS\ReturnsController::class, 'export'])->name('pos.returns.export')->middleware('store.permission:pos_returns.view');
            Route::get('/returns/new', [\App\Http\Controllers\POS\ReturnsController::class, 'create'])->name('pos.returns.create')->middleware('store.permission:pos_returns.create');
            Route::get('/returns/{return}', [\App\Http\Controllers\POS\ReturnsController::class, 'show'])->name('pos.returns.show')->middleware('store.permission:pos_returns.view');

            // Opening stock (MVP Phase 2) — staff submits, manager approves → opening_balance ledger.
            Route::get('/opening-stock', [\App\POS\Http\Controllers\OpeningStockController::class, 'index'])->name('pos.opening-stock.index')->middleware('store.permission:opening_stock.view');
            Route::get('/opening-stock/export', [\App\POS\Http\Controllers\OpeningStockController::class, 'export'])->name('pos.opening-stock.export')->middleware('store.permission:opening_stock.view');
            Route::post('/opening-stock', [\App\POS\Http\Controllers\OpeningStockController::class, 'store'])->name('pos.opening-stock.store')->middleware('store.permission:opening_stock.create');
            Route::post('/opening-stock/{openingStockRequest}/approve', [\App\POS\Http\Controllers\OpeningStockController::class, 'approve'])->name('pos.opening-stock.approve')
                ->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:opening_stock.update']);
            Route::post('/opening-stock/{openingStockRequest}/reject', [\App\POS\Http\Controllers\OpeningStockController::class, 'reject'])->name('pos.opening-stock.reject')
                ->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:opening_stock.update']);

            // Opening-stock reconciliation (Phase 2.5) — imported opening stock vs the ledger; manager approves → correction adjustments.
            Route::get('/reconciliation', [\App\POS\Http\Controllers\InventoryReconciliationController::class, 'index'])->name('pos.reconciliation.index')->middleware('store.permission:stock_reconciliation.view');
            Route::get('/reconciliation/export', [\App\POS\Http\Controllers\InventoryReconciliationController::class, 'export'])->name('pos.reconciliation.export')->middleware('store.permission:stock_reconciliation.view');
            Route::post('/reconciliation/approve', [\App\POS\Http\Controllers\InventoryReconciliationController::class, 'approve'])->name('pos.reconciliation.approve')
                ->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:stock_reconciliation.update']);

            // Inventory adjustments (MVP Phase 2, final) — staff submits, manager approves → adjustment_in/out ledger.
            Route::get('/adjustments', [\App\POS\Http\Controllers\InventoryAdjustmentController::class, 'index'])->name('pos.adjustments.index')->middleware('store.permission:stock_adjustments.view');
            Route::get('/adjustments/export', [\App\POS\Http\Controllers\InventoryAdjustmentController::class, 'export'])->name('pos.adjustments.export')->middleware('store.permission:stock_adjustments.view');
            Route::post('/adjustments', [\App\POS\Http\Controllers\InventoryAdjustmentController::class, 'store'])->name('pos.adjustments.store')->middleware('store.permission:stock_adjustments.create');
            Route::post('/adjustments/{inventoryAdjustment}/approve', [\App\POS\Http\Controllers\InventoryAdjustmentController::class, 'approve'])->name('pos.adjustments.approve')
                ->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:stock_adjustments.update']);
            Route::post('/adjustments/{inventoryAdjustment}/reject', [\App\POS\Http\Controllers\InventoryAdjustmentController::class, 'reject'])->name('pos.adjustments.reject')
                ->middleware([EnsureStoreAccess::class . ':store_manager', 'store.permission:stock_adjustments.update']);
        });
    });
