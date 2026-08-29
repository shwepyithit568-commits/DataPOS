<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MembershipTier;
use App\POS\Services\MembershipLoyaltyService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MembershipLoyaltyController extends Controller
{
    public function __construct(
        protected MembershipLoyaltyService $membershipService
    ) {
    }

    /**
     * Display the Membership Tiers & Loyalty Points Dashboard.
     */
    public function index(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $tiers = $this->membershipService->getTiers($store);
        $stats = $this->membershipService->getSummaryStats($store);
        $members = $this->membershipService->getMembers($store, $request->all(), 15);

        return view('admin.membership.index', compact('store', 'tiers', 'stats', 'members'));
    }

    /**
     * Store a new membership tier.
     */
    public function storeTier(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('membership_tiers', 'code')->where('store_id', $store->id),
            ],
            'min_spending' => 'required|numeric|min:0',
            'discount_percent' => 'required|numeric|min:0|max:100',
            'point_multiplier' => 'required|numeric|min:0.1|max:10',
            'badge_color' => 'required|string|in:slate,blue,amber,purple,emerald,rose',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $this->membershipService->saveTier($store, $validated, null, $request->user());

        return redirect()->route('store.admin.membership.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.membership_tier_created'));
    }

    /**
     * Update an existing tier.
     */
    public function updateTier(StoreContext $context, string $store_slug, int|string $tier, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $membershipTier = MembershipTier::where('store_id', $store->id)->findOrFail($tier);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('membership_tiers', 'code')->where('store_id', $store->id)->ignore($membershipTier->id),
            ],
            'min_spending' => 'required|numeric|min:0',
            'discount_percent' => 'required|numeric|min:0|max:100',
            'point_multiplier' => 'required|numeric|min:0.1|max:10',
            'badge_color' => 'required|string|in:slate,blue,amber,purple,emerald,rose',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $this->membershipService->saveTier($store, $validated, $membershipTier, $request->user());

        return redirect()->route('store.admin.membership.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.membership_tier_updated'));
    }

    /**
     * Delete a membership tier.
     */
    public function destroyTier(StoreContext $context, string $store_slug, int|string $tier, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $membershipTier = MembershipTier::where('store_id', $store->id)->findOrFail($tier);

        if ($membershipTier->is_default) {
            return back()->withErrors(['error' => __('messages.membership_cannot_delete_default')]);
        }

        $this->membershipService->deleteTier($store, $membershipTier, $request->user());

        return redirect()->route('store.admin.membership.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.membership_tier_deleted'));
    }

    /**
     * Manually adjust loyalty points for a customer.
     */
    public function adjustPoints(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $validated = $request->validate([
            'customer_id' => 'required|integer',
            'points' => 'required|integer',
            'type' => 'required|string|in:bonus,adjusted,earned,redeemed',
            'notes' => 'nullable|string|max:500',
        ]);

        $this->membershipService->adjustPoints(
            $store,
            (int) $validated['customer_id'],
            (int) $validated['points'],
            $validated['type'],
            $validated['notes'] ?? null,
            $request->user()
        );

        return redirect()->route('store.admin.membership.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.membership_points_adjusted'));
    }

    /**
     * Manually assign tier to a customer.
     */
    public function assignTier(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $validated = $request->validate([
            'customer_id' => 'required|integer',
            'tier_id' => ['required', 'integer', Rule::exists('membership_tiers', 'id')->where('store_id', $store->id)],
        ]);

        $this->membershipService->assignTier(
            $store,
            (int) $validated['customer_id'],
            (int) $validated['tier_id'],
            $request->user()
        );

        return redirect()->route('store.admin.membership.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.membership_tier_assigned'));
    }
}
