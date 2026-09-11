<?php
// Simulate what the browser would do when visiting the pilot-import page
// Run the controller action and catch any render errors

$store = \App\Models\Store::where('slug', 'acdc-mobile')->first();
$user = \App\Models\User::find(11);

echo "Store: {$store->name} (id={$store->id})\n";
echo "User: {$user->name}\n\n";

// Check if the view file itself compiles
try {
    $blade = new \Illuminate\View\Compilers\BladeCompiler(
        app('files'),
        storage_path('framework/views')
    );
    $viewPath = resource_path('views/admin/pilot_import/index.blade.php');
    $compiled = $blade->compileString(file_get_contents($viewPath));
    echo "Blade compilation: OK\n";
} catch (\Throwable $e) {
    echo "Blade compilation ERROR: " . $e->getMessage() . "\n";
}

// Check if the layout view compiles
try {
    $layoutPath = resource_path('views/layouts/admin/app.blade.php');
    $compiled = $blade->compileString(file_get_contents($layoutPath));
    echo "Layout compilation: OK\n";
} catch (\Throwable $e) {
    echo "Layout compilation ERROR: " . $e->getMessage() . "\n";
}

// Try to render the view with required data
try {
    $context = app(\App\Services\StoreContext::class);
    
    // Simulate request
    $request = \Illuminate\Http\Request::create("/store/acdc-mobile/admin/pilot-import", 'GET');
    $request->setUserResolver(fn() => $user);
    app()->instance('request', $request);
    
    // Set auth
    \Illuminate\Support\Facades\Auth::login($user);
    
    // Set store context
    $context->setStore($store);
    
    $tab = 'scenarios';
    $stats = [
        'products' => \App\Models\Product::where('store_id', $store->id)->count(),
        'categories' => \App\Models\Category::where('store_id', $store->id)->count(),
        'brands' => \App\Models\Brand::where('store_id', $store->id)->count(),
        'suppliers' => \App\Models\Supplier::where('store_id', $store->id)->count(),
        'customers' => \App\Models\User::whereHas('stores', fn ($q) => $q->where('store_id', $store->id))->count(),
    ];
    $histories = \App\Models\ImportHistory::where('store_id', $store->id)->with('user')->latest()->take(10)->get();
    $summary = [
        'total_imports' => \App\Models\ImportHistory::where('store_id', $store->id)->count(),
        'successful_rows' => \App\Models\ImportHistory::where('store_id', $store->id)->sum('success_rows'),
        'failed_rows' => \App\Models\ImportHistory::where('store_id', $store->id)->sum('failed_rows'),
    ];
    $demoScenarios = app(\App\Services\DemoBusinessScenarioService::class)->scenarios();
    $demoScenariosEnabled = true;
    
    echo "Data preparation: OK\n";
    echo "Stats: " . json_encode($stats) . "\n";
    
    // Try rendering the view
    $html = view('admin.pilot_import.index', compact('store', 'tab', 'stats', 'histories', 'summary', 'demoScenarios', 'demoScenariosEnabled'))->render();
    echo "View render: OK (length=" . strlen($html) . ")\n";
    
} catch (\Throwable $e) {
    echo "\nView render ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    
    // Check for previous exception
    if ($prev = $e->getPrevious()) {
        echo "Previous: " . $prev->getMessage() . "\n";
        echo "File: " . $prev->getFile() . ":" . $prev->getLine() . "\n";
    }
}

exit(0);
