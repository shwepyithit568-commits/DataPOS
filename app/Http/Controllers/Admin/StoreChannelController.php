<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Store;
use App\Services\ModuleBlockerService;
use App\Services\StoreContext;
use App\Services\StorePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreChannelController extends Controller
{
    public function __construct(
        protected ModuleBlockerService $blockerService
    ) {}

    /**
     * Display list of sales channels.
     */
    public function index(Request $request, string $store_slug, StoreContext $context): View
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $channels = [
            Store::CHANNEL_POS => [
                'name_en' => 'Point of Sale (POS)',
                'name_mm' => 'အရောင်းကောင်တာ (POS)',
                'description_en' => 'In-store counter sales, product scanning, checkout and thermal receipts.',
                'description_mm' => 'ဆိုင်တွင်း အရောင်းကောင်တာ၊ ဘားကုဒ်စကင်ဖတ်ခြင်း၊ ငွေရှင်းခြင်းနှင့် ပြေစာထုတ်ခြင်း။',
                'is_protected' => true,
                'dependencies' => [],
            ],
            Store::CHANNEL_ONLINE_STORE => [
                'name_en' => 'Online Storefront & Catalog',
                'name_mm' => 'အွန်လိုင်း ကက်တလောက်နှင့် စတိုး',
                'description_en' => 'Public customer-facing web catalog, categories, product browsing and promotions.',
                'description_mm' => 'ဝယ်ယူသူများ ကြည့်ရှုနိုင်သော ဝဘ်ဆိုဒ် ကက်တလောက်၊ အမျိုးအစားများနှင့် ပရိုမိုးရှင်းများ။',
                'is_protected' => false,
                'dependencies' => [],
            ],
            Store::CHANNEL_ONLINE_ORDERING => [
                'name_en' => 'Online Direct Ordering & Checkout',
                'name_mm' => 'အွန်လိုင်း အော်ဒါတင်စနစ်',
                'description_en' => 'Direct shopping cart checkout and order submission from the online storefront.',
                'description_mm' => 'ဝဘ်ဆိုဒ်မှတဆင့် ဝယ်ယူသူများ တိုက်ရိုက် အော်ဒါတင်ခြင်းနှင့် စီမံခန့်ခွဲခြင်း။',
                'is_protected' => false,
                'dependencies' => [Store::CHANNEL_ONLINE_STORE],
            ],
        ];

        $channelsState = [];
        foreach (array_keys($channels) as $ch) {
            $isEnabled = $store->hasSalesChannel($ch);
            $blockers = $isEnabled ? $this->blockerService->getBlockersForChannel($store, $ch) : [];

            $channelsState[$ch] = [
                'is_enabled' => $isEnabled,
                'blockers' => $blockers,
                'can_disable' => empty($blockers) && !$channels[$ch]['is_protected'],
            ];
        }

        $stats = [
            'total' => count($channels),
            'enabled' => count(array_filter($channelsState, fn ($s) => $s['is_enabled'])),
            'disabled' => count(array_filter($channelsState, fn ($s) => !$s['is_enabled'])),
        ];

        return view('admin.settings.channels', compact('store', 'channels', 'channelsState', 'stats'));
    }

    /**
     * Toggle a sales channel on/off.
     */
    public function toggle(Request $request, string $store_slug, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $validated = $request->validate([
            'channel' => 'required|string|in:pos,online_store,online_ordering',
            'reason' => 'nullable|string|max:255',
        ]);

        $channel = $validated['channel'];

        if ($channel === Store::CHANNEL_POS) {
            return back()->with('error', __('messages.channel_pos_protected'));
        }

        $currentStatus = $store->hasSalesChannel($channel);
        $newStatus = !$currentStatus;

        // If disabling, check for blockers
        if (!$newStatus) {
            $blockers = $this->blockerService->getBlockersForChannel($store, $channel);
            if (!empty($blockers)) {
                $reasons = array_map(fn ($b) => __($b['message_key'], ['count' => $b['count']]), $blockers);
                return back()->with('error', __('messages.channel_cannot_disable_blockers', [
                    'reasons' => implode(', ', $reasons),
                ]));
            }
        }

        // Channel dependency rules
        $channelOverrides = is_array($store->sales_channels) ? $store->sales_channels : [];

        if ($newStatus && $channel === Store::CHANNEL_ONLINE_ORDERING) {
            // Enabling online_ordering requires online_store
            if (!$store->hasSalesChannel(Store::CHANNEL_ONLINE_STORE)) {
                $channelOverrides[Store::CHANNEL_ONLINE_STORE] = true;
            }
        }

        if (!$newStatus && $channel === Store::CHANNEL_ONLINE_STORE) {
            // Disabling online_store also disables online_ordering
            $channelOverrides[Store::CHANNEL_ONLINE_ORDERING] = false;
        }

        $channelOverrides[$channel] = $newStatus;
        $store->sales_channels = $channelOverrides;
        $store->save();

        // Audit Trail
        AuditLog::write(
            storeId: $store->id,
            action: 'store_channel_toggle',
            entityType: 'store',
            entityId: $store->id,
            metadata: [
                'channel' => $channel,
                'previous_status' => $currentStatus,
                'new_status' => $newStatus,
                'reason' => $validated['reason'] ?? null,
                'request_id' => $request->header('X-Request-ID') ?? null,
                'user_agent' => $request->userAgent(),
            ],
            actorId: auth()->id(),
            ipAddress: $request->ip()
        );

        StorePermissionService::invalidateCache();

        return back()->with('success', __('messages.channel_updated_successfully'));
    }
}
