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
