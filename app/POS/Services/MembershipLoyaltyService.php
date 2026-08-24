<?php

namespace App\POS\Services;

use App\Models\AuditLog;
use App\Models\LoyaltyPointTransaction;
use App\Models\MembershipTier;
use App\Models\Store;
use App\Models\User;
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
}
