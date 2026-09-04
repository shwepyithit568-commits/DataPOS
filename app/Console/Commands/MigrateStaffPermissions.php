<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\StaffRole;
use App\Services\StorePermissionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateStaffPermissions extends Command
{
    protected $signature = 'staff:migrate-permissions
                            {--dry-run : Analyze and report without committing changes}
                            {--report : Display detailed mapping per role}
                            {--force : Force migration even if already marked}
                            {--rollback : Safely restore roles from audit snapshot}';

    protected $description = 'Expand legacy role template and custom user .edit permissions into explicit .create and .update keys with auditable snapshot';

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

        // 1. Migrate StaffRole templates
        $roles = StaffRole::orderBy('id')->get();
        $roleSummary = [
            'total' => $roles->count(),
            'updated' => 0,
            'unchanged' => 0,
            'wildcards_quarantined' => 0,
        ];

        $roleRows = [];

        foreach ($roles as $role) {
            $currentPermissions = $role->permissions ?? [];
            if (!is_array($currentPermissions)) {
                $currentPermissions = [];
            }

            // Quarantine non-system wildcards
            if (in_array('*', $currentPermissions, true) && !$role->is_system && $role->slug !== 'store_owner') {
                $roleSummary['wildcards_quarantined']++;
            }

            [$newPermissions, $addedKeys] = $this->migratePermissionsSet($currentPermissions);

            if ($newPermissions === $currentPermissions) {
                $roleSummary['unchanged']++;
                $status = 'UNCHANGED';
            } else {
                $roleSummary['updated']++;
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

                    StorePermissionService::invalidateCache($role->store_id);
                }
            }

            if ($isReport) {
                $roleRows[] = [
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

        // 2. Migrate store_user.custom_permissions
        $storeUsers = DB::table('store_user')
            ->whereNotNull('custom_permissions')
            ->get();

        $userSummary = [
            'total' => $storeUsers->count(),
            'updated' => 0,
            'unchanged' => 0,
        ];

        $userRows = [];

        foreach ($storeUsers as $su) {
            $currentPermissions = $su->custom_permissions;
            [$newPermissions, $addedKeys] = $this->migratePermissionsSet($currentPermissions);

            $currentNormalized = is_string($currentPermissions) ? json_decode($currentPermissions, true) : $currentPermissions;
            $newNormalized = is_string($newPermissions) ? json_decode($newPermissions, true) : $newPermissions;

            if ($currentNormalized === $newNormalized || empty($addedKeys)) {
                $userSummary['unchanged']++;
                $status = 'UNCHANGED';
            } else {
                $userSummary['updated']++;
                $status = $isDryRun ? 'WOULD_UPDATE' : 'UPDATED';

                if (!$isDryRun) {
                    $jsonValue = is_string($newPermissions) ? $newPermissions : json_encode($newPermissions);
                    DB::transaction(function () use ($su, $currentPermissions, $jsonValue, $addedKeys, $currentNormalized, $newNormalized) {
                        DB::table('store_user')
                            ->where('id', $su->id)
                            ->update(['custom_permissions' => $jsonValue]);

                        AuditLog::write(
                            storeId: $su->store_id,
                            action: 'staff_permissions.migrate',
                            entityType: 'store_user',
                            entityId: $su->id,
                            metadata: [
                                'migration_marker' => self::MIGRATION_MARKER,
                                'actor' => 'system',
                                'store_id' => $su->store_id,
                                'user_id' => $su->user_id,
                                'before' => $currentNormalized,
                                'after' => $newNormalized,
                                'added_keys' => $addedKeys,
                            ],
                            actorId: null,
                            ipAddress: '127.0.0.1'
                        );
                    });

                    StorePermissionService::invalidateCache($su->store_id, $su->user_id);
                }
            }

            if ($isReport) {
                $userRows[] = [
                    $su->id,
                    "Store #{$su->store_id} / User #{$su->user_id}",
                    empty($addedKeys) ? '-' : implode(', ', array_slice($addedKeys, 0, 4)) . (count($addedKeys) > 4 ? '...' : ''),
                    $status,
                ];
            }
        }

        if ($isReport && !empty($roleRows)) {
            $this->info('--- Staff Roles ---');
            $this->table(
                ['Role ID', 'Name', 'Slug', 'Old Perms', 'New Perms', 'Added Keys', 'Status'],
                $roleRows
            );
        }

        if ($isReport && !empty($userRows)) {
            $this->info('--- Store User Custom Permissions ---');
            $this->table(
                ['Pivot ID', 'Assignment', 'Added Keys', 'Status'],
                $userRows
            );
        }

        $this->newLine();
        $this->info("Roles Summary: Total: {$roleSummary['total']} | Updated: {$roleSummary['updated']} | Unchanged: {$roleSummary['unchanged']} | Quarantined Wildcards: {$roleSummary['wildcards_quarantined']}");
        $this->info("Store User Summary: Total: {$userSummary['total']} | Updated: {$userSummary['updated']} | Unchanged: {$userSummary['unchanged']}");

        return self::SUCCESS;
    }

    /**
     * Safely restore staff roles and store_user custom permissions from migration snapshot.
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

        $roleCount = 0;
        $userCount = 0;

        foreach ($logs as $log) {
            if ($log->entity_type === StaffRole::class) {
                $role = StaffRole::find($log->entity_id);
                if (!$role) {
                    continue;
                }

                $currentPermissions = $role->permissions;
                $migratedPermissions = $log->metadata['after'] ?? null;

                if ($currentPermissions === $migratedPermissions) {
                    $roleCount++;
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

                        StorePermissionService::invalidateCache($role->store_id);
                    }
                }
            } elseif ($log->entity_type === 'store_user') {
                $pivot = DB::table('store_user')->where('id', $log->entity_id)->first();
                if (!$pivot) {
                    continue;
                }

                $currentVal = $pivot->custom_permissions;
                $currentNormalized = is_string($currentVal) ? json_decode($currentVal, true) : $currentVal;
                $migratedNormalized = $log->metadata['after'] ?? null;

                if ($currentNormalized === $migratedNormalized) {
                    $userCount++;
                    if (!$isDryRun) {
                        $revertValue = $log->metadata['before'] ?? null;
                        $jsonVal = is_array($revertValue) ? json_encode($revertValue) : $revertValue;

                        DB::transaction(function () use ($pivot, $log, $jsonVal, $currentNormalized) {
                            DB::table('store_user')
                                ->where('id', $pivot->id)
                                ->update(['custom_permissions' => $jsonVal]);

                            AuditLog::write(
                                storeId: $pivot->store_id,
                                action: 'staff_permissions.rollback',
                                entityType: 'store_user',
                                entityId: $pivot->id,
                                metadata: [
                                    'migration_marker' => self::MIGRATION_MARKER,
                                    'actor' => 'system',
                                    'store_id' => $pivot->store_id,
                                    'user_id' => $pivot->user_id,
                                    'reverted_from' => $currentNormalized,
                                    'reverted_to' => $log->metadata['before'] ?? null,
                                ],
                                actorId: null,
                                ipAddress: '127.0.0.1'
                            );
                        });

                        StorePermissionService::invalidateCache($pivot->store_id, $pivot->user_id);
                    }
                }
            }
        }

        $this->info($isDryRun
            ? "Dry-run rollback identified {$roleCount} role(s) and {$userCount} user assignment(s) to restore."
            : "Restored {$roleCount} role(s) and {$userCount} user assignment(s) safely.");

        return self::SUCCESS;
    }

    /**
     * Migrate a permissions set (list or map) by expanding .edit to .update and .create.
     *
     * @param mixed $current
     * @return array{0: mixed, 1: array<string>} [newPermissions, addedKeys]
     */
    protected function migratePermissionsSet(mixed $current): array
    {
        if (empty($current)) {
            return [$current, []];
        }

        $isJsonString = false;
        if (is_string($current)) {
            $decoded = json_decode($current, true);
            if (is_array($decoded)) {
                $current = $decoded;
                $isJsonString = true;
            } else {
                return [$current, []];
            }
        }

        if (!is_array($current)) {
            return [$current, []];
        }

        $allCanonical = StaffRole::allPermissionKeys()->flip()->all();
        $addedKeys = [];

        // Format 1: ['grants' => [...], 'denies' => [...]]
        if (isset($current['grants']) || isset($current['denies'])) {
            $newGrants = (array) ($current['grants'] ?? []);
            $newDenies = (array) ($current['denies'] ?? []);

            foreach (['grants' => &$newGrants, 'denies' => &$newDenies] as $type => &$list) {
                $toAdd = [];
                foreach ($list as $p) {
                    if (is_string($p) && str_ends_with($p, '.edit')) {
                        $prefix = substr($p, 0, -5);
                        $updateKey = $prefix . '.update';
                        $createKey = $prefix . '.create';

                        if (!in_array($updateKey, $list, true) && !in_array($updateKey, $toAdd, true)) {
                            $toAdd[] = $updateKey;
                            $addedKeys[] = $updateKey;
                        }
                        if ((isset($allCanonical[$createKey]) || in_array($prefix, self::CREATABLE_MODULES, true)) &&
                            !in_array($createKey, $list, true) && !in_array($createKey, $toAdd, true)) {
                            $toAdd[] = $createKey;
                            $addedKeys[] = $createKey;
                        }
                    }
                }
                $list = array_values(array_unique(array_merge($list, $toAdd)));
            }

            $result = ['grants' => $newGrants, 'denies' => $newDenies];
            return [$isJsonString ? json_encode($result) : $result, $addedKeys];
        }

        // Format 2: Associative map ['products.edit' => true, ...]
        $isMap = array_keys($current) !== range(0, count($current) - 1);
        if ($isMap) {
            $newMap = $current;
            foreach ($current as $perm => $val) {
                if (is_string($perm) && str_ends_with($perm, '.edit')) {
                    $prefix = substr($perm, 0, -5);
                    $updateKey = $prefix . '.update';
                    $createKey = $prefix . '.create';

                    if (!isset($newMap[$updateKey])) {
                        $newMap[$updateKey] = $val;
                        $addedKeys[] = $updateKey;
                    }
                    if ((isset($allCanonical[$createKey]) || in_array($prefix, self::CREATABLE_MODULES, true)) && !isset($newMap[$createKey])) {
                        $newMap[$createKey] = $val;
                        $addedKeys[] = $createKey;
                    }
                }
            }
            return [$isJsonString ? json_encode($newMap) : $newMap, $addedKeys];
        }

        // Format 3: Simple string list ['products.edit', '-repairs.edit', ...]
        $newList = $current;
        $toAdd = [];
        foreach ($current as $perm) {
            if (!is_string($perm)) {
                continue;
            }
            $cleanPerm = ltrim($perm, '!-');
            $prefixChar = (str_starts_with($perm, '!') || str_starts_with($perm, '-')) ? $perm[0] : '';

            if (str_ends_with($cleanPerm, '.edit')) {
                $resource = substr($cleanPerm, 0, -5);
                $updateKey = $prefixChar . $resource . '.update';
                $createKey = $prefixChar . $resource . '.create';

                if (!in_array($updateKey, $newList, true) && !in_array($updateKey, $toAdd, true)) {
                    $toAdd[] = $updateKey;
                    $addedKeys[] = $updateKey;
                }
                if ((isset($allCanonical[$resource . '.create']) || in_array($resource, self::CREATABLE_MODULES, true)) &&
                    !in_array($createKey, $newList, true) && !in_array($createKey, $toAdd, true)) {
                    $toAdd[] = $createKey;
                    $addedKeys[] = $createKey;
                }
            }
        }

        $newList = array_values(array_unique(array_merge($newList, $toAdd)));
        return [$isJsonString ? json_encode($newList) : $newList, $addedKeys];
    }
}
