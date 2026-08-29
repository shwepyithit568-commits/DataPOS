<?php

use App\Http\Controllers\Api\SyncApiController;
use App\Http\Controllers\PushNotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Push-subscription endpoints and Offline-to-Cloud Auto Sync API endpoints.
|
*/

Route::middleware(['web'])->prefix('push')->group(function () {
    // Store (create/update) a browser push subscription.
    Route::post('/subscribe', [PushNotificationController::class, 'subscribe']);

    // Remove a browser push subscription by endpoint.
    Route::delete('/unsubscribe', [PushNotificationController::class, 'unsubscribe']);

    // Send a test notification to all subscribers (admin only).
    Route::post('/test', [PushNotificationController::class, 'test']);
});

Route::prefix('v1/store/{slug}/sync')->group(function () {
    // Batch ingest offline operational records
    Route::post('/push', [SyncApiController::class, 'push']);

    // Delta pull for products, categories, customers
    Route::get('/pull', [SyncApiController::class, 'pull']);

    // Live sync status and health stats
    Route::get('/status', [SyncApiController::class, 'status']);

    // Trigger immediate sync
    Route::post('/trigger', [SyncApiController::class, 'trigger']);
});
