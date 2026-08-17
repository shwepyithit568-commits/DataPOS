<?php

namespace App\POS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\PosSale;
use App\POS\Services\CashierShiftService;
use App\POS\Services\CustomerDebtService;
use App\POS\Services\PosSaleService;
use App\Services\StoreContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * POS cart + sale posting (target-design §2.8).
 *
 * Statically registered under /store/{store_slug}/pos with
 * ResolveStoreContext + EnsureStoreAccess — every sale/cart operation is
 * re-validated against the resolved store server-side (never trust the client).
 */
class PosSaleController extends Controller
{
    public function __construct(
        protected PosSaleService $sales,
        protected CashierShiftService $shifts,
        protected CustomerDebtService $debts,
    ) {
    }

    /* ------------------------------------------------------------------ */
    /*  Product search (barcode / SKU / name)                              */
    /* ------------------------------------------------------------------ */

    public function search(Request $request, StoreContext $context): JsonResponse
    {
        $store = $context->getStore();

        $data = $request->validate([
            'q' => ['required', 'string', 'max:120'],
        ]);

        return response()->json([
            'results' => $this->sales->searchProducts($store, $data['q'], $this->sales->cartCustomer($store)),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Product grid + live cart state (AJAX — reference alinthit_pos UI)  */
    /* ------------------------------------------------------------------ */

    /**
     * Full product grid for the POS home (browse + category/brand filters),
     * with live ledger balances and variants — used by the left product panel.
     */
    public function grid(Request $request, StoreContext $context): JsonResponse
    {
        $store = $context->getStore();

        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer'],
            'brand_id' => ['nullable', 'integer'],
        ]);

        return response()->json([
            'products' => $this->sales->gridProducts(
                $store,
                isset($data['category_id']) ? (int) $data['category_id'] : null,
                isset($data['brand_id']) ? (int) $data['brand_id'] : null,
                $data['q'] ?? '',
                $this->sales->cartCustomer($store),
            ),
            'categories' => Category::query()->where('store_id', $store->id)->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::query()->where('store_id', $store->id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Live cart snapshot (lines + totals + shift state) for the cart panel.
     */
    public function cartState(Request $request, StoreContext $context): JsonResponse
    {
        $store = $context->getStore();

        // Wrapped in 'cart' — identical shape to every AJAX mutation response,
        // so the client applies the snapshot uniformly (and can read
        // expired_count for the auto-expiry notice).
        return response()->json(['cart' => $this->sales->cartState($store, $request->user())]);
    }

    /* ------------------------------------------------------------------ */
    /*  Customer search (attach a customer to a sale)                      */
    /* ------------------------------------------------------------------ */

    /**
     * JSON search for retail/wholesale customers of this store — used to
     * attach a customer before posting a credit (debt) sale.
     */
    public function customers(Request $request, StoreContext $context): JsonResponse
    {
        $store = $context->getStore();

        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $q = trim($data['q'] ?? '');

        $query = User::query()
            ->whereHas('stores', fn ($q2) => $q2
                ->where('stores.id', $store->id)
                ->whereIn('store_user.role', ['retail_customer', 'wholesale_customer'])
                ->where('store_user.status', 'active'));

        if ($q !== '') {
            $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%"));
        }

        $customers = $query->orderBy('name')->limit(12)->get()->map(function (User $user) use ($store) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'role' => $user->getStoreRole($store->id),
                'balance' => $this->debts->balanceFor($store->id, $user->id),
            ];
        });

        return response()->json(['customers' => $customers]);
    }

    /**
     * Attach a store customer to the cart (server-side) so the whole POS
     * prices at their tier — wholesale members see wholesale prices in the
     * grid, cart and posted sale; walk-in (detach) resets to retail.
     */
    public function attachCustomer(Request $request, string $store_slug, StoreContext $context, User $customer): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();

        try {
            $this->sales->attachCartCustomer($store, $customer);
        } catch (InventoryException $e) {
            return $this->jsonOrRedirect($request, $store, null, $e->getMessage());
        }

        return $this->jsonOrRedirect($request, $store, __('messages.pos_customer_attached') . ' — ' . $customer->name);
    }

    /**
     * Detach — back to walk-in (retail) pricing for the cart.
     */
    public function detachCustomer(Request $request, StoreContext $context): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();
        $this->sales->attachCartCustomer($store, null);

        return $this->jsonOrRedirect($request, $store, __('messages.pos_customer_detached'));
    }

    /**
     * Quick-add a customer from the POS (the "+ ဖောက်သည်" button or the
     * "not found — add new" fallback). Identity is the normalized phone:
     *
     *   - No user with that phone  → create a shopper account (no login yet)
     *   - User exists, not yet a member of this store → attach as
     *     retail_customer (same person record, per-store membership — this is
     *     how the same person shops at several stores without duplication)
     *   - Already a customer of this store → idempotent, refresh the name
     *   - Staff / manager / owner phone → rejected (never claimable)
     *
     * The membership is created immediately, so the customer is shared with
     * this store's ecommerce list and can log in later via register/forgot.
     */
    public function addCustomer(Request $request, StoreContext $context): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'type' => ['nullable', 'string', 'in:retail_customer,wholesale_customer'],
        ]);

        $name = trim($data['name']);
        $role = $data['type'] ?? 'retail_customer';
        $phone = User::normalizePhone($data['phone']);

        if ($phone === '' || strlen($phone) < 7 || strlen($phone) > 15 || !preg_match('/^\d+$/', $phone)) {
            return $this->jsonOrRedirect($request, $store, null, __('messages.pos_customer_invalid_phone'));
        }

        $user = User::findByNormalizedPhone($data['phone']);

        if ($user !== null) {
            $isStaffAccount = $user->isPlatformOwner()
                || $user->stores()->wherePivotIn('role', ['store_manager', 'staff'])->exists();

            if ($isStaffAccount) {
                return $this->jsonOrRedirect($request, $store, null, __('messages.pos_customer_staff_phone'));
            }

            $hasMembership = $user->stores()->wherePivot('store_id', $store->id)->exists();

            if ($hasMembership) {
                // Idempotent — already a customer of this store; refresh name.
                $user->update(['name' => $name]);
            } else {
                // Same person, new store — attach a membership here only.
                $user->stores()->attach($store->id, [
                    'role' => $role,
                    'status' => 'active',
                ]);
            }
        } else {
            // Debt shoppers don't need login credentials; a random password
            // keeps the account locked until they register / reset it online.
            $user = User::create([
                'name' => $name,
                'phone' => $data['phone'],
                'password' => Hash::make(Str::random(24)),
                'role' => 'customer',
            ]);

            $user->stores()->attach($store->id, [
                'role' => $role,
                'status' => 'active',
            ]);
        }

        // Select the new customer in the cart right away — the cashier added
        // them because they are about to sell to them (credit sale or tiered
        // pricing), so re-selecting is a wasted step.
        $this->sales->attachCartCustomer($store, $user);

        $customer = [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'role' => $role,
            'balance' => $this->debts->balanceFor($store->id, $user->id),
        ];

        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['success' => __('messages.pos_customer_added'), 'customer' => $customer, 'cart' => $this->sales->cartState($store, $request->user())]);
        }

        return back()->with('success', __('messages.pos_customer_added') . ' — ' . $user->name);
    }

    /* ------------------------------------------------------------------ */
    /*  Debt collection                                                    */
    /* ------------------------------------------------------------------ */

    /**
     * Record a payment toward a customer's outstanding debt (SoT §17 — a new
     * ledger transaction, never a direct balance edit).
     */
    public function collect(Request $request, string $store_slug, User $customer, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        // The customer must belong to this store — never collect on a
        // cross-store receivable (mirrors the sale-posting guard).
        $isCustomer = $customer->stores()
            ->where('stores.id', $store->id)
            ->whereIn('store_user.role', ['retail_customer', 'wholesale_customer'])
            ->where('store_user.status', 'active')
            ->exists();
        if (! $isCustomer) {
            abort(403);
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $entry = $this->debts->collect(
                store: $store,
                customerId: $customer->id,
                amount: (string) $data['amount'],
                actor: $request->user(),
                notes: $data['notes'] ?? null,
                clientTransactionId: 'pos_collect:' . $store->id . ':' . $customer->id . ':' . now()->format('YmdHis') . ':' . random_int(1000, 9999),
            );
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        AuditLog::write(
            storeId: $store->id,
            action: 'customer_debt_collected',
            entityType: 'customer_ledger_entry',
            entityId: $entry->id,
            metadata: ['customer_id' => $customer->id, 'amount' => (string) $entry->amount],
            actorId: $request->user()?->id,
            ipAddress: $request->ip(),
        );

        return back()->with('success', __('messages.debt_collected') . ' — ' . $customer->name);
    }

    /* ------------------------------------------------------------------ */
    /*  Cart operations                                                    */
    /* ------------------------------------------------------------------ */

    public function addItem(Request $request, StoreContext $context): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();

        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'product_variant_id' => ['nullable', 'integer'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
        ]);

        try {
            $this->sales->addToCart($store, (int) $data['product_id'], isset($data['product_variant_id']) ? (int) $data['product_variant_id'] : null, (string) $data['quantity']);
        } catch (InventoryException $e) {
            return $this->jsonOrRedirect($request, $store, null, $e->getMessage());
        }

        return $this->jsonOrRedirect($request, $store, __('messages.pos_item_added'));
    }

    public function updateLine(Request $request, string $store_slug, StoreContext $context, int $line): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();

        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.001'],
        ]);

        try {
            $this->sales->updateCartLine($store, $line, (string) $data['quantity']);
        } catch (InventoryException $e) {
            return $this->jsonOrRedirect($request, $store, null, $e->getMessage());
        }

        return $this->jsonOrRedirect($request, $store);
    }

    public function removeLine(Request $request, string $store_slug, StoreContext $context, int $line): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();

        try {
            $this->sales->removeCartLine($store, $line);
        } catch (InventoryException $e) {
            return $this->jsonOrRedirect($request, $store, null, $e->getMessage());
        }

        return $this->jsonOrRedirect($request, $store);
    }

    /**
     * Drop the whole session cart (F4 clear-cart shortcut).
     */
    public function clearCart(Request $request, StoreContext $context): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();
        $this->sales->clearCart($store);

        return $this->jsonOrRedirect($request, $store, __('messages.pos_cart_cleared'));
    }

    /**
     * JSON cart mutation responses for the AJAX POS UI; plain form posts
     * (non-XHR) keep the classic redirect-with-flash behaviour.
     */
    private function jsonOrRedirect(Request $request, \App\Models\Store $store, ?string $success = null, ?string $error = null): JsonResponse|RedirectResponse
    {
        $wantsJson = $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest';

        if ($wantsJson) {
            if ($error !== null) {
                return response()->json(['error' => $error], 422);
            }

            return response()->json([
                'success' => $success ?? true,
                'cart' => $this->sales->cartState($store, $request->user()),
            ]);
        }

        if ($error !== null) {
            return back()->with('error', $error);
        }

        return $success !== null ? back()->with('success', $success) : back();
    }

    /* ------------------------------------------------------------------ */
    /*  Hold / resume / void                                               */
    /* ------------------------------------------------------------------ */

    public function hold(Request $request, StoreContext $context): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();
        $user = $request->user();
        $shift = $this->shifts->openShiftFor($store, $user);

        try {
            $sale = $this->sales->holdCart($store, $user, $shift);
        } catch (InventoryException $e) {
            return $this->jsonOrRedirect($request, $store, null, $e->getMessage());
        }

        return $this->jsonOrRedirect($request, $store, __('messages.sale_held') . " #{$sale->id}");
    }

    public function resume(Request $request, string $store_slug, StoreContext $context, PosSale $sale): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();

        try {
            $this->sales->resumeHeld($store, $sale, $request->user());
        } catch (InventoryException $e) {
            return $this->jsonOrRedirect($request, $store, null, $e->getMessage());
        }

        return $this->jsonOrRedirect($request, $store, __('messages.sale_resumed'));
    }

    public function void(Request $request, string $store_slug, StoreContext $context, PosSale $sale): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();

        try {
            $this->sales->voidHeld($store, $sale, $request->user());
        } catch (InventoryException $e) {
            return $this->jsonOrRedirect($request, $store, null, $e->getMessage());
        }

        return $this->jsonOrRedirect($request, $store, __('messages.sale_voided'));
    }

    /* ------------------------------------------------------------------ */
    /*  Post (atomic)                                                      */
    /* ------------------------------------------------------------------ */

    public function post(Request $request, string $store_slug, StoreContext $context, ?PosSale $sale = null): RedirectResponse
    {
        $store = $context->getStore();
        $user = $request->user();

        $data = $request->validate([
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', 'string', 'in:cash,kpay,wavepay,cb_pay,mmqr,credit'],
            'payments.*.amount' => ['nullable', 'numeric', 'min:0'], // empty = unused method, dropped in the service
            'customer_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $shift = $this->shifts->openShiftFor($store, $user);

        try {
            if ($sale && $sale->exists) {
                $lines = $sale->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity' => (string) $item->quantity,
                ])->all();
            } else {
                $lines = $this->sales->cartLines($store);
                $sale = null;
            }

            $posted = $this->sales->post(
                store: $store,
                lines: $lines,
                payments: $data['payments'],
                actor: $user,
                shift: $shift,
                heldSale: $sale,
                customerId: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            );
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        $change = $posted->payments->firstWhere('method', 'cash')?->change_given ?? '0';
        $debt = $posted->payments->firstWhere('method', 'credit')?->amount ?? '0';

        $response = back()->with('success', __('messages.sale_posted') . " {$posted->receipt_number}")
            ->with('posted_receipt', $posted->receipt_number)
            ->with('posted_sale_id', $posted->id)
            ->with('posted_change', (string) $change);

        if (bccomp((string) $debt, '0', 2) > 0) {
            $response->with('posted_debt', (string) $debt);
        }

        return $response;
    }

    /* ------------------------------------------------------------------ */
    /*  Receipt (print / reprint)                                          */
    /* ------------------------------------------------------------------ */

    /**
     * Printable receipt for a posted sale. Every view is audited: the first is
     * pos_receipt_printed, every later one pos_receipt_reprinted (SoT §8:
     * "Reprints must be marked/audited").
     */
    public function receipt(Request $request, string $store_slug, PosSale $sale, StoreContext $context): View
    {
        $store = $context->getStore();

        if ((int) $sale->store_id !== (int) $store->id || ! $sale->isPosted()) {
            abort(404);
        }

        $sale->load(['items', 'payments', 'cashier']);

        $printed = AuditLog::countFor('pos_receipt_printed', 'pos_sale', $sale->id);
        $reprinted = AuditLog::countFor('pos_receipt_reprinted', 'pos_sale', $sale->id);
        $isReprint = ($printed + $reprinted) > 0;

        AuditLog::write(
            storeId: $store->id,
            action: $isReprint ? 'pos_receipt_reprinted' : 'pos_receipt_printed',
            entityType: 'pos_sale',
            entityId: $sale->id,
            metadata: ['receipt_number' => $sale->receipt_number, 'total' => (string) $sale->total],
            actorId: $request->user()?->id,
            ipAddress: $request->ip(),
        );

        $printCount = $printed + $reprinted + 1;

        return view('pos.receipt', compact('store', 'sale', 'printCount', 'isReprint'));
    }
}
