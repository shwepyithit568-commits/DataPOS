<?php

namespace App\POS\Services;

use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CurrencyExchangeService
{
    /**
     * Auto-seed default currencies if store has none.
     */
    public function ensureDefaultCurrencies(Store $store): void
    {
        $count = Currency::where('store_id', $store->id)->count();
        if ($count > 0) {
            return;
        }

        DB::transaction(function () use ($store) {
            $defaults = [
                [
                    'code' => 'MMK',
                    'name' => 'Myanmar Kyat',
                    'symbol' => 'Ks',
                    'exchange_rate' => 1.0000,
                    'is_base' => true,
                    'is_active' => true,
                ],
                [
                    'code' => 'USD',
                    'name' => 'US Dollar',
                    'symbol' => '$',
                    'exchange_rate' => 4500.0000,
                    'is_base' => false,
                    'is_active' => true,
                ],
                [
                    'code' => 'THB',
                    'name' => 'Thai Baht',
                    'symbol' => '฿',
                    'exchange_rate' => 135.0000,
                    'is_base' => false,
                    'is_active' => true,
                ],
                [
                    'code' => 'CNY',
                    'name' => 'Chinese Yuan',
                    'symbol' => '¥',
                    'exchange_rate' => 630.0000,
                    'is_base' => false,
                    'is_active' => true,
                ],
                [
                    'code' => 'SGD',
                    'name' => 'Singapore Dollar',
                    'symbol' => 'S$',
                    'exchange_rate' => 3450.0000,
                    'is_base' => false,
                    'is_active' => true,
                ],
            ];

            foreach ($defaults as $curr) {
                Currency::create(array_merge($curr, [
                    'store_id' => $store->id,
                    'last_updated_at' => now(),
                ]));
            }
        });
    }

    /**
     * Get all currencies for the store.
     *
     * @return Collection<int, Currency>
     */
    public function getCurrencies(Store $store): Collection
    {
        $this->ensureDefaultCurrencies($store);

        return Currency::where('store_id', $store->id)
            ->orderByDesc('is_base')
            ->orderBy('code')
            ->get();
    }

    /**
     * Get summary KPI stats.
     *
     * @return array<string, mixed>
     */
    public function getSummaryStats(Store $store): array
    {
        $this->ensureDefaultCurrencies($store);

        $currencies = Currency::where('store_id', $store->id)->get();
        $usd = $currencies->firstWhere('code', 'USD');
        $thb = $currencies->firstWhere('code', 'THB');
        $cny = $currencies->firstWhere('code', 'CNY');
        $sgd = $currencies->firstWhere('code', 'SGD');

        return [
            'total_currencies' => $currencies->count(),
            'active_currencies' => $currencies->where('is_active', true)->count(),
            'usd_rate' => $usd ? $usd->exchange_rate : 0.0,
            'thb_rate' => $thb ? $thb->exchange_rate : 0.0,
            'cny_rate' => $cny ? $cny->exchange_rate : 0.0,
            'sgd_rate' => $sgd ? $sgd->exchange_rate : 0.0,
        ];
    }

    /**
     * Save (create or update) a currency.
     */
    public function saveCurrency(
        Store $store,
        array $data,
        ?Currency $currency = null,
        ?User $user = null
    ): Currency {
        return DB::transaction(function () use ($store, $data, $currency, $user) {
            $isBase = !empty($data['is_base']);
            $rate = (float) ($data['exchange_rate'] ?? 1.0);

            if ($currency && $currency->is_base) {
                $isBase = true;
                $rate = 1.0;
            }

            if ($isBase) {
                $rate = 1.0;
            }

            $attributes = [
                'code' => strtoupper(trim($data['code'])),
                'name' => trim($data['name']),
                'symbol' => trim($data['symbol'] ?? ''),
                'exchange_rate' => $rate,
                'is_base' => $isBase,
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
                'last_updated_at' => now(),
            ];

            if ($currency) {
                $currency->update($attributes);
                $action = 'currency_updated';
            } else {
                $currency = Currency::create(array_merge($attributes, ['store_id' => $store->id]));
                $action = 'currency_created';
            }

            AuditLog::write(
                $store->id,
                $action,
                'currencies',
                $currency->id,
                ['code' => $currency->code, 'rate' => $currency->exchange_rate],
                $user?->id
            );

            return $currency;
        });
    }

    /**
     * Bulk update currency rates.
     *
     * @param array<int|string, float|int|string> $rates [currency_id => rate]
     */
    public function bulkUpdateRates(Store $store, array $rates, ?User $user = null): int
    {
        $updatedCount = 0;

        DB::transaction(function () use ($store, $rates, $user, &$updatedCount) {
            foreach ($rates as $id => $rate) {
                $currency = Currency::where('store_id', $store->id)->find($id);
                if (!$currency || $currency->is_base) {
                    continue; // Skip base currency or not found
                }

                $numericRate = max(0.0001, (float) $rate);
                $currency->update([
                    'exchange_rate' => $numericRate,
                    'last_updated_at' => now(),
                ]);
                $updatedCount++;
            }

            AuditLog::write(
                $store->id,
                'currency_rates_bulk_updated',
                'currencies',
                0,
                ['count' => $updatedCount],
                $user?->id
            );
        });

        return $updatedCount;
    }

    /**
     * Delete a currency (cannot delete base).
     */
    public function deleteCurrency(Store $store, Currency $currency, ?User $user = null): bool
    {
        if ($currency->is_base) {
            throw new \RuntimeException('Cannot delete the base store currency.');
        }

        return DB::transaction(function () use ($store, $currency, $user) {
            $code = $currency->code;
            $id = $currency->id;
            $currency->delete();

            AuditLog::write(
                $store->id,
                'currency_deleted',
                'currencies',
                $id,
                ['code' => $code],
                $user?->id
            );

            return true;
        });
    }

    /**
     * Convert an amount between two currencies.
     */
    public function convert(Store $store, float $amount, string $fromCode, string $toCode): float
    {
        $this->ensureDefaultCurrencies($store);

        if ($fromCode === $toCode) {
            return $amount;
        }

        $from = Currency::where('store_id', $store->id)->where('code', $fromCode)->first();
        $to = Currency::where('store_id', $store->id)->where('code', $toCode)->first();

        if (!$from || !$to || $from->exchange_rate <= 0 || $to->exchange_rate <= 0) {
            return $amount;
        }

        // Amount in Base (MMK) = amount * from_rate
        $amountInBase = $amount * $from->exchange_rate;

        // Amount in Target = amountInBase / to_rate
        return $amountInBase / $to->exchange_rate;
    }
}
