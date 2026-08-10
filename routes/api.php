<?php

use App\Http\Controllers\PushNotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Push-subscription endpoints used by the storefront's service worker and by
| the admin push-management page. These are same-origin JSON endpoints, so
| they reuse the `web` middleware group (session auth + CSRF) instead of the
| stateless `api` group — consistent with the rest of this session-based app.
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
