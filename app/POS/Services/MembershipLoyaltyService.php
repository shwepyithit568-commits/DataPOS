<?php

namespace App\POS\Services;

use App\Models\AuditLog;
use App\Models\LoyaltyPointTransaction;
use App\Models\MembershipTier;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\PosReturn;
use App\POS\Models\PosSale;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MembershipLoyaltyService
{
    /**
     * Auto-seed default membership tiers for a store if none exist.
     */
    public function ensureDefaultTiers(Store $store): void
    {
        $count = MembershipTier::where('store_id', $store->id)->count();
        if ($count > 0) {
            return;
        }

        DB::transaction(function () use ($store) {
            $defaults = [
                [
                    'name' => 'Standard Member',
                    'code' => 'STANDARD',
                    'min_spending' => 0.00,
                    'discount_percent' => 0.00,
                    'point_multiplier' => 1.00,
                    'badge_color' => 'slate',
                    'is_default' => true,
                    'is_active' => true,
                ],
                [
                    'name' => 'Silver VIP',
                    'code' => 'SILVER',
                    'min_spending' => 200000.00,
                    'discount_percent' => 3.00,
                    'point_multiplier' => 1.20,
                    'badge_color' => 'blue',
                    'is_default' => false,
                    'is_active' => true,
                ],
                [
                    'name' => 'Gold VIP',
                    'code' => 'GOLD',
                    'min_spending' => 1000000.00,
                    'discount_percent' => 5.00,
                    'point_multiplier' => 1.50,
                    'badge_color' => 'amber',
                    'is_default' => false,
                    'is_active' => true,
                ],
                [
                    'name' => 'Platinum VIP',
                    'code' => 'PLATINUM',
                    'min_spending' => 3000000.00,
                    'discount_percent' => 10.00,
                    'point_multiplier' => 2.00,
                    'badge_color' => 'purple',
                    'is_default' => false,
                    'is_active' => true,
                ],
            ];

            foreach ($defaults as $tierData) {
                MembershipTier::create(array_merge($tierData, ['store_id' => $store->id]));
            }
        });
    }

    /**
     * Get all membership tiers for the store with member count.
     *
     * @return Collection<int, MembershipTier>
     */
    public function getTiers(Store $store): Collection
    {
        $this->ensureDefaultTiers($store);

        $tiers = MembershipTier::where('store_id', $store->id)
            ->orderBy('min_spending')
            ->get();

        // Calculate member count per tier
        $memberCounts = DB::table('store_user')
            ->where('store_id', $store->id)
            ->whereIn('role', ['retail_customer', 'wholesale_customer'])
            ->whereNotNull('membership_tier_id')
            ->select('membership_tier_id', DB::raw('count(*) as count'))
            ->groupBy('membership_tier_id')
            ->pluck('count', 'membership_tier_id');

        foreach ($tiers as $t) {
            $t->members_count = $memberCounts[$t->id] ?? 0;
        }

        return $tiers;
    }

    /**
     * Get summary KPI stats.
     *
     * @return array<string, mixed>
     */
    public function getSummaryStats(Store $store): array
    {
        $this->ensureDefaultTiers($store);

        $totalMembers = DB::table('store_user')
            ->where('store_id', $store->id)
            ->whereIn('role', ['retail_customer', 'wholesale_customer'])
            ->count();

        $pointsInCirculation = (int) DB::table('store_user')
            ->where('store_id', $store->id)
            ->whereIn('role', ['retail_customer', 'wholesale_customer'])
            ->sum('loyalty_points');

        $activeTiersCount = MembershipTier::where('store_id', $store->id)->where('is_active', true)->count();

        $totalPointsRedeemed = (int) abs(LoyaltyPointTransaction::where('store_id', $store->id)
            ->where('type', 'redeemed')
            ->sum('points'));

        return [
            'total_members' => $totalMembers,
            'active_tiers' => $activeTiersCount,
            'points_in_circulation' => $pointsInCirculation,
            'total_points_redeemed' => $totalPointsRedeemed,
        ];
    }

    /**
     * Get paginated customer members list with tier information.
     */
    public function getMembers(Store $store, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $this->ensureDefaultTiers($store);

        $defaultTier = MembershipTier::where('store_id', $store->id)->where('is_default', true)->first();

        $query = User::query()
            ->join('store_user', 'users.id', '=', 'store_user.user_id')
            ->leftJoin('membership_tiers', 'store_user.membership_tier_id', '=', 'membership_tiers.id')
            ->where('store_user.store_id', $store->id)
            ->whereIn('store_user.role', ['retail_customer', 'wholesale_customer'])
            ->select(
                'users.*',
                'store_user.role as customer_role',
                'store_user.membership_tier_id',
                'store_user.loyalty_points',
                'store_user.total_spent',
                'membership_tiers.name as tier_name',
                'membership_tiers.code as tier_code',
                'membership_tiers.badge_color as tier_color',
                'membership_tiers.discount_percent as tier_discount',
                'membership_tiers.point_multiplier as tier_multiplier'
            );

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                  ->orWhere('users.phone', 'like', "%{$search}%")
                  ->orWhere('users.email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['tier_id'])) {
            $query->where('store_user.membership_tier_id', $filters['tier_id']);
        }

        $sort = $filters['sort'] ?? 'points_desc';
        match ($sort) {
            'spent_desc'  => $query->orderByDesc('store_user.total_spent'),
            'points_desc' => $query->orderByDesc('store_user.loyalty_points'),
            'name_asc'    => $query->orderBy('users.name'),
            default       => $query->orderByDesc('store_user.loyalty_points'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Save (create or update) a membership tier.
     */
    public function saveTier(
        Store $store,
        array $data,
        ?MembershipTier $tier = null,
        ?User $user = null
    ): MembershipTier {
        return DB::transaction(function () use ($store, $data, $tier, $user) {
            $isDefault = !empty($data['is_default']);

            if ($isDefault) {
                MembershipTier::where('store_id', $store->id)->update(['is_default' => false]);
            }

            $attributes = [
                'name' => trim($data['name']),
                'code' => strtoupper(trim($data['code'])),
                'min_spending' => (float) ($data['min_spending'] ?? 0),
                'discount_percent' => (float) ($data['discount_percent'] ?? 0),
                'point_multiplier' => (float) ($data['point_multiplier'] ?? 1.0),
                'badge_color' => $data['badge_color'] ?? 'slate',
                'is_default' => $isDefault,
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            ];

            if ($tier) {
                $tier->update($attributes);
                $action = 'membership_tier_updated';
            } else {
                $tier = MembershipTier::create(array_merge($attributes, ['store_id' => $store->id]));
                $action = 'membership_tier_created';
            }

            AuditLog::write(
                $store->id,
                $action,
                'membership_tiers',
                $tier->id,
                ['name' => $tier->name, 'code' => $tier->code],
                $user?->id
            );

            return $tier;
        });
    }

    /**
     * Delete a membership tier (reassigns members to default).
     */
    public function deleteTier(Store $store, MembershipTier $tier, ?User $user = null): bool
    {
        if ($tier->is_default) {
            throw new \RuntimeException('Cannot delete the default membership tier.');
        }

        return DB::transaction(function () use ($store, $tier, $user) {
            $defaultTier = MembershipTier::where('store_id', $store->id)
                ->where('is_default', true)
                ->first();

            // Reassign existing members
            DB::table('store_user')
                ->where('store_id', $store->id)
                ->where('membership_tier_id', $tier->id)
                ->update(['membership_tier_id' => $defaultTier?->id]);

            $id = $tier->id;
            $name = $tier->name;
            $tier->delete();

            AuditLog::write(
                $store->id,
                'membership_tier_deleted',
                'membership_tiers',
                $id,
                ['name' => $name],
                $user?->id
            );

            return true;
        });
    }

    /**
     * Adjust loyalty points manually for a customer.
     */
    public function adjustPoints(
        Store $store,
        int $customerId,
        int $points,
        string $type,
        ?string $notes = null,
        ?User $user = null
    ): int {
        return DB::transaction(function () use ($store, $customerId, $points, $type, $notes, $user) {
            $pivot = DB::table('store_user')
                ->where('store_id', $store->id)
                ->where('user_id', $customerId)
                ->first();

            if (!$pivot) {
                throw new \RuntimeException('Customer not found in store.');
            }

            $currentPoints = (int) ($pivot->loyalty_points ?? 0);
            $newPoints = max(0, $currentPoints + $points);

            DB::table('store_user')
                ->where('store_id', $store->id)
                ->where('user_id', $customerId)
                ->update(['loyalty_points' => $newPoints]);

            LoyaltyPointTransaction::create([
                'store_id' => $store->id,
                'customer_id' => $customerId,
                'type' => $type,
                'points' => $points,
                'balance_after' => $newPoints,
                'notes' => $notes,
                'created_by' => $user?->id,
            ]);

            AuditLog::write(
                $store->id,
                'loyalty_points_adjusted',
                'users',
                $customerId,
                [
                    'points_change' => $points,
                    'balance_after' => $newPoints,
                    'type' => $type,
                    'notes' => $notes,
                ],
                $user?->id
            );

            return $newPoints;
        });
    }

    /**
     * Manually assign membership tier to a customer.
     */
    public function assignTier(Store $store, int $customerId, int $tierId, ?User $user = null): void
    {
        DB::transaction(function () use ($store, $customerId, $tierId, $user) {
            $tier = MembershipTier::where('store_id', $store->id)->findOrFail($tierId);

            DB::table('store_user')
                ->where('store_id', $store->id)
                ->where('user_id', $customerId)
                ->update(['membership_tier_id' => $tier->id]);

            AuditLog::write(
                $store->id,
                'customer_tier_assigned',
                'users',
                $customerId,
                ['tier_id' => $tier->id, 'tier_name' => $tier->name],
                $user?->id
            );
        });
    }

    // ── Earning from sales ────────────────────────────────────────────────────
    //
    // Points used to be adjustable only by hand: nothing credited them when a
    // customer bought something, so `store_user.total_spent` stayed 0 and the
    // spend-based tiers (Silver/Gold/Platinum) could never be reached. These
    // methods are the earning side, called from the sale and return transactions.

    public const TYPE_EARNED = 'bonus';
    public const TYPE_ADJUSTED = 'adjusted';

    /**
     * Money the customer must spend to earn one point (MMK per point).
     *
     * Stored per store in the POS settings (`loyalty_amount_per_point`). 0 means
     * the programme is not configured and nothing is earned — the store opts in
     * by setting a value (e.g. 1000 = 1 point per 1,000 Ks spent).
     */
    public function earningRate(Store $store): string
    {
        $rate = (string) ($store->setting?->getPosSetting('loyalty_amount_per_point', 0) ?? '0');

        return bcadd($rate !== '' ? $rate : '0', '0', 2);
    }

    /**
     * Whether sales earn points for this store at all.
     */
    public function isEarningEnabled(Store $store): bool
    {
        if (! $store->hasCapability(\App\Capabilities\Capability::COMMERCE_LOYALTY)) {
            return false;
        }

        return bccomp($this->earningRate($store), '0', 2) > 0;
    }

    /**
     * Points for an amount, using the customer's tier multiplier.
     *
     * floor(amount / rate × multiplier) — the floor keeps the shop from handing
     * out a point for money it never received, and bcmath keeps the division
     * away from floats.
     */
    public function pointsForAmount(Store $store, ?User $customer, string $amount): int
    {
        $rate = $this->earningRate($store);

        if (bccomp($rate, '0', 2) <= 0 || bccomp($amount, '0', 2) <= 0) {
            return 0;
        }

        $multiplier = $this->tierMultiplierFor($store, $customer);
        $raw = bcmul(bcdiv($amount, $rate, 6), $multiplier, 6);

        return (int) floor((float) $raw);
    }

    /**
     * Credit the points a posted sale earned, once per sale.
     *
     * Called inside the sale transaction so the points and the sale land
     * together. Returns null when the programme is off, the sale has no
     * customer, or this sale was already credited.
     */
    public function accrueForSale(PosSale $sale, ?User $actor = null): ?LoyaltyPointTransaction
    {
        $store = $sale->store;
        $customerId = $sale->customer_id;

        if (! $store || ! $customerId || bccomp((string) $sale->total, '0', 2) <= 0) {
            return null;
        }

        if (! $this->isEarningEnabled($store)) {
            return null;
        }

        if (LoyaltyPointTransaction::where('pos_sale_id', $sale->id)->where('type', self::TYPE_EARNED)->exists()) {
            return null;
        }

        $customer = User::find($customerId);
        $points = $this->pointsForAmount($store, $customer, (string) $sale->total);

        $this->shiftSpending($store, $customerId, (string) $sale->total);

        $transaction = null;

        if ($points > 0) {
            $transaction = $this->applyPoints(
                store: $store,
                customerId: $customerId,
                points: $points,
                type: self::TYPE_EARNED,
                notes: __('messages.loyalty_note_sale_earned', ['receipt' => $sale->receipt_number]),
                actor: $actor,
                saleId: $sale->id,
            );
        }

        // A sale can push the customer over a tier threshold.
        $this->syncTierForSpending($store, $customerId, $actor);

        return $transaction;
    }

    /**
     * Take back the points a refund undid, proportionally.
     *
     * Proportional rather than "recall everything": a partial refund should cost
     * the customer only the points that part of the money earned, never more
     * than the sale credited. Balances never go below zero.
     */
    public function reverseForReturn(PosReturn $return, ?User $actor = null): ?LoyaltyPointTransaction
    {
        $store = $return->store;
        $sale = $return->sale;
        $customerId = $return->customer_id;

        if (! $store || ! $sale || ! $customerId || bccomp((string) $return->total, '0', 2) <= 0) {
            return null;
        }

        $earned = LoyaltyPointTransaction::where('pos_sale_id', $sale->id)
            ->where('type', self::TYPE_EARNED)
            ->first();

        $saleTotal = bcadd((string) $sale->total, '0', 2);
        $refundTotal = bcadd((string) $return->total, '0', 2);

        $reversal = 0;

        if ($earned && bccomp($saleTotal, '0', 2) > 0) {
            // floor(earned × refunded / sale total), capped at what was earned.
            $reversal = (int) floor((float) bcmul(
                (string) $earned->points,
                bcdiv($refundTotal, $saleTotal, 6),
                6
            ));
            $reversal = min($reversal, (int) $earned->points);
        }

        $this->shiftSpending($store, $customerId, '-' . $refundTotal);

        $transaction = null;

        if ($reversal > 0) {
            $transaction = $this->applyPoints(
                store: $store,
                customerId: $customerId,
                points: -$reversal,
                type: self::TYPE_ADJUSTED,
                notes: __('messages.loyalty_note_return_reversed', ['refund' => $return->refund_number]),
                actor: $actor,
                saleId: $sale->id,
            );
        }

        $this->syncTierForSpending($store, $customerId, $actor);

        return $transaction;
    }

    /**
     * Move the customer up to the best tier their spending now qualifies for.
     *
     * Upgrades only: a refund that lowers spending does not silently demote a
     * customer, and a tier an operator set by hand is never overwritten unless
     * the spending has grown past it.
     */
    public function syncTierForSpending(Store $store, int $customerId, ?User $actor = null): ?MembershipTier
    {
        $pivot = DB::table('store_user')
            ->where('store_id', $store->id)
            ->where('user_id', $customerId)
            ->first();

        if (! $pivot) {
            return null;
        }

        $spent = bcadd((string) ($pivot->total_spent ?? '0'), '0', 2);

        $qualified = MembershipTier::where('store_id', $store->id)
            ->where('is_active', true)
            ->where('min_spending', '<=', (float) $spent)
            ->orderByDesc('min_spending')
            ->first();

        if (! $qualified) {
            return null;
        }

        $current = $pivot->membership_tier_id
            ? MembershipTier::where('store_id', $store->id)->find($pivot->membership_tier_id)
            : null;

        if ($current && (float) $current->min_spending >= (float) $qualified->min_spending) {
            return null; // already at or above this tier
        }

        $this->assignTier($store, $customerId, $qualified->id, $actor);

        return $qualified;
    }

    /** Customer's tier multiplier (1.00 when they hold no tier). */
    private function tierMultiplierFor(Store $store, ?User $customer): string
    {
        if (! $customer) {
            return '1.00';
        }

        $tierId = DB::table('store_user')
            ->where('store_id', $store->id)
            ->where('user_id', $customer->id)
            ->value('membership_tier_id');

        if (! $tierId) {
            return '1.00';
        }

        $multiplier = MembershipTier::where('store_id', $store->id)->whereKey($tierId)->value('point_multiplier');

        return $multiplier !== null ? bcadd((string) $multiplier, '0', 2) : '1.00';
    }

    /**
     * Add (or subtract) spending for the spend-based tiers, never below zero.
     * Decimal string on purpose: this is money, and it feeds the tier rules.
     */
    private function shiftSpending(Store $store, int $customerId, string $delta): void
    {
        $pivot = DB::table('store_user')
            ->where('store_id', $store->id)
            ->where('user_id', $customerId)
            ->first();

        if (! $pivot) {
            return;
        }

        $next = bcadd((string) ($pivot->total_spent ?? '0'), $delta, 2);

        if (bccomp($next, '0', 2) < 0) {
            $next = '0.00';
        }

        DB::table('store_user')
            ->where('store_id', $store->id)
            ->where('user_id', $customerId)
            ->update(['total_spent' => $next]);
    }

    /**
     * Apply a points delta and write the ledger row (balance_after included).
     *
     * @param  int  $points  positive to credit, negative to take back
     */
    private function applyPoints(
        Store $store,
        int $customerId,
        int $points,
        string $type,
        ?string $notes,
        ?User $actor,
        ?int $saleId = null,
    ): LoyaltyPointTransaction {
        $pivot = DB::table('store_user')
            ->where('store_id', $store->id)
            ->where('user_id', $customerId)
            ->first();

        if (! $pivot) {
            throw new \RuntimeException('Customer not found in store.');
        }

        $balance = max(0, (int) ($pivot->loyalty_points ?? 0) + $points);

        DB::table('store_user')
            ->where('store_id', $store->id)
            ->where('user_id', $customerId)
            ->update(['loyalty_points' => $balance]);

        $transaction = LoyaltyPointTransaction::create([
            'store_id' => $store->id,
            'customer_id' => $customerId,
            'type' => $type,
            'points' => $points,
            'balance_after' => $balance,
            'pos_sale_id' => $saleId,
            'notes' => $notes,
            'created_by' => $actor?->id,
        ]);

        AuditLog::write(
            $store->id,
            $type === self::TYPE_EARNED ? 'loyalty_points_earned' : 'loyalty_points_adjusted',
            'users',
            $customerId,
            ['points_change' => $points, 'balance_after' => $balance, 'type' => $type, 'pos_sale_id' => $saleId],
            $actor?->id
        );

        return $transaction;
    }
}
