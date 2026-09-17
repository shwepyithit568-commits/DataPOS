<?php

use App\Models\Store;
use App\Models\StorefrontSetting;
use App\Services\StoreContext;
use App\Support\CurrencyFormatter;

if (! function_exists('format_currency')) {
    /**
     * Format a numerical amount into accounting/currency string according to the store's settings.
     */
    function format_currency(float|int|string|null $amount, ?Store $store = null): string
    {
        if (! $store && app()->bound(StoreContext::class)) {
            try {
                $store = app(StoreContext::class)->getStore();
            } catch (\Throwable) {
                $store = null;
            }
        }

        $setting = $store?->setting;
        $currencySettings = $setting?->currency_settings ?? [];

        return CurrencyFormatter::format($amount, $currencySettings);
    }
}

if (! function_exists('bc_sum')) {
    /**
     * Sum money/quantity values exactly.
     *
     * Use this instead of array_sum()/`+=` whenever the numbers are decimals:
     * PHP floats cannot represent most decimal fractions, so a running total
     * accumulates error. Returns a decimal string.
     */
    function bc_sum(iterable $values, int $scale = 2): string
    {
        $total = '0';

        foreach ($values as $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $total = bcadd($total, (string) $value, $scale);
        }

        return $total;
    }
}

if (! function_exists('exact_sum')) {
    /**
     * Exact SUM of a column (or SQL expression) over a query, as a decimal string.
     *
     * MySQL sums DECIMAL columns exactly, so its answer is taken verbatim.
     * SQLite has no DECIMAL type — `SUM()` runs in REAL (a double) and drifts
     * once a report covers a few hundred thousand rows (measured: ~0.01 kyat at
     * 200k rows, 0.19 at 1M), so the rows are totalled here with bcmath instead.
     * Callers therefore get the same figure on both engines.
     *
     * The query should be a plain where-clause query; a custom select list is
     * discarded so only the aggregated value is fetched.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     */
    function exact_sum($query, string $columnOrExpression, int $scale = 2): string
    {
        /** @var \Illuminate\Database\Connection $connection */
        $connection = $query->getConnection();

        if ($connection->getDriverName() !== 'sqlite') {
            $row = (clone $query)
                ->selectRaw('COALESCE(SUM(' . $columnOrExpression . '), 0) AS exact_sum_value')
                ->first();

            $value = is_object($row) ? ($row->exact_sum_value ?? null) : null;

            return bcadd((string) ($value ?? '0'), '0', $scale);
        }

        $total = '0';

        $rows = (clone $query)
            ->selectRaw('(' . $columnOrExpression . ') AS exact_sum_value')
            ->cursor();

        foreach ($rows as $row) {
            $value = $row->exact_sum_value ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $total = bcadd($total, (string) $value, $scale);
        }

        return $total;
    }
}

if (! function_exists('format_quantity')) {
    /**
     * Format a numerical quantity according to the store's currency/number settings.
     */
    function format_quantity(float|int|string|null $quantity, ?Store $store = null): string
    {
        if (! $store && app()->bound(StoreContext::class)) {
            try {
                $store = app(StoreContext::class)->getStore();
            } catch (\Throwable) {
                $store = null;
            }
        }

        $setting = $store?->setting;
        $currencySettings = $setting?->currency_settings ?? [];

        return CurrencyFormatter::formatQuantity($quantity, $currencySettings);
    }
}

if (! function_exists('currency_symbol')) {
    /**
     * Get the active store currency symbol according to the store's settings.
     */
    function currency_symbol(?Store $store = null): string
    {
        if (! $store && app()->bound(StoreContext::class)) {
            try {
                $store = app(StoreContext::class)->getStore();
            } catch (\Throwable) {
                $store = null;
            }
        }

        $setting = $store?->setting;
        $currencySettings = $setting?->currency_settings ?? [];

        return $currencySettings['currency_symbol'] ?? CurrencyFormatter::DEFAULT_SETTINGS['currency_symbol'];
    }
}

if (! function_exists('currency_code')) {
    /**
     * Get the active store currency code (e.g. MMK, USD) according to the store's settings.
     */
    function currency_code(?Store $store = null): string
    {
        if (! $store && app()->bound(StoreContext::class)) {
            try {
                $store = app(StoreContext::class)->getStore();
            } catch (\Throwable) {
                $store = null;
            }
        }

        $setting = $store?->setting;
        $currencySettings = $setting?->currency_settings ?? [];

        return $currencySettings['currency_code'] ?? CurrencyFormatter::DEFAULT_SETTINGS['currency_code'];
    }
}

if (! function_exists('store_can')) {
    /**
     * Determine if the current active store has a specific capability,
     * or if the authenticated user has a specific staff permission in this store.
     */
    function store_can(string $identifier, ?Store $store = null): bool
    {
        if (! $store && app()->bound(StoreContext::class)) {
            try {
                $store = app(StoreContext::class)->getStore();
            } catch (\Throwable) {
                $store = null;
            }
        }

        if (! $store) {
            return false;
        }

        static $capabilities = null;
        if ($capabilities === null) {
            $capabilities = array_values((new \ReflectionClass(\App\Capabilities\Capability::class))->getConstants());
        }

        // If it is a defined store capability, strictly check store capability
        if (in_array($identifier, $capabilities, true)) {
            return $store->hasCapability($identifier);
        }

        // Otherwise evaluate as a staff permission for the authenticated user
        $user = auth()->user();
        if ($user) {
            return app(\App\Services\StorePermissionService::class)->can($user, $store, $identifier);
        }

        return false;
    }
}
