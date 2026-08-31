<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'set.locale' => \App\Http\Middleware\SetLocale::class,
            'platform_owner' => \App\Http\Middleware\EnsurePlatformOwner::class,
            'cache.public_page' => \App\Http\Middleware\CachePublicPage::class,
            'store.capability' => \App\Http\Middleware\CheckStoreCapability::class,
            'finance_access' => \App\Http\Middleware\EnsureFinanceAccess::class,
        ]);

        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        // Clear the request-scoped ThemeContext preview override after every
        // response so it can never leak across requests in long-running
        // runtimes (Octane) or feature tests.
        $middleware->append(\App\Http\Middleware\ResetThemePreview::class);
    })
    ->withCommands([
        \App\POS\Console\InventoryReconcileCommand::class,
        \App\POS\Console\EnsureStoreLocationsCommand::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
