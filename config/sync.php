<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shop ⇄ Cloud replication
    |--------------------------------------------------------------------------
    |
    | A shop in Myanmar loses its internet connection regularly. The counter
    | must not stop because of that, so the supported shape is:
    |
    |   standalone  — this installation IS the shop's source of truth. Sales are
    |                 posted locally, nothing is replicated. (A single-host shop
    |                 that serves the admin/POS over its own LAN uses this too.)
    |
    |   terminal    — a shop installation that keeps selling locally and PUSHES
    |                 every posted sale to a central installation (the online
    |                 one that serves the storefront) whenever the internet is
    |                 available. Nothing is ever blocked on the central being
    |                 reachable.
    |
    | The pull direction is deliberately READ-ONLY: it reports the central's
    | catalog (prices, stock, customers) without writing it here. Applying it
    | would mean mapping another database's row ids onto this one, and a
    | collision would silently rewrite a local product's price or stock history.
    | That needs a deliberate "who owns the catalog" decision first.
    |
    */

    'enabled' => (bool) env('DATAPOS_SYNC_ENABLED', false),

    'role' => env('DATAPOS_SYNC_ROLE', 'standalone'),

    'central_url' => rtrim((string) env('DATAPOS_SYNC_CENTRAL_URL', ''), '/'),

    'store_slug' => (string) env('DATAPOS_SYNC_STORE_SLUG', ''),

    // Issued from the central's Sync screen (Stores → Sync → Generate key).
    'api_key' => (string) env('DATAPOS_SYNC_API_KEY', ''),

    'timeout' => (int) env('DATAPOS_SYNC_TIMEOUT', 20),

    // Local shops often run a self-signed certificate on a LAN box. Turning
    // this off is a real weakening — only do it for a LAN/IP central.
    'verify_tls' => (bool) env('DATAPOS_SYNC_VERIFY_TLS', true),

    'batch_size' => (int) env('DATAPOS_SYNC_BATCH_SIZE', 50),

    // Give up retrying a record after this many attempts (it stays visible on
    // the Sync screen as failed rather than retrying forever).
    'max_attempts' => (int) env('DATAPOS_SYNC_MAX_ATTEMPTS', 10),

];
