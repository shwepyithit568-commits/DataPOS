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
