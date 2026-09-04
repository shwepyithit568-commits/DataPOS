<?php

namespace App\Console\Commands;

use App\BusinessProfiles\BusinessProfile;
use App\Capabilities\Capability;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillStoreSalesChannels extends Command
{
    protected $signature = 'store:backfill-sales-channels
                            {--dry-run : Analyze and report without committing changes}
                            {--report : Display detailed evidence and decision matrix per store}
                            {--force : Overwrite existing sales_channels overrides}
                            {--rollback : Rollback backfilled values safely using audit markers}';

    protected $description = 'Idempotent, transaction-safe backfill for stores.sales_channels with audit logs';

    public const MIGRATION_MARKER = 'backfill_sales_channels_2026_09';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $isReport = (bool) $this->option('report') || $isDryRun;
        $isForce = (bool) $this->option('force');
        $isRollback = (bool) $this->option('rollback');

        if ($isRollback) {
            return $this->handleRollback($isDryRun);
        }

        $this->info($isDryRun ? '🔍 [DRY-RUN] Analyzing store sales channels...' : '🚀 Applying sales channels backfill...');

        $stores = Store::orderBy('id')->get();
        if ($stores->isEmpty()) {
            $this->warn('No stores found to process.');
            return self::SUCCESS;
        }

        $summary = [
            'total' => $stores->count(),
            'updated' => 0,
            'skipped' => 0,
            'unchanged' => 0,
        ];

        $rows = [];

        foreach ($stores as $store) {
            $existing = $store->sales_channels;

            // Idempotent: skip already backfilled stores unless --force
            if (!empty($existing) && !$isForce) {
                $summary['skipped']++;
                if ($isReport) {
                    $rows[] = [
                        $store->id,
                        $store->slug,
                        $store->operation_mode ?? 'omnichannel',
                        'Already set (idempotent)',
                        json_encode($existing),
                        'SKIPPED',
                    ];
                }
                continue;
            }

            // Evidence collection
            $evidence = [];
            $posActive = true; // Invariant: POS is protected in this phase

            // Check operation mode & overrides
            $opMode = $store->operation_mode ?? BusinessProfile::MODE_OMNICHANNEL;
            $overrides = is_array($store->capabilities_override) ? $store->capabilities_override : [];

            $ecommerceCapabilityDisabled = isset($overrides[Capability::STOREFRONT_ECOMMERCE]) && !$overrides[Capability::STOREFRONT_ECOMMERCE];
            $orderingCapabilityDisabled = isset($overrides[Capability::STOREFRONT_ONLINE_ORDERING]) && !$overrides[Capability::STOREFRONT_ONLINE_ORDERING];

            // Evidence for online store
            $hasProducts = Product::where('store_id', $store->id)->exists();
            $hasBanners = $store->homeBanners()->exists();
            $hasOrders = Order::where('store_id', $store->id)->exists();

            if ($hasProducts) $evidence[] = 'products_present';
            if ($hasBanners) $evidence[] = 'banners_configured';
            if ($hasOrders) $evidence[] = 'orders_recorded';

            if ($opMode === BusinessProfile::MODE_POS_ONLY || $ecommerceCapabilityDisabled) {
                $onlineStore = false;
                $onlineOrdering = false;
                $evidence[] = 'pos_only_constraint';
            } elseif ($opMode === BusinessProfile::MODE_CATALOG_ONLY || $orderingCapabilityDisabled) {
                $onlineStore = true;
                $onlineOrdering = false;
                $evidence[] = 'catalog_only_constraint';
            } else {
                // Omnichannel or standard store
                $onlineStore = true;
                $onlineOrdering = true;
                $evidence[] = 'omnichannel_default';
            }

            $newChannels = [
                Store::CHANNEL_POS => $posActive,
                Store::CHANNEL_ONLINE_STORE => $onlineStore,
                Store::CHANNEL_ONLINE_ORDERING => $onlineOrdering,
            ];

            if ($existing === $newChannels) {
                $summary['unchanged']++;
                $status = 'UNCHANGED';
            } else {
                $summary['updated']++;
                $status = $isDryRun ? 'WOULD_UPDATE' : 'UPDATED';

                if (!$isDryRun) {
                    DB::transaction(function () use ($store, $existing, $newChannels, $evidence) {
                        $store->sales_channels = $newChannels;
                        $store->save();

                        AuditLog::write(
                            storeId: $store->id,
                            action: 'sales_channels.backfill',
                            entityType: Store::class,
                            entityId: $store->id,
                            metadata: [
                                'migration_marker' => self::MIGRATION_MARKER,
                                'actor' => 'system',
                                'before' => $existing,
                                'after' => $newChannels,
                                'evidence' => $evidence,
                            ],
                            actorId: null,
                            ipAddress: '127.0.0.1'
                        );
                    });
                }
            }

            if ($isReport) {
                $rows[] = [
                    $store->id,
                    $store->slug,
                    $opMode,
                    implode(', ', $evidence),
                    json_encode($newChannels),
                    $status,
                ];
            }
        }

        if ($isReport && !empty($rows)) {
            $this->table(
                ['Store ID', 'Slug', 'Mode', 'Evidence', 'Target Channels', 'Status'],
                $rows
            );
        }

        $this->newLine();
        $this->info("Summary: Total: {$summary['total']} | Updated: {$summary['updated']} | Skipped: {$summary['skipped']} | Unchanged: {$summary['unchanged']}");

        return self::SUCCESS;
    }

    /**
     * Safely revert backfilled values using the migration marker in audit logs.
     */
    protected function handleRollback(bool $isDryRun): int
    {
        $this->warn($isDryRun ? '🔍 [DRY-RUN] Checking rollback eligibility...' : '⏪ Rolling back backfilled sales channels...');

        $logs = AuditLog::where('action', 'sales_channels.backfill')
            ->whereJsonContains('metadata->migration_marker', self::MIGRATION_MARKER)
            ->get();

        if ($logs->isEmpty()) {
            $this->info('No backfilled audit records found to rollback.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($logs as $log) {
            $store = Store::find($log->store_id);
            if (!$store) {
                continue;
            }

            $currentChannels = $store->sales_channels;
            $backfilledChannels = $log->metadata['after'] ?? null;

            // Only rollback if the store's channels still match the backfilled state (owner hasn't customized since)
            if ($currentChannels === $backfilledChannels) {
                $count++;
                if (!$isDryRun) {
                    DB::transaction(function () use ($store, $log, $currentChannels) {
                        $store->sales_channels = $log->metadata['before'] ?? null;
                        $store->save();

                        AuditLog::write(
                            storeId: $store->id,
                            action: 'sales_channels.rollback',
                            entityType: Store::class,
                            entityId: $store->id,
                            metadata: [
                                'migration_marker' => self::MIGRATION_MARKER,
                                'actor' => 'system',
                                'reverted_from' => $currentChannels,
                                'reverted_to' => $log->metadata['before'] ?? null,
                            ],
                            actorId: null,
                            ipAddress: '127.0.0.1'
                        );
                    });
                }
            }
        }

        $this->info($isDryRun ? "Dry-run rollback identified {$count} store(s) to revert." : "Rolled back {$count} store(s) safely.");
        return self::SUCCESS;
    }
}
