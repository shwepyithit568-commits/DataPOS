<?php

namespace App\POS\Services;

use App\Models\AuditLog;
use App\Models\Promotion;
use App\Models\PromotionUsage;
use App\Models\Store;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PromotionService
{
    // ---------- Query Helpers ----------

    /**
     * Summary KPI stats for the promotions dashboard.
     *
     * @return array<string, int|float>
     */
    public function getSummaryStats(Store $store): array
    {
        $total = Promotion::where('store_id', $store->id)->count();
        $active = Promotion::where('store_id', $store->id)->valid()->count();
        $expired = Promotion::where('store_id', $store->id)
            ->where(fn ($q) => $q->where('is_active', false)
                ->orWhere('expires_at', '<', now())
                ->orWhere(fn ($qq) => $qq->whereNotNull('total_uses_limit')
                    ->whereColumn('used_count', '>=', 'total_uses_limit')))
            ->count();

        $totalDiscount = PromotionUsage::where('store_id', $store->id)->sum('discount_applied');
        $totalUses = PromotionUsage::where('store_id', $store->id)->count();

        return [
            'total'          => $total,
            'active'         => $active,
            'expired'        => $expired,
            'total_discount' => (float) $totalDiscount,
            'total_uses'     => (int) $totalUses,
        ];
    }

    /**
     * Paginated promotions list with optional filters.
     */
    public function getPromotions(Store $store, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Promotion::where('store_id', $store->id)
            ->with(['category', 'product', 'creator'])
            ->withCount('usages');

        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%"));
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            match ($filters['status']) {
                'active'    => $query->valid(),
                'inactive'  => $query->where('is_active', false),
                'expired'   => $query->where('expires_at', '<', now()),
                'scheduled' => $query->where('starts_at', '>', now()),
                default     => null,
            };
        }

        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'oldest'     => $query->orderBy('created_at', 'asc'),
            'name_asc'   => $query->orderBy('name', 'asc'),
            'uses_desc'  => $query->orderByDesc('used_count'),
            'value_desc' => $query->orderByDesc('value'),
            default      => $query->orderByDesc('created_at'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    // ---------- CRUD ----------

    /**
     * Create or update a promotion.
     */
    public function save(Store $store, array $data, ?Promotion $promotion = null, ?User $actor = null): Promotion
    {
        return DB::transaction(function () use ($store, $data, $promotion, $actor) {
            $attrs = [
                'name'               => trim($data['name']),
                'code'               => !empty($data['code']) ? strtoupper(trim($data['code'])) : null,
                'type'               => $data['type'],
                'value'              => (float) ($data['value'] ?? 0),
                'min_order_amount'   => (float) ($data['min_order_amount'] ?? 0),
                'category_id'        => !empty($data['category_id']) ? (int) $data['category_id'] : null,
                'product_id'         => !empty($data['product_id']) ? (int) $data['product_id'] : null,
                'total_uses_limit'   => !empty($data['total_uses_limit']) ? (int) $data['total_uses_limit'] : null,
                'per_customer_limit' => !empty($data['per_customer_limit']) ? (int) $data['per_customer_limit'] : null,
                'starts_at'          => !empty($data['starts_at']) ? $data['starts_at'] : null,
                'expires_at'         => !empty($data['expires_at']) ? $data['expires_at'] : null,
                'is_active'          => isset($data['is_active']) ? (bool) $data['is_active'] : true,
                'is_public'          => isset($data['is_public']) ? (bool) $data['is_public'] : false,
            ];

            if ($promotion) {
                $promotion->update($attrs);
                $action = 'promotion_updated';
            } else {
                $attrs['store_id'] = $store->id;
                $attrs['created_by'] = $actor?->id;
                $promotion = Promotion::create($attrs);
                $action = 'promotion_created';
            }

            AuditLog::write(
                $store->id,
                $action,
                'promotions',
                $promotion->id,
                ['name' => $promotion->name, 'type' => $promotion->type, 'code' => $promotion->code],
                $actor?->id
            );

            return $promotion->fresh();
        });
    }

    /**
     * Activate / deactivate a promotion.
     */
    public function toggleActive(Promotion $promotion, ?User $actor = null): Promotion
    {
        $promotion->update(['is_active' => !$promotion->is_active]);

        AuditLog::write(
            $promotion->store_id,
            $promotion->is_active ? 'promotion_activated' : 'promotion_deactivated',
            'promotions',
            $promotion->id,
            ['name' => $promotion->name],
            $actor?->id
        );

        return $promotion->fresh();
    }

    /**
     * Soft-delete (deactivate + expire) a promotion.
     */
    public function delete(Promotion $promotion, ?User $actor = null): void
    {
        DB::transaction(function () use ($promotion, $actor) {
            AuditLog::write(
                $promotion->store_id,
                'promotion_deleted',
                'promotions',
                $promotion->id,
                ['name' => $promotion->name, 'code' => $promotion->code],
                $actor?->id
            );

            $promotion->delete();
        });
    }

    // ---------- Coupon Validation (used by POS) ----------

    /**
     * Validate a coupon code against an order total.
     * Returns ['valid' => bool, 'discount' => float, 'message' => string, 'promotion' => Promotion|null]
     *
     * @return array{valid: bool, discount: float, message: string, promotion: Promotion|null}
     */
    public function validateCoupon(Store $store, string $code, float $orderTotal, ?int $customerId = null): array
    {
        // Delegates to the shared rule check so the admin validator and the POS
        // checkout can never drift apart.
        [$promotion, $reason, $params] = $this->checkCoupon($store, $code, (string) $orderTotal, $customerId);

        if ($reason !== null) {
            return [
                'valid' => false,
                'discount' => 0.0,
                'message' => $this->reasonText($reason, $params),
                'promotion' => $promotion,
            ];
        }

        /** @var Promotion $promotion */
        $discount = $this->calculateDiscountDecimal($promotion, (string) $orderTotal);

        return [
            'valid' => true,
            'discount' => (float) $discount,
            'message' => "Coupon applied: {$promotion->name} — " . number_format((float) $discount) . ' Ks off',
            'promotion' => $promotion,
        ];
    }

    /**
     * The human sentence the admin validator returns for a rejection reason.
     *
     * @param  array<string,string>  $params
     */
    private function reasonText(string $reason, array $params = []): string
    {
        return match ($reason) {
            'coupon_inactive' => 'This promotion is inactive.',
            'coupon_not_started' => 'This promotion has not started yet.',
            'coupon_expired' => 'This promotion has expired.',
            'coupon_limit_reached' => 'This promotion\'s usage limit has been reached.',
            'coupon_min_order' => 'Minimum order amount of ' . number_format((float) ($params['amount'] ?? 0)) . ' Ks required.',
            'coupon_customer_limit' => 'You have reached the usage limit for this coupon.',
            'coupon_type_unsupported' => 'This promotion type is not supported at the counter yet.',
            'coupon_not_applicable' => 'This coupon does not apply to anything in this cart.',
            default => 'Coupon code not found.',
        };
    }

    /**
     * Calculate discount amount for an order.
     */
    public function calculateDiscount(Promotion $promotion, float $orderTotal): float
    {
        return (float) $this->calculateDiscountDecimal($promotion, (string) $orderTotal);
    }

    /**
     * Calculate the discount as an exact decimal string.
     *
     * @param  string  $orderTotal  decimal string; the discount may be written
     *                              onto the order, so it must not be a float.
     */
    public function calculateDiscountDecimal(Promotion $promotion, string $orderTotal): string
    {
        $total = bcadd($orderTotal !== '' ? $orderTotal : '0', '0', 2);
        $value = bcadd((string) ($promotion->value ?? '0'), '0', 2);

        return match ($promotion->type) {
            // total x percent / 100
            'percent_off' => bcdiv(bcmul($total, $value, 4), '100', 2),
            'flat_off'    => bccomp($value, $total, 2) > 0 ? $total : $value,
            'bogo'        => '0.00', // BOGO is handled at line-item level in POS
            default       => '0.00',
        };
    }

    // ---------- Redemption (POS counter) ----------
    //
    // Coupons could be created, listed and validated from the admin screen, but
    // nothing ever wrote a redemption: `promotions.used_count` and the
    // `promotion_usages` ledger were read-only, so usage limits never bit, the
    // per-customer limit never applied and the dashboard always showed 0 uses.
    // These two methods are the write side, called from the sale transaction.

    /**
     * Validate a coupon for a POS sale and return the discount as a decimal.
     *
     * Same rules as validateCoupon() but decimal-safe (the discount is written
     * onto a sale) and returning translation keys instead of English sentences,
     * so the POS can show the reason in the cashier's language.
     *
     * @return array{valid:bool, discount:string, reason:?string, params:array<string,string>, promotion:?Promotion}
     */
    public function validateCouponDecimal(
        Store $store,
        string $code,
        string $orderTotal,
        ?int $customerId = null,
        ?string $eligibleSubtotal = null,
    ): array {
        [$promotion, $reason, $params] = $this->checkCoupon($store, $code, $orderTotal, $customerId);

        if (! $promotion || $reason !== null) {
            return ['valid' => false, 'discount' => '0.00', 'reason' => $reason, 'params' => $params, 'promotion' => $promotion];
        }

        // A promotion can be limited to one product or category. Those discounts
        // are priced on the matching part of the bill only — applying a
        // "10% off chargers" coupon to the whole basket would give away money
        // the shop never offered.
        $base = $orderTotal;

        if ($promotion->product_id || $promotion->category_id) {
            if ($eligibleSubtotal === null) {
                // The caller cannot tell us what matched, so we must not guess.
                return ['valid' => false, 'discount' => '0.00', 'reason' => 'coupon_not_applicable', 'params' => [], 'promotion' => $promotion];
            }

            if (bccomp(bcadd($eligibleSubtotal, '0', 2), '0', 2) <= 0) {
                return ['valid' => false, 'discount' => '0.00', 'reason' => 'coupon_not_applicable', 'params' => [], 'promotion' => $promotion];
            }

            $base = $eligibleSubtotal;
        }

        return [
            'valid' => true,
            'discount' => $this->calculateDiscountDecimal($promotion, $base),
            'reason' => null,
            'params' => [],
            'promotion' => $promotion,
        ];
    }

    /**
     * Record that a coupon was used on a sale: usage ledger + used counter.
     *
     * Called inside the sale transaction so a sale and its redemption are one
     * fact — a crash between the two would let the same coupon go over its limit.
     */
    public function redeem(
        Store $store,
        Promotion $promotion,
        string $discountApplied,
        ?int $customerId = null,
        ?int $posSaleId = null,
        ?User $actor = null,
    ): PromotionUsage {
        return DB::transaction(function () use ($store, $promotion, $discountApplied, $customerId, $posSaleId, $actor) {
            $locked = Promotion::whereKey($promotion->id)->lockForUpdate()->firstOrFail();

            $usage = PromotionUsage::create([
                'promotion_id' => $locked->id,
                'store_id' => $store->id,
                'customer_id' => $customerId,
                'pos_sale_id' => $posSaleId,
                'discount_applied' => bcadd($discountApplied, '0', 2),
            ]);

            $locked->update(['used_count' => (int) $locked->used_count + 1]);

            AuditLog::write(
                storeId: $store->id,
                action: 'promotion_redeemed',
                entityType: Promotion::class,
                entityId: $locked->id,
                metadata: [
                    'code' => $locked->code,
                    'discount' => bcadd($discountApplied, '0', 2),
                    'customer_id' => $customerId,
                    'pos_sale_id' => $posSaleId,
                    'used_count' => (int) $locked->used_count,
                ],
                actorId: $actor?->id,
            );

            return $usage;
        });
    }

    /** The promotion a code belongs to, or null. */
    public function findByCode(Store $store, string $code): ?Promotion
    {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return null;
        }

        return Promotion::where('store_id', $store->id)->where('code', $code)->first();
    }

    /**
     * The shared rule check behind both validate methods.
     *
     * @return array{0: ?Promotion, 1: ?string, 2: array<string,string>} [promotion, reasonKey, params]
     */
    private function checkCoupon(Store $store, string $code, string $orderTotal, ?int $customerId): array
    {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return [null, 'coupon_not_found', []];
        }

        $promotion = Promotion::where('store_id', $store->id)->where('code', $code)->first();

        if (! $promotion) {
            return [null, 'coupon_not_found', []];
        }

        if (! $promotion->is_active) {
            return [$promotion, 'coupon_inactive', []];
        }

        if ($promotion->isNotStarted()) {
            return [$promotion, 'coupon_not_started', []];
        }

        if ($promotion->isExpired()) {
            return [$promotion, 'coupon_expired', []];
        }

        if ($promotion->isUsageLimitReached()) {
            return [$promotion, 'coupon_limit_reached', []];
        }

        // BOGO is priced per line item, which the POS checkout does not do yet.
        // Charging a coupon that cannot discount anything would be worse than
        // saying so.
        if ($promotion->type === 'bogo') {
            return [$promotion, 'coupon_type_unsupported', []];
        }

        $minOrder = bcadd((string) ($promotion->min_order_amount ?? '0'), '0', 2);

        if (bccomp(bcadd($orderTotal !== '' ? $orderTotal : '0', '0', 2), $minOrder, 2) < 0) {
            return [$promotion, 'coupon_min_order', ['amount' => $minOrder]];
        }

        if ($customerId && $promotion->per_customer_limit) {
            $used = PromotionUsage::where('promotion_id', $promotion->id)
                ->where('customer_id', $customerId)
                ->count();

            if ($used >= (int) $promotion->per_customer_limit) {
                return [$promotion, 'coupon_customer_limit', []];
            }
        }

        return [$promotion, null, []];
    }
}
