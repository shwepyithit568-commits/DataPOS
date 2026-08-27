<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use App\POS\Services\PromotionService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function __construct(
        protected PromotionService $promotionService
    ) {
    }

    /**
     * Promotions dashboard — lists all promotions with stats.
     */
    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $limit = $request->input('limit') === 'all' ? 1000 : (int) $request->input('limit', 20);
        $stats      = $this->promotionService->getSummaryStats($store);
        $promotions = $this->promotionService->getPromotions($store, $request->all(), $limit);
        $categories = Category::where('store_id', $store->id)->orderBy('name')->get(['id', 'name']);
        $products   = Product::where('store_id', $store->id)->orderBy('name')->take(200)->get(['id', 'name', 'retail_price']);

        return view('admin.promotions.index', compact('store', 'stats', 'promotions', 'categories', 'products'));
    }

    /**
     * Store a new promotion.
     */
    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $validated = $this->validatePromotion($request, $store->id);

        $this->promotionService->save($store, $validated, null, $request->user());

        return redirect()
            ->route('store.admin.promotions.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.promotion_created'));
    }

    /**
     * Update an existing promotion.
     */
    public function update(Request $request, StoreContext $context, string $store_slug, int|string $promotion): RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $promo = Promotion::where('store_id', $store->id)->findOrFail($promotion);
        $validated = $this->validatePromotion($request, $store->id, $promo->id);

        $this->promotionService->save($store, $validated, $promo, $request->user());

        return redirect()
            ->route('store.admin.promotions.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.promotion_updated'));
    }

    /**
     * Activate or deactivate a promotion.
     */
    public function toggle(Request $request, StoreContext $context, string $store_slug, int|string $promotion): RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $promo = Promotion::where('store_id', $store->id)->findOrFail($promotion);
        $this->promotionService->toggleActive($promo, $request->user());

        return back()->with('success', __('messages.promotion_updated'));
    }

    /**
     * Delete a promotion.
     */
    public function destroy(Request $request, StoreContext $context, string $store_slug, int|string $promotion): RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $promo = Promotion::where('store_id', $store->id)->findOrFail($promotion);
        $this->promotionService->delete($promo, $request->user());

        return redirect()
            ->route('store.admin.promotions.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.promotion_deleted'));
    }

    /**
     * AJAX coupon validation endpoint (used by POS).
     */
    public function validateCoupon(Request $request, StoreContext $context): \Illuminate\Http\JsonResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $request->validate([
            'code'        => 'required|string|max:80',
            'order_total' => 'required|numeric|min:0',
            'customer_id' => 'nullable|integer',
        ]);

        $result = $this->promotionService->validateCoupon(
            $store,
            $request->input('code'),
            (float) $request->input('order_total'),
            $request->input('customer_id') ? (int) $request->input('customer_id') : null
        );

        return response()->json($result);
    }

    // ---------- Private helpers ----------

    private function validatePromotion(Request $request, int $storeId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'               => 'required|string|max:200',
            'code'               => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('promotions', 'code')
                    ->where('store_id', $storeId)
                    ->ignore($ignoreId),
            ],
            'type'               => 'required|in:percent_off,flat_off,bogo',
            'value'              => 'required|numeric|min:0',
            'min_order_amount'   => 'nullable|numeric|min:0',
            'category_id'        => 'nullable|integer|exists:categories,id',
            'product_id'         => 'nullable|integer|exists:products,id',
            'total_uses_limit'   => 'nullable|integer|min:1',
            'per_customer_limit' => 'nullable|integer|min:1',
            'starts_at'          => 'nullable|date',
            'expires_at'         => 'nullable|date|after_or_equal:starts_at',
            'is_active'          => 'nullable|boolean',
            'is_public'          => 'nullable|boolean',
        ]);
    }
}
