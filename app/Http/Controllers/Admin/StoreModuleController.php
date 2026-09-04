<?php

namespace App\Http\Controllers\Admin;

use App\Capabilities\CapabilityRegistry;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\ModuleBlockerService;
use App\Services\StoreContext;
use App\Services\StorePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        abort_if(!$store, 404);

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
     * Toggle a capability module on/off.
     */
    public function toggle(Request $request, string $store_slug, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $validated = $request->validate([
            'capability' => 'required|string',
            'reason' => 'nullable|string|max:255',
        ]);

        $capability = $validated['capability'];

        if (!CapabilityRegistry::has($capability)) {
            return back()->with('error', __('messages.invalid_capability'));
        }

        $currentStatus = $store->hasCapability($capability);
        $newStatus = !$currentStatus;

        // If disabling, check for blockers
        if (!$newStatus) {
            $blockers = $this->blockerService->getBlockersForCapability($store, $capability);
            if (!empty($blockers)) {
                $reasons = array_map(fn ($b) => __($b['message_key'], ['count' => $b['count']]), $blockers);
                return back()->with('error', __('messages.module_cannot_disable_blockers', [
                    'reasons' => implode(', ', $reasons),
                ]));
            }
        }

        $overrides = is_array($store->capabilities_override) ? $store->capabilities_override : [];
        $overrides[$capability] = $newStatus;
        $store->capabilities_override = $overrides;
        $store->save();

        // Audit Trail
        AuditLog::write(
            storeId: $store->id,
            action: 'store_module_toggle',
            entityType: 'store',
            entityId: $store->id,
            metadata: [
                'capability' => $capability,
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

        return back()->with('success', __('messages.module_updated_successfully'));
    }
}
