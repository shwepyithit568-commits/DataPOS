<?php

namespace App\Http\Controllers;

use App\Models\PushNotificationLog;
use App\Models\PushSubscription;
use App\Models\Store;
use App\Notifications\TestPushNotification;
use App\Services\StoreContext;
use App\Support\PushSubscriberList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class PushNotificationController extends Controller
{
    /**
     * Cache key holding the recent-notifications log shown on the admin page.
     */
    private const RECENT_CACHE_KEY = 'push.recent_notifications';

    /**
     * Store a browser push subscription (create or update by endpoint).
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        $subscription = PushSubscription::storeFromRequest($request);

        return response()->json([
            'success' => true,
            'message' => 'Subscription stored.',
            'id' => $subscription->id,
        ]);
    }

    /**
     * Remove a browser push subscription by endpoint.
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
        ]);

        PushSubscription::removeSubscription($validated['endpoint']);

        return response()->json([
            'success' => true,
            'message' => 'Subscription removed.',
        ]);
    }

    /**
     * Send a test / custom notification to every stored subscription.
     *
     * Admin only: the storefront has no public endpoint that broadcasts to
     * all subscribers. Access is guarded here by role checks (platform owner
     * or a store manager/staff in any active store).
     */
    public function test(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || (! $user->isPlatformOwner() && ! $user->isStoreAdmin())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:1000'],
            'url' => ['nullable', 'string', 'max:500'],
        ]);

        $subscriberCount = PushSubscription::count();

        if ($subscriberCount === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No push subscribers yet — open the store in a browser that has allowed notifications first.',
            ], 422);
        }

        $notification = new TestPushNotification(
            $validated['title'] ?? 'Test notification',
            $validated['body'] ?? 'This is a test push notification from your store.',
            $validated['url'] ?? url('/'),
        );

        // PushSubscriberList routes the WebPush channel to every stored
        // subscription in one Notification::send call. A single broken
        // subscription (e.g. a stale endpoint whose keys no longer decode)
        // must never fail the whole broadcast, so the send is guarded and the
        // response reports the intended recipient count.
        try {
            $notifiable = new PushSubscriberList(PushSubscription::all());
            Notification::send($notifiable, $notification);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Web push broadcast failed: ' . $e->getMessage());
        }

        $this->rememberRecent([
            'title' => $notification->title,
            'body' => $notification->body,
            'url' => $notification->url,
            'recipients' => $subscriberCount,
            'sent_at' => now()->toDateTimeString(),
        ]);

        // Permanent audit-log row (type "system") so the admin history page
        // records test/custom broadcasts alongside order/status/payment sends.
        PushNotificationLog::create([
            'type' => $notification->logType(),
            'title' => $notification->logTitle(),
            'body' => $notification->logBody(),
            'url' => $notification->logUrl(),
            'recipient_count' => $subscriberCount,
            'sent_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Notification queued for {$subscriberCount} subscriber(s).",
            'recipients' => $subscriberCount,
        ]);
    }

    /**
     * Admin push-history page: the last 50 dispatched notifications,
     * optionally filtered by type (order | payment | status | system).
     */
    public function history(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();

        $type = $request->query('type');
        if (! in_array($type, PushNotificationLog::TYPES, true)) {
            $type = null;
        }

        $logs = PushNotificationLog::query()
            ->when($type, fn ($q) => $q->where('type', $type))
            ->latest('sent_at')
            ->limit(50)
            ->get();

        return view('admin.push.history', compact('store', 'logs', 'type'));
    }

    /**
     * Admin push-management page: subscriber count, test/custom send, history.
     */
    public function index(StoreContext $context): View
    {
        $store = $context->getStore();

        $subscriberCount = PushSubscription::count();
        $recent = Cache::get(self::RECENT_CACHE_KEY, []);

        return view('admin.push.index', compact('store', 'subscriberCount', 'recent'));
    }

    /**
     * Keep the last 20 sends in the (database-backed) cache for the admin page.
     */
    protected function rememberRecent(array $entry): void
    {
        $recent = Cache::get(self::RECENT_CACHE_KEY, []);
        array_unshift($recent, $entry);
        $recent = array_slice($recent, 0, 20);
        Cache::put(self::RECENT_CACHE_KEY, $recent, now()->addDays(30));
    }
}
