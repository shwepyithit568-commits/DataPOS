<?php

use App\Services\GlassFinderNormalizationAudit;
use App\Services\DataMaintenanceRollbackService;
use App\Services\ProductSkuCleanupService;
use App\Services\ProductSkuUniquenessAudit;
use App\Models\Store;
use App\Models\StorefrontSetting;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('production:create-admin
    {--role= : platform_owner, store_manager, or staff}
    {--store= : Store slug or ID; required for store_manager and staff}
    {--name= : Admin full name}
    {--phone= : Unique phone number using 09 format}
    {--password= : Secure password; prefer interactive entry}
    {--password-confirmation= : Repeat password when using --password}', function () {
    $role = $this->option('role') ?: $this->choice('Role', ['platform_owner', 'store_manager', 'staff'], 'platform_owner');
    $name = $this->option('name') ?: $this->ask('Name');
    $phone = $this->option('phone') ?: $this->ask('Phone');
    $password = $this->option('password') ?: $this->secret('Password');
    $passwordConfirmation = $this->option('password-confirmation') ?: $this->secret('Confirm password');
    $storeRef = $this->option('store');

    $validator = Validator::make([
        'role' => $role,
        'name' => $name,
        'phone' => $phone,
        'password' => $password,
        'password_confirmation' => $passwordConfirmation,
    ], [
        'role' => ['required', Rule::in(['platform_owner', 'store_manager', 'staff'])],
        'name' => ['required', 'string', 'max:255'],
        'phone' => ['required', 'string', 'regex:/^09[0-9]{7,11}$/', 'unique:users,phone'],
        'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    $store = null;
    if (in_array($role, ['store_manager', 'staff'], true)) {
        if (! $storeRef) {
            $this->error('A --store slug or ID is required for store_manager and staff roles.');

            return 1;
        }

        $store = Store::query()
            ->where('slug', $storeRef)
            ->when(is_numeric($storeRef), fn ($query) => $query->orWhere('id', (int) $storeRef))
            ->first();

        if (! $store) {
            $this->error('The requested store was not found.');

            return 1;
        }
    }

    $user = DB::transaction(function () use ($name, $phone, $password, $role, $store) {
        $user = User::create([
            'name' => $name,
            'phone' => $phone,
            'password' => Hash::make($password),
            'role' => $role === 'platform_owner' ? 'platform_owner' : 'customer',
        ]);

        if ($store) {
            $user->stores()->attach($store->id, [
                'role' => $role,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $user;
    });

    Log::info('Production admin account created.', [
        'user_id' => $user->id,
        'role' => $role,
        'store_id' => $store?->id,
    ]);

    $this->info('Production admin account created.');
    $this->line('User ID: ' . $user->id);
    $this->line('Role: ' . $role);
    if ($store) {
        $this->line('Store ID: ' . $store->id);
    }

    return 0;
})->purpose('Create the first production platform or store admin without default credentials');

Artisan::command('production:create-store
    {--name= : Store display name}
    {--slug= : Unique canonical store slug}
    {--phone= : Public store phone number}
    {--viber= : Public Viber contact number}
    {--telegram= : Public Telegram username without @}
    {--address= : Store address}
    {--opening-hours= : Public opening hours}
    {--delivery-info= : Delivery coverage/details}
    {--payment-info= : Payment methods/details}
    {--default-language=my : Supported default locale code}', function () {
    $name = $this->option('name') ?: $this->ask('Store name');
    $slug = $this->option('slug') ?: $this->ask('Canonical store slug');

    $data = [
        'name' => $name,
        'slug' => $slug,
        'phone' => $this->option('phone'),
        'viber_number' => $this->option('viber'),
        'telegram_username' => $this->option('telegram'),
        'address' => $this->option('address'),
        'opening_hours' => $this->option('opening-hours'),
        'delivery_info' => $this->option('delivery-info'),
        'payment_info' => $this->option('payment-info'),
        'default_language' => $this->option('default-language') ?: 'my',
    ];

    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
        'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:stores,slug'],
        'phone' => ['nullable', 'string', 'max:50', 'regex:/^(\+?95|09)[0-9]{7,11}$/'],
        'viber_number' => ['nullable', 'string', 'max:50', 'regex:/^(\+?95|09)[0-9]{7,11}$/'],
        'telegram_username' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_]{5,32}$/'],
        'address' => ['nullable', 'string'],
        'opening_hours' => ['nullable', 'string', 'max:255'],
        'delivery_info' => ['nullable', 'string'],
        'payment_info' => ['nullable', 'string'],
        'default_language' => ['required', Rule::in(array_keys(config('localization.supported', [])))],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    $store = DB::transaction(function () use ($data) {
        $store = Store::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'viber_number' => $data['viber_number'],
            'telegram_username' => $data['telegram_username'],
            'is_active' => true,
        ]);

        StorefrontSetting::create([
            'store_id' => $store->id,
            'store_name' => $data['name'],
            'phone' => $data['phone'],
            'viber_number' => $data['viber_number'],
            'telegram_username' => $data['telegram_username'],
            'address' => $data['address'],
            'opening_hours' => $data['opening_hours'],
            'delivery_info' => $data['delivery_info'],
            'payment_info' => $data['payment_info'],
            'default_language' => $data['default_language'],
        ]);

        return $store;
    });

    Log::info('Production store created.', [
        'store_id' => $store->id,
        'slug' => $store->slug,
    ]);

    $this->info('Production store created.');
    $this->line('Store ID: ' . $store->id);
    $this->line('Store slug: ' . $store->slug);
    $this->line('Storefront URL: /store/' . $store->slug);
    $this->line('Admin URL: /store/' . $store->slug . '/admin/');

    return 0;
})->purpose('Create the first production store and storefront settings without demo data');

