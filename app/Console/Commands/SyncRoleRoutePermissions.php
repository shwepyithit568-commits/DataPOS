<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\StaffRole;
use App\Services\StorePermissionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Grant role permissions that a page's own route already requires.
 *
 * Roles created before a route's permission key was added to the template keep
 * the old key set, so the page renders (its menu entry is gated on a different
 * key) and then answers 403 — P&L export, the membership page and the business
 * reconciliation report all behaved that way. This command adds only the keys
 * listed below, never removes anything, and records an audit row per role.
 */
class SyncRoleRoutePermissions extends Command
{
    protected $signature = 'staff:sync-role-permissions
                            {--dry-run : Report what would be added without writing}
                            {--store= : Limit to one store id}';

    protected $description = 'Add route-required permission keys to existing staff roles (add-only, audited)';

    public const SYNC_MARKER = 'staff_role_route_permissions_2026_09';

    /**
     * Role slug => keys the page behind its menu entry already demands.
     *
     * @var array<string, array<int, string>>
     */
    protected const ROLE_KEYS = [
        'store_manager' => [
            'membership.view',
            'profit_loss.export',
            'stock_reconciliation.view',
        ],
        'accountant' => [
            'profit_loss.export',
        ],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $storeId = $this->option('store');

        $query = StaffRole::query()->orderBy('id');
        if ($storeId !== null && $storeId !== '') {
            $query->where('store_id', (int) $storeId);
        }

        $rows = [];
        $updated = 0;

        foreach ($query->get() as $role) {
            $additions = self::ROLE_KEYS[$role->slug] ?? [];
            if ($additions === []) {
                continue;
            }

            $current = $role->permissions;
            $current = is_array($current) ? $current : (json_decode((string) $current, true) ?: []);

            // Never touch a role that denies the key explicitly, and never
            // reorder what an admin already configured.
            $missing = array_values(array_diff($additions, $current));

            if ($missing === []) {
                continue;
            }

            $rows[] = [$role->id, $role->store_id, $role->slug, implode(', ', $missing), $dryRun ? 'WOULD_ADD' : 'ADDED'];

            if ($dryRun) {
                continue;
            }

            DB::transaction(function () use ($role, $current, $missing) {
                $role->permissions = array_values(array_unique(array_merge($current, $missing)));
                $role->save();

                AuditLog::write(
                    storeId: $role->store_id,
                    action: 'staff_permissions.sync_route_keys',
                    entityType: StaffRole::class,
                    entityId: $role->id,
                    metadata: [
                        'migration_marker' => self::SYNC_MARKER,
                        'actor' => 'system',
                        'role_slug' => $role->slug,
                        'before' => $current,
                        'after' => $role->permissions,
                        'added_keys' => $missing,
                    ],
                    actorId: null,
                    ipAddress: '127.0.0.1',
                );
            });

            StorePermissionService::invalidateCache($role->store_id);
            $updated++;
        }

        if ($rows !== []) {
            $this->table(['Role', 'Store', 'Slug', 'Keys added', 'Status'], $rows);
        }

        $this->info($dryRun
            ? 'Dry run: ' . count($rows) . ' role(s) would receive route-required keys.'
            : "Updated {$updated} role(s).");

        return self::SUCCESS;
    }
}
