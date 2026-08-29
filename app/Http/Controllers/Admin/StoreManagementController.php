<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StorefrontSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Platform-owner-only store management (multi-store-ready plan Phase 2).
 *
 * Rules enforced here (backend is authoritative, UI is just a convenience):
 * - Only platform owners may create/edit/deactivate stores.
 * - Exactly one store may be primary at a time.
 * - "Destroy" means deactivate (is_active = false) — never a hard delete,
 *   because orders/reviews/history keep referencing the store.
 */
class StoreManagementController extends Controller
{
    public function index(): View
    {
        $stores = Store::withCount('products')
            ->with('setting')
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.stores.index', compact('stores'));
    }

    public function create(): View
    {
        $editions = \App\Services\StoreOnboardingService::EDITIONS;
        return view('admin.stores.create', compact('editions'));
    }

    public function store(Request $request, \App\Services\StoreOnboardingService $onboardingService): RedirectResponse
    {
        $validated = $this->validateStore($request);

        $store = $onboardingService->provisionStore($validated);

        if ($store->is_primary) {
            $this->demoteOtherPrimaries($store->id);
        }

        return redirect()
            ->route('admin.stores.index')
            ->with('success', __('messages.store_created'));
    }

    public function edit(Store $store): View
    {
        $store->load('setting');

        return view('admin.stores.edit', compact('store'));
    }

    public function update(Request $request, Store $store): RedirectResponse
    {
        $validated = $this->validateStore($request, $store);

        DB::transaction(function () use ($validated, $store) {
            $store->update([
                'name' => trim($validated['name']),
                'slug' => trim($validated['slug']),
                'viber_number' => $validated['viber_number'] ?? null,
                'telegram_username' => $validated['telegram_username'] ?? null,
                'is_active' => $validated['is_active'] ?? $store->is_active,
                'is_primary' => $validated['is_primary'] ?? false,
            ]);

            // Single-primary rule: flagging this store primary demotes all others.
            if ($store->is_primary) {
                $this->demoteOtherPrimaries($store->id);
            }

            $store->setting()->updateOrCreate(
                ['store_id' => $store->id],
                [
                    'store_name' => trim($validated['name']),
                    'phone' => $validated['phone'] ?? null,
                    'viber_number' => $validated['viber_number'] ?? null,
                    'telegram_username' => $validated['telegram_username'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'opening_hours' => $validated['opening_hours'] ?? null,
                    'delivery_info' => $validated['delivery_info'] ?? null,
                    'payment_info' => $validated['payment_info'] ?? null,
                    'default_language' => $validated['default_language'],
                ]
            );
        });

        return redirect()
            ->route('admin.stores.index')
            ->with('success', __('messages.store_updated'));
    }

    /**
     * Deactivate a store (never hard-delete). Deactivating the primary store
     * promotes the next active store so the root storefront keeps working.
     */
    public function destroy(Store $store): RedirectResponse
    {
        $activeCount = Store::where('is_active', true)->count();

        if ($store->is_active && $activeCount <= 1) {
            return back()->withErrors([
                'store' => __('messages.store_last_active_blocked'),
            ]);
        }

        $wasPrimary = $store->is_primary;

        DB::transaction(function () use ($store, $wasPrimary) {
            $store->update(['is_active' => false, 'is_primary' => false]);

            // A deactivated primary must hand the flag to another active store
            // so fallback resolution keeps returning a store.
            if ($wasPrimary) {
                $next = Store::where('is_active', true)
                    ->where('id', '!=', $store->id)
                    ->orderBy('id')
                    ->first();

                if ($next) {
                    $next->update(['is_primary' => true]);
                }
            }
        });

        return redirect()
            ->route('admin.stores.index')
            ->with('success', $wasPrimary
                ? __('messages.store_deactivated_primary')
                : __('messages.store_deactivated'));
    }

    /**
     * Toggle a store back to active (reactivate). Deactivated stores keep all
     * their history, so reactivation restores the storefront as-is.
     */
    public function activate(Store $store): RedirectResponse
    {
        $store->update(['is_active' => true]);

        return redirect()
            ->route('admin.stores.index')
            ->with('success', __('messages.store_reactivated'));
    }

    /**
     * Permanently delete a store and its related records.
     */
    public function forceDestroy(Store $store): RedirectResponse
    {
        $activeCount = Store::where('is_active', true)->count();
        if ($store->is_active && $activeCount <= 1) {
            return back()->withErrors([
                'store' => __('messages.store_last_active_blocked'),
            ]);
        }

        $wasPrimary = $store->is_primary;
        $storeName = $store->name;

        DB::transaction(function () use ($store, $wasPrimary) {
            app(\App\Services\DemoBusinessScenarioService::class)->cleanStoreData($store);

            if (\Illuminate\Support\Facades\Schema::hasTable('storefront_settings')) {
                DB::table('storefront_settings')->where('store_id', $store->id)->delete();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('store_payment_methods')) {
                DB::table('store_payment_methods')->where('store_id', $store->id)->delete();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('store_delivery_methods')) {
                DB::table('store_delivery_methods')->where('store_id', $store->id)->delete();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('warehouses')) {
                DB::table('warehouses')->where('store_id', $store->id)->delete();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('branches')) {
                DB::table('branches')->where('store_id', $store->id)->delete();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('store_user')) {
                DB::table('store_user')->where('store_id', $store->id)->delete();
            }

            if ($wasPrimary) {
                $next = Store::where('is_active', true)
                    ->where('id', '!=', $store->id)
                    ->orderBy('id')
                    ->first();
                if ($next) {
                    $next->update(['is_primary' => true]);
                }
            }

            $store->delete();
        });

        return redirect()
            ->route('admin.stores.index')
            ->with('success', "Store '{$storeName}' ကို အပြီးတိုင် ဖျက်သိမ်းပြီးပါပြီ။");
    }

    private function demoteOtherPrimaries(int $exceptStoreId): void
    {
        Store::where('is_primary', true)
            ->where('id', '!=', $exceptStoreId)
            ->update(['is_primary' => false]);
    }

    /**
     * Validation mirrors the production:create-store console command so both
     * entry points behave identically.
     */
    private function validateStore(Request $request, ?Store $store = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('stores', 'slug')->ignore($store),
            ],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^(\+?95|09)[0-9]{7,11}$/'],
            'viber_number' => ['nullable', 'string', 'max:50', 'regex:/^(\+?95|09)[0-9]{7,11}$/'],
            'telegram_username' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_]{5,32}$/'],
            'address' => ['nullable', 'string'],
            'opening_hours' => ['nullable', 'string', 'max:255'],
            'delivery_info' => ['nullable', 'string'],
            'payment_info' => ['nullable', 'string'],
            'edition' => ['nullable', 'string', Rule::in(array_keys(\App\Services\StoreOnboardingService::EDITIONS))],
            'owner_name' => ['nullable', 'string', 'max:100'],
            'owner_phone' => ['nullable', 'string', 'max:50', 'regex:/^(\+?95|09)[0-9]{7,11}$/'],
            'owner_password' => ['nullable', 'string', 'min:6'],
            'owner_pos_pin' => ['nullable', 'string', 'min:4', 'max:8'],
            'default_language' => ['required', Rule::in(array_keys(config('localization.supported', [])))],
            'is_active' => ['nullable', 'boolean'],
            'is_primary' => ['nullable', 'boolean'],
        ]);
    }
}