// Daily database backup (Hostinger cron: php artisan schedule:run)
Schedule::command('backup:database')->dailyAt('02:00')->withoutOverlapping();

Artisan::command('glass-finder:audit-normalization {--store= : Store slug or ID to audit}', function (GlassFinderNormalizationAudit $audit) {
    $analysis = $audit->analyze($this->option('store'));

    writeGlassFinderNormalizationSummary($this, $analysis, false);
})->purpose('Dry-run audit for legacy Glass Finder normalized codes');

Artisan::command('glass-finder:audit-duplicates {--store= : Store slug or ID to audit}', function (GlassFinderNormalizationAudit $audit) {
    $analysis = $audit->analyze($this->option('store'));

    writeGlassFinderNormalizationSummary($this, $analysis, false);
})->purpose('Dry-run audit for Glass Finder duplicate business keys');

Artisan::command('glass-finder:normalize {--store= : Store slug or ID to normalize} {--apply : Explicitly write safe normalized_glass_code updates}', function (GlassFinderNormalizationAudit $audit) {
    if (! $this->option('apply')) {
        $analysis = $audit->analyze($this->option('store'));
        writeGlassFinderNormalizationSummary($this, $analysis, false);
        $this->warn('Dry-run only. Re-run with --apply after taking a database backup.');

        return 0;
    }

    $this->warn('Apply mode requested. Confirm a fresh database backup exists before running this in production.');
    $this->line('No schema migration or row deletion will be performed.');
    Artisan::call('migrate:status');
    $migrationStatus = Artisan::output();
    if (str_contains($migrationStatus, 'Pending')) {
        $this->warn('Pending migrations detected. Review them before running this in production.');
    } else {
        $this->line('Migration status checked: no pending migrations reported.');
    }

    $result = $audit->apply($this->option('store'));
    writeGlassFinderNormalizationSummary($this, $result['analysis'], true);
    $this->info('Execution ID: ' . $result['execution_id']);
    $this->info('Rows updated: ' . $result['changed_count']);
    $this->line('Changed row IDs: ' . ($result['changed_ids'] === [] ? 'none' : implode(', ', $result['changed_ids'])));
    $this->line('Rollback dry-run: php artisan data-maintenance:rollback ' . $result['execution_id']);
    $this->line('Rollback apply: php artisan data-maintenance:rollback ' . $result['execution_id'] . ' --apply');

    return 0;
})->purpose('Safely normalize legacy Glass Finder normalized codes with explicit apply mode');

Artisan::command('products:audit-sku-uniqueness {--store= : Store slug or ID to audit}', function (ProductSkuUniquenessAudit $audit) {
    $analysis = $audit->analyze($this->option('store'));
    $summary = $analysis['summary'];

    $this->info('Product SKU uniqueness DRY-RUN summary');
    $this->line('Total products inspected: ' . $summary['total_products_inspected']);
    $this->line('Duplicate SKU groups: ' . $summary['duplicate_sku_groups']);
    $this->line('Blank SKU rows: ' . $summary['blank_sku_rows']);
    $this->line('Case-only duplicate groups: ' . $summary['case_only_duplicate_groups']);
    $this->line('Whitespace-normalized duplicate groups: ' . $summary['whitespace_normalized_duplicate_groups']);
    $this->line('Affected stores: ' . (empty($summary['affected_stores']) ? 'none' : implode(', ', $summary['affected_stores'])));
    $this->line('Affected product IDs: ' . (empty($summary['affected_product_ids']) ? 'none' : implode(', ', $summary['affected_product_ids'])));

    foreach ($analysis['affected_rows']->take(20) as $row) {
        $this->line(" - #{$row['id']} {$row['store_slug']} sku={$row['sku']} name={$row['name']} stock={$row['stock_status']}");
    }
})->purpose('Dry-run audit for product SKU uniqueness risks');

