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
        $promotion = Promotion::where('store_id', $store->id)
            ->where('code', strtoupper(trim($code)))
            ->first();

        if (!$promotion) {
            return ['valid' => false, 'discount' => 0.0, 'message' => 'Coupon code not found.', 'promotion' => null];
        }

        if (!$promotion->is_active) {
            return ['valid' => false, 'discount' => 0.0, 'message' => 'This promotion is inactive.', 'promotion' => $promotion];
        }

        if ($promotion->isNotStarted()) {
            return ['valid' => false, 'discount' => 0.0, 'message' => 'This promotion has not started yet.', 'promotion' => $promotion];
        }

        if ($promotion->isExpired()) {
            return ['valid' => false, 'discount' => 0.0, 'message' => 'This promotion has expired.', 'promotion' => $promotion];
        }

        if ($promotion->isUsageLimitReached()) {
            return ['valid' => false, 'discount' => 0.0, 'message' => 'This promotion\'s usage limit has been reached.', 'promotion' => $promotion];
        }

        if ($orderTotal < $promotion->min_order_amount) {
            return [
                'valid' => false,
                'discount' => 0.0,
                'message' => 'Minimum order amount of ' . number_format($promotion->min_order_amount) . ' Ks required.',
                'promotion' => $promotion,
            ];
        }

        if ($customerId && $promotion->per_customer_limit) {
            $customerUsed = PromotionUsage::where('promotion_id', $promotion->id)
                ->where('customer_id', $customerId)
                ->count();

            if ($customerUsed >= $promotion->per_customer_limit) {
                return ['valid' => false, 'discount' => 0.0, 'message' => 'You have reached the usage limit for this coupon.', 'promotion' => $promotion];
            }
        }

        $discount = $this->calculateDiscount($promotion, $orderTotal);

        return [
            'valid' => true,
            'discount' => $discount,
            'message' => "Coupon applied: {$promotion->name} — " . number_format($discount) . ' Ks off',
            'promotion' => $promotion,
        ];
    }

    /**
     * Calculate discount amount for an order.
     */
    public function calculateDiscount(Promotion $promotion, float $orderTotal): float
    {
        return match ($promotion->type) {
            'percent_off' => round($orderTotal * ($promotion->value / 100), 2),
            'flat_off'    => min($promotion->value, $orderTotal),
            'bogo'        => 0.0, // BOGO is handled at line-item level in POS
            default       => 0.0,
        };
    }
}
