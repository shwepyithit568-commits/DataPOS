<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Negative stock policy (SoT §14.3 / target-design §2.8)
    |--------------------------------------------------------------------------
    |
    | Default: negative available stock is BLOCKED. A specifically authorized
    | manager override is designed later (Open Decision #16); every override
    | must be audited and visibly reported. Until then this stays false.
    |
    */

    'allow_negative_stock' => env('INVENTORY_ALLOW_NEGATIVE_STOCK', false),

    /*
    |--------------------------------------------------------------------------
    | Derived products.stock_status cache
    |--------------------------------------------------------------------------
    |
    | products.stock_status is a migration-period derived compatibility field
    | (SoT §5). The ledger stays authoritative; the service refreshes this cache
    | after each movement. Set to false to skip the cache refresh entirely.
    |
    */

    'sync_stock_status_cache' => env('INVENTORY_SYNC_STOCK_STATUS', true),

];
