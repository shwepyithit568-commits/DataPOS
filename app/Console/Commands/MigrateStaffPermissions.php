<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\StaffRole;
use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateStaffPermissions extends Command
{
    protected $signature = 'staff:migrate-permissions
                            {--dry-run : Analyze and report without committing changes}
                            {--report : Display detailed mapping per role}
                            {--force : Force migration even if already marked}
                            {--rollback : Safely restore roles from audit snapshot}';

    protected $description = 'Expand legacy role template .edit permissions into explicit .create and .update keys with auditable snapshot';

    public const MIGRATION_MARKER = 'staff_permissions_migration_2026_09';

    /**
     * Modules where .edit historically authorized both creating and updating records.
     */
    protected const CREATABLE_MODULES = [
        'products',
        'master_data',
        'customers',
        'suppliers',
        'purchases',
        'purchase_returns',
        'transfers',
        'expenses',
        'expense_categories',
        'repairs',
        'spare_parts',
        'stock_adjustments',
        'stock_count',
        'promotions',
        'coupons',
        'flash_sales',
        'membership_tiers',
        'loyalty',
        'customer_groups',
    ];

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $isReport = (bool) $this->option('report') || $isDryRun;
        $isForce = (bool) $this->option('force');
        $isRollback = (bool) $this->option('rollback');

        if ($isRollback) {
            return $this->handleRollback($isDryRun);
        }

        $this->info($isDryRun ? '🔍 [DRY-RUN] Analyzing staff permissions migration...' : '🚀 Applying staff permissions migration...');

        $roles = StaffRole::orderBy('id')->get();
        if ($roles->isEmpty()) {
            $this->warn('No staff roles found.');
            return self::SUCCESS;
        }

        $summary = [
            'total' => $roles->count(),
            'updated' => 0,
            'unchanged' => 0,
            'wildcards_quarantined' => 0,
        ];

        $rows = [];

        foreach ($roles as $role) {
            $currentPermissions = $role->permissions ?? [];
            if (!is_array($currentPermissions)) {
                $currentPermissions = [];
            }

            // Quarantine non-system wildcards
            if (in_array('*', $currentPermissions, true) && !$role->is_system && $role->slug !== 'store_owner') {
                $summary['wildcards_quarantined']++;
            }

            $newPermissions = $currentPermissions;
            $addedKeys = [];

            foreach ($currentPermissions as $perm) {
                if (str_ends_with($perm, '.edit')) {
                    $prefix = substr($perm, 0, -5);

                    // Add .update alias
                    $updateKey = $prefix . '.update';
                    if (!in_array($updateKey, $newPermissions, true)) {
                        $newPermissions[] = $updateKey;
                        $addedKeys[] = $updateKey;
                    }

                    // Add .create if historically creatable
                    if (in_array($prefix, self::CREATABLE_MODULES, true)) {
                        $createKey = $prefix . '.create';
                        if (!in_array($createKey, $newPermissions, true)) {
                            $newPermissions[] = $createKey;
                            $addedKeys[] = $createKey;
                        }
                    }
                }
            }

            $newPermissions = array_values(array_unique($newPermissions));

            if ($newPermissions === $currentPermissions) {
                $summary['unchanged']++;
                $status = 'UNCHANGED';
            } else {
                $summary['updated']++;
                $status = $isDryRun ? 'WOULD_UPDATE' : 'UPDATED';

                if (!$isDryRun) {
                    DB::transaction(function () use ($role, $currentPermissions, $newPermissions, $addedKeys) {
                        $role->permissions = $newPermissions;
                        $role->save();

                        AuditLog::write(
                            storeId: $role->store_id,
                            action: 'staff_permissions.migrate',
                            entityType: StaffRole::class,
                            entityId: $role->id,
                            metadata: [
                                'migration_marker' => self::MIGRATION_MARKER,
                                'actor' => 'system',
                                'role_slug' => $role->slug,
                                'before' => $currentPermissions,
                                'after' => $newPermissions,
                                'added_keys' => $addedKeys,
                            ],
                            actorId: null,
                            ipAddress: '127.0.0.1'
                        );
                    });
                }
            }

            if ($isReport) {
                $rows[] = [
                    $role->id,
                    $role->name,
                    $role->slug,
                    count($currentPermissions),
                    count($newPermissions),
                    empty($addedKeys) ? '-' : implode(', ', array_slice($addedKeys, 0, 4)) . (count($addedKeys) > 4 ? '...' : ''),
                    $status,
                ];
            }
        }

        if ($isReport && !empty($rows)) {
            $this->table(
                ['Role ID', 'Name', 'Slug', 'Old Perms', 'New Perms', 'Added Keys', 'Status'],
                $rows
            );
        }

        $this->newLine();
        $this->info("Summary: Total: {$summary['total']} | Updated: {$summary['updated']} | Unchanged: {$summary['unchanged']} | Quarantined Wildcards: {$summary['wildcards_quarantined']}");

        return self::SUCCESS;
    }

    /**
     * Safely restore staff roles from migration snapshot.
     */
    protected function handleRollback(bool $isDryRun): int
    {
        $this->warn($isDryRun ? '🔍 [DRY-RUN] Checking staff permission rollback eligibility...' : '⏪ Rolling back staff permission migration...');

        $logs = AuditLog::where('action', 'staff_permissions.migrate')
            ->whereJsonContains('metadata->migration_marker', self::MIGRATION_MARKER)
            ->get();

        if ($logs->isEmpty()) {
            $this->info('No staff permissions migration audit snapshots found.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($logs as $log) {
            $role = StaffRole::find($log->entity_id);
            if (!$role) {
                continue;
            }

            $currentPermissions = $role->permissions;
            $migratedPermissions = $log->metadata['after'] ?? null;

            // Only rollback if permissions match migrated snapshot (not modified by user since)
            if ($currentPermissions === $migratedPermissions) {
                $count++;
                if (!$isDryRun) {
                    DB::transaction(function () use ($role, $log, $currentPermissions) {
                        $role->permissions = $log->metadata['before'] ?? [];
                        $role->save();

                        AuditLog::write(
                            storeId: $role->store_id,
                            action: 'staff_permissions.rollback',
                            entityType: StaffRole::class,
                            entityId: $role->id,
                            metadata: [
                                'migration_marker' => self::MIGRATION_MARKER,
                                'actor' => 'system',
                                'reverted_from' => $currentPermissions,
                                'reverted_to' => $log->metadata['before'] ?? [],
                            ],
                            actorId: null,
                            ipAddress: '127.0.0.1'
                        );
                    });
                }
            }
        }

        $this->info($isDryRun ? "Dry-run rollback identified {$count} role(s) to restore." : "Restored {$count} role(s) safely.");
        return self::SUCCESS;
    }
}
