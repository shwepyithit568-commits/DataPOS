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
            'settings.update',
            'expense_categories.view',
            'banners.view',
            'web_products.view',
            'glass_finder.view',
            'opening_stock.view',
            'product_import.view',
            'roles.view',
            'roles.export',
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
            $companions = $this->companionExportsFor($role);
            $additions = array_values(array_unique(array_merge($additions, $companions)));

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

    /**
     * The `.export` keys that pair with the `.view` keys this role holds.
     *
     * A list page shows its XLSX/CSV buttons to anyone who can view it, but the
     * export route checks a separate key — that mismatch is what made 24 of 67
     * export endpoints answer 403 for the store manager. Only keys present in
     * the permission catalogue are offered, so nothing the routes do not check
     * is ever invented.
     *
     * @return array<int, string>
     */
    protected function companionExportsFor(StaffRole $role): array
    {
        $permissions = is_array($role->permissions)
            ? $role->permissions
            : (json_decode((string) $role->permissions, true) ?: []);

        if ($permissions === [] || in_array('*', $permissions, true)) {
            return [];
        }

        $catalogue = array_flip(StaffRole::allPermissionKeys()->all());

        $companions = [];
        foreach ($permissions as $granted) {
            if (! is_string($granted) || ! str_ends_with($granted, '.view')) {
                continue;
            }

            $export = substr($granted, 0, -5) . '.export';

            if (isset($catalogue[$export]) && ! in_array($export, $permissions, true)) {
                $companions[] = $export;
            }
        }

        return array_values(array_unique($companions));
    }
}