Artisan::command('products:cleanup-skus {--store= : Store slug or ID to clean} {--map= : JSON mapping file of product ID to replacement SKU} {--apply : Explicitly write operator-provided SKU resolutions}', function (ProductSkuCleanupService $cleanup) {
    $mappingFile = $this->option('map');
    if (!$mappingFile) {
        $this->error('A --map JSON file is required. No SKUs will be changed automatically.');
        return 1;
    }

    if (!$this->option('apply')) {
        $preview = $cleanup->preview($mappingFile, $this->option('store'));
        $this->info('Product SKU cleanup DRY-RUN summary');
        $this->line('Mapped changes: ' . $preview['changes']->count());
        foreach ($preview['changes'] as $change) {
            $this->line(" - #{$change['id']} {$change['store_slug']} {$change['old_value']} => {$change['new_value']}");
        }
        $this->warn('Dry-run only. Re-run with --apply after reviewing the mapping file and backup.');
        return 0;
    }

    $result = $cleanup->apply($mappingFile, $this->option('store'), 'artisan');
    $this->info('Product SKU cleanup APPLY summary');
    $this->info('Execution ID: ' . $result['execution_id']);
    $this->line('Rows updated: ' . $result['changed_count']);
    $this->line('Changed product IDs: ' . ($result['changed_ids'] === [] ? 'none' : implode(', ', $result['changed_ids'])));
    $this->line('Rollback dry-run: php artisan data-maintenance:rollback ' . $result['execution_id']);
    $this->line('Rollback apply: php artisan data-maintenance:rollback ' . $result['execution_id'] . ' --apply');
    return 0;
})->purpose('Safely apply operator-provided product SKU resolutions');

Artisan::command('data-maintenance:rollback {execution_id : Maintenance execution ID} {--store= : Optional store slug or ID} {--apply : Explicitly restore logged old values}', function (DataMaintenanceRollbackService $rollback) {
    $executionId = $this->argument('execution_id');

    if (!$this->option('apply')) {
        $preview = $rollback->preview($executionId, $this->option('store'));
        $this->info('Data maintenance rollback DRY-RUN summary');
        $this->line('Execution ID: ' . $executionId);
        $this->line('Total logs: ' . $preview['total_logs']);
        $this->line('Reversible rows: ' . $preview['summary']['reversible_count']);
        $this->line('Skipped rows: ' . $preview['summary']['skipped_count']);
        foreach ($preview['skipped'] as $row) {
            $this->warn(" - skipped #{$row['record_id']}: {$row['reason']}");
        }
        $this->warn('Dry-run only. Re-run with --apply after reviewing backup and skipped rows.');
        return 0;
    }

    $result = $rollback->apply($executionId, $this->option('store'));
    $this->info('Data maintenance rollback APPLY summary');
    $this->line('Execution ID: ' . $executionId);
    $this->line('Rows restored: ' . $result['restored_count']);
    $this->line('Restored record IDs: ' . ($result['restored_ids'] === [] ? 'none' : implode(', ', $result['restored_ids'])));
    $this->line('Skipped rows: ' . $result['analysis']['summary']['skipped_count']);
    return 0;
})->purpose('Rollback one durable data maintenance execution ID');

if (! function_exists('writeGlassFinderNormalizationSummary')) {
    function writeGlassFinderNormalizationSummary($command, array $analysis, bool $applied): void
    {
        $summary = $analysis['summary'];
        $mode = $applied ? 'APPLY' : 'DRY-RUN';

        $command->info("Glass Finder normalization {$mode} summary");
        $command->line('Rows inspected: ' . $summary['rows_inspected']);
        $command->line('Already normalized rows: ' . $summary['already_normalized_rows']);
        $command->line('Rows requiring updates: ' . $summary['rows_requiring_updates']);
        $command->line('Safe update rows: ' . $summary['safe_update_rows']);
        $command->line('Rows blocked from automatic update: ' . $summary['rows_blocked_from_automatic_update']);
        $command->line('Exact duplicate groups: ' . $summary['exact_duplicate_groups']);
        $command->line('Conflicting duplicate groups: ' . $summary['conflicting_duplicate_groups']);
        $command->line('Valid compatibility groups: ' . $summary['valid_compatibility_groups']);
        $command->line('Affected stores: ' . (empty($summary['affected_stores']) ? 'none' : implode(', ', $summary['affected_stores'])));
        $command->line('Affected phone models: ' . (empty($summary['affected_phone_models']) ? 'none' : implode(', ', $summary['affected_phone_models'])));

        $blockedRows = $analysis['blocked_rows'];
        if ($blockedRows->isNotEmpty()) {
            $command->warn('Blocked rows:');
            foreach ($blockedRows->take(20) as $row) {
                $command->line(" - #{$row['id']} {$row['store_slug']} {$row['phone_model']} current={$row['current_normalized_glass_code']} expected={$row['expected_normalized_glass_code']}");
            }
        }

        $safeRows = $analysis['safe_update_rows'];
        if ($safeRows->isNotEmpty()) {
            $command->line('Safe rows:');
            foreach ($safeRows->take(20) as $row) {
                $command->line(" - #{$row['id']} {$row['store_slug']} {$row['phone_model']} current={$row['current_normalized_glass_code']} expected={$row['expected_normalized_glass_code']}");
            }
        }
    }
}

// Web-push queue drain for shared hosting without a persistent worker:
// run one worker pass every minute (Laravel's scheduler needs a single cron
// line: `* * * * * php artisan schedule:run`). Skipped automatically when the
// queue driver is sync (notifications then deliver inline).
Schedule::command('queue:work', ['--once', '--stop-when-empty', '--tries=3', '--timeout=60'])
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn () => config('queue.default') !== 'sync')
    ->appendOutputTo(storage_path('logs/queue-worker.log'));
