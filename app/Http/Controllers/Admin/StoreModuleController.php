<?php

namespace App\Http\Controllers\Admin;

use App\Capabilities\CapabilityRegistry;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Store;
use App\Services\ModuleBlockerService;
use App\Services\StoreContext;
use App\Services\StorePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StoreModuleController extends Controller
{
    public function __construct(
        protected ModuleBlockerService $blockerService
    ) {}

    /**
     * Display list of business capability modules.
     */
    public function index(Request $request, string $store_slug, StoreContext $context): View
    {
        $store = $context->getStore();
        abort_if(!$store, 404, __('messages.store_not_found'));

        $user = $request->user();
        abort_unless(
            $user && ($user->isPlatformOwner() || $user->isStoreOwner($store->id)),
            403,
            __('messages.unauthorized')
        );

        $groupedCapabilities = CapabilityRegistry::grouped();
        $capabilitiesState = [];

        foreach (CapabilityRegistry::all() as $cap) {
            $isEnabled = $store->hasCapability($cap);
            $blockers = $isEnabled ? $this->blockerService->getBlockersForCapability($store, $cap) : [];

            $capabilitiesState[$cap] = [
                'is_enabled' => $isEnabled,
                'blockers' => $blockers,
                'can_disable' => empty($blockers),
            ];
        }

        $stats = [
            'total' => count(CapabilityRegistry::all()),
            'enabled' => count(array_filter($capabilitiesState, fn ($s) => $s['is_enabled'])),
            'disabled' => count(array_filter($capabilitiesState, fn ($s) => !$s['is_enabled'])),
        ];

        return view('admin.settings.modules', compact('store', 'groupedCapabilities', 'capabilitiesState', 'stats'));
    }

    /**
     * Toggle a capability module on/off with pessimistic row locking and atomic transaction.
     */
    public function toggle(Request $request, string $store_slug, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404, __('messages.store_not_found'));

        $user = $request->user();
        abort_unless(
            $user && ($user->isPlatformOwner() || $user->isStoreOwner($store->id)),
            403,
            __('messages.unauthorized')
        );

        $validated = $request->validate([
            'capability' => 'required|string',
            'reason' => 'nullable|string|max:255',
        ]);

        $capability = $validated['capability'];

        if (!CapabilityRegistry::has($capability)) {
            return back()->with('error', __('messages.invalid_capability'));
        }

        try {
            DB::transaction(function () use ($store, $capability, $validated, $request, $user) {
                /** @var Store $lockedStore */
                $lockedStore = Store::where('id', $store->id)->lockForUpdate()->firstOrFail();

                $currentStatus = $lockedStore->hasCapability($capability);
                $newStatus = !$currentStatus;

                // If disabling, check for domain blockers
                if (!$newStatus) {
                    $blockers = $this->blockerService->getBlockersForCapability($lockedStore, $capability);
                    if (!empty($blockers)) {
                        $reasons = array_map(fn ($b) => __($b['message_key'], ['count' => $b['count']]), $blockers);
                        throw new \DomainException(__('messages.module_cannot_disable_blockers', [
                            'reasons' => implode(', ', $reasons),
                        ]));
                    }
                }

                $overrides = is_array($lockedStore->capabilities_override) ? $lockedStore->capabilities_override : [];
                $overrides[$capability] = $newStatus;
                $lockedStore->capabilities_override = $overrides;
                $lockedStore->save();

                // Audit Trail inside same transaction
                AuditLog::write(
                    storeId: $lockedStore->id,
                    action: 'store_module_toggle',
                    entityType: 'store',
                    entityId: $lockedStore->id,
                    metadata: [
                        'capability' => $capability,
                        'previous_status' => $currentStatus,
                        'new_status' => $newStatus,
                        'reason' => $validated['reason'] ?? null,
                        'request_id' => $request->header('X-Request-ID') ?? null,
                        'user_agent' => $request->userAgent(),
                    ],
                    actorId: $user->id,
                    ipAddress: $request->ip()
                );
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        StorePermissionService::invalidateCache();

        return back()->with('success', __('messages.module_updated_successfully'));
    }
}
