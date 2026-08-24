<?php

namespace App\POS\Services;

use App\Models\Store;
use App\Models\User;
use App\POS\Models\DeviceWarranty;
use App\POS\Models\ServiceJob;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class WarrantyTrackerService
{
    /**
     * Get summary KPI statistics for a store's warranties.
     *
     * @return array{total: int, active: int, expiring_soon: int, expired: int, claimed: int}
     */
    public function getStatistics(Store $store): array
    {
        $today = Carbon::today()->toDateString();
        $in30Days = Carbon::today()->addDays(30)->toDateString();

        $base = DeviceWarranty::where('store_id', $store->id);

        $total = (clone $base)->count();

        $active = (clone $base)
            ->where('status', 'active')
            ->where('warranty_expiry_date', '>=', $today)
            ->count();

        $expiringSoon = (clone $base)
            ->where('status', 'active')
            ->whereBetween('warranty_expiry_date', [$today, $in30Days])
            ->count();

        $expired = (clone $base)
            ->where(function ($q) use ($today) {
                $q->where('status', 'expired')
                    ->orWhere(function ($q2) use ($today) {
                        $q2->where('status', 'active')->where('warranty_expiry_date', '<', $today);
                    });
            })
            ->count();

        $claimed = (clone $base)
            ->where(function ($q) {
                $q->where('status', 'claimed')
                    ->orWhere('claim_count', '>', 0);
            })
            ->count();

        return compact('total', 'active', 'expiringSoon', 'expired', 'claimed');
    }

    /**
     * Search and paginate device warranties for store admin.
     */
    public function listWarranties(Store $store, ?string $search = null, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return DeviceWarranty::forStore($store->id)
            ->with(['product', 'customer', 'sale', 'creator'])
            ->search($search)
            ->status($status)
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Fast barcode / IMEI scanner lookup returning matching records.
     *
     * @return Collection<int, DeviceWarranty>
     */
    public function quickScanLookup(Store $store, string $query): Collection
    {
        $clean = trim($query);
        if (empty($clean)) {
            return new Collection();
        }

        return DeviceWarranty::forStore($store->id)
            ->with(['product', 'customer', 'sale'])
            ->search($clean)
            ->take(10)
            ->get();
    }

    /**
     * Register a new device warranty card.
     */
    public function register(Store $store, array $data, ?User $user = null): DeviceWarranty
    {
        $purchaseDate = Carbon::parse($data['purchase_date'] ?? now());
        $months = (int) ($data['warranty_duration_months'] ?? 12);
        $expiryDate = $purchaseDate->copy()->addMonths($months);

        return DeviceWarranty::create([
            'store_id' => $store->id,
            'product_id' => $data['product_id'] ?? null,
            'product_name' => $data['product_name'],
            'customer_id' => $data['customer_id'] ?? null,
            'customer_name' => $data['customer_name'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'serial_number' => trim($data['serial_number']),
            'imei_primary' => ! empty($data['imei_primary']) ? trim($data['imei_primary']) : null,
            'imei_secondary' => ! empty($data['imei_secondary']) ? trim($data['imei_secondary']) : null,
            'invoice_number' => ! empty($data['invoice_number']) ? trim($data['invoice_number']) : null,
            'pos_sale_id' => $data['pos_sale_id'] ?? null,
            'purchase_date' => $purchaseDate->toDateString(),
            'warranty_duration_months' => $months,
            'warranty_expiry_date' => $expiryDate->toDateString(),
            'warranty_type' => $data['warranty_type'] ?? 'shop',
            'status' => $data['status'] ?? 'active',
            'terms_conditions' => $data['terms_conditions'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $user?->id,
        ]);
    }

    /**
     * Record a warranty claim / replacement.
     */
    public function recordClaim(DeviceWarranty $warranty, array $data, ?User $user = null): DeviceWarranty
    {
        $claimNote = "\n[" . now()->format('Y-m-d H:i') . ' Claimed by ' . ($user->name ?? 'Staff') . ']: ' . ($data['claim_reason'] ?? 'Warranty Claim logged.');
        if (! empty($data['resolution'])) {
            $claimNote .= ' Resolution: ' . $data['resolution'];
        }

        $warranty->claim_count++;
        $warranty->last_claimed_at = now();
        $warranty->notes = trim(($warranty->notes ?? '') . $claimNote);

        if (! empty($data['status'])) {
            $warranty->status = $data['status'];
        }

        $warranty->save();

        return $warranty;
    }

    /**
     * Retrieve related repair / service jobs logged for this device's IMEI/Serial.
     *
     * @return Collection<int, ServiceJob>
     */
    public function getServiceHistory(DeviceWarranty $warranty): Collection
    {
        $identifiers = array_filter([
            $warranty->serial_number,
            $warranty->imei_primary,
            $warranty->imei_secondary,
        ]);

        if (empty($identifiers)) {
            return new Collection();
        }

        return ServiceJob::where('store_id', $warranty->store_id)
            ->where(function ($q) use ($identifiers) {
                foreach ($identifiers as $id) {
                    $q->orWhere('imei_serial', 'like', "%{$id}%");
                }
            })
            ->with(['technician', 'customer'])
            ->latest('received_at')
            ->get();
    }
}
