<?php

namespace App\Services;

use App\Capabilities\Capability;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdminNavigationService
{
    public function __construct(
        protected StoreContext $storeContext
    ) {
    }

    /**
     * Determine if current request is strictly in Platform Scope (/admin/* but not /store/*).
     */
    public function isPlatformScope(?Request $request = null): bool
    {
        $req = $request ?? request();
        return $req->is('admin/*') && ! $req->is('store/*');
    }

    /**
     * Determine if current request is in Store Scope (/store/*).
     */
    public function isStoreScope(?Request $request = null): bool
    {
        $req = $request ?? request();
        return filled($req->route('store_slug')) || $req->is('store/*');
    }

    /**
     * Check if the authenticated user has permission for a given key in the store.
     */
    public function userHasPermission(?User $user, ?Store $store, string|array $permissions): bool
    {
        if (! $user) {
            return false;
        }

        // Platform-level access without store context
        if ($user->isPlatformOwner() && ! $store) {
            return true;
        }

        if (! $store) {
            return false;
        }

        $permList = (array) $permissions;
        if (empty($permList)) {
            return true;
        }

        // Unified permission resolution without unsafe unconditional bypasses (Plan §6)
        return app(StorePermissionService::class)->canAny($user, $store, $permList);
    }

    public function can(?User $user, ?Store $store, string $permission): bool
    {
        if (! $user || ! $store) {
            return false;
        }
        return app(StorePermissionService::class)->can($user, $store, $permission);
    }

    public function canAny(?User $user, ?Store $store, array $permissions): bool
    {
        if (! $user || ! $store) {
            return false;
        }
        return app(StorePermissionService::class)->canAny($user, $store, $permissions);
    }

    public function canAccessStaffTools(?User $user, ?Store $store): bool
    {
        if (! $user || ! $store) {
            return false;
        }
        if ($user->isPlatformOwner() || $user->isStoreOwner($store->id)) {
            return true;
        }
        return $user->hasStoreRole($store->id, ['store_manager', 'staff']);
    }

    public function canManageSettings(?User $user, ?Store $store): bool
    {
        if (! $user || ! $store) {
            return false;
        }
        if ($user->isPlatformOwner() || $user->isStoreOwner($store->id)) {
            return true;
        }
        return $user->hasStoreRole($store->id, 'store_manager')
            && $this->userHasPermission($user, $store, ['settings.view', 'settings.edit']);
    }

    public function canManageUsers(?User $user, ?Store $store): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->isPlatformOwner()) {
            return true;
        }
        return $store && $user->isStoreOwner($store->id);
    }

    public function canManageFinance(?User $user, ?Store $store): bool
    {
        if (! $user || ! $store) {
            return false;
        }
        if ($user->isPlatformOwner() || $user->isStoreOwner($store->id)) {
            return true;
        }
        return $user->hasStoreRole($store->id, ['store_owner', 'store_manager'])
            || $this->userHasPermission($user, $store, ['profit_loss.view', 'expenses.view', 'transactions.view']);
    }

    /**
     * Get pending order count safely scoped to the active store.
     * Only executed if online store / ordering capability is enabled and permitted.
     */
    public function getPendingOrderCount(?Store $store): int
    {
        if (! $store) {
            return 0;
        }

        return Order::where('store_id', $store->id)
            ->where('status', 'pending_contact')
            ->count();
    }

    /**
     * Get the filtered navigation tree tailored to the current request, user, and store.
     * Plan §7:
     * - Platform routes never resolve store badges/counts or store menus.
     * - Store routes require active StoreContext and membership.
     * - Empty groups disappear.
     * - Badge/KPI resolvers execute ONLY after scope/channel/capability/permission checks.
     * - Active state matches current URL/route.
     */
    public function getFilteredNavigationTree(?User $user, ?Store $store, ?Request $request = null): array
    {
        $req = $request ?? request();
        $isPlatformScope = $this->isPlatformScope($req);
        $rawTree = $this->getRawNavigationTree();
        $filtered = [];

        if ($isPlatformScope) {
            // Platform Scope: Only platform items; never resolve store queries or menus
            foreach ($rawTree['platform'] as $item) {
                if (!empty($item['platform_owner_only']) && (! $user || ! $user->isPlatformOwner())) {
                    continue;
                }

                $isActive = $this->isNodeActive($item, $req);
                $url = !empty($item['route_name']) ? route($item['route_name']) : '#';

                $filtered[] = [
                    'key' => $item['key'],
                    'label' => __($item['translation_key']),
                    'translation_key' => $item['translation_key'],
                    'type' => 'link',
                    'scope' => 'platform',
                    'icon' => $item['icon'],
                    'route_name' => $item['route_name'] ?? null,
                    'url' => $url,
                    'active' => $isActive,
                    'badge' => null,
                ];
            }

            return $filtered;
        }

        // Store Scope: Requires active store and user membership
        if (! $store || ! $user) {
            return [];
        }

        // Platform owner can access any store; regular users require store membership
        if (! $user->isPlatformOwner() && ! $user->getStoreMembership($store->id)) {
            return [];
        }

        $storeRouteParams = ['store_slug' => $store->slug];

        foreach ($rawTree['store'] as $item) {
            if ($item['type'] === 'link') {
                if (! $this->isNodeAllowed($item, $user, $store)) {
                    continue;
                }

                $isActive = $this->isNodeActive($item, $req);
                $url = !empty($item['route_name']) ? route($item['route_name'], $storeRouteParams) : '#';
                $badge = null;
                if (!empty($item['badge_resolver']) && is_callable($item['badge_resolver'])) {
                    $badge = ($item['badge_resolver'])($store, $user);
                }

                $filtered[] = [
                    'key' => $item['key'],
                    'label' => __($item['translation_key']),
                    'translation_key' => $item['translation_key'],
                    'type' => 'link',
                    'scope' => 'store',
                    'icon' => $item['icon'],
                    'route_name' => $item['route_name'] ?? null,
                    'url' => $url,
                    'active' => $isActive,
                    'badge' => $badge,
                ];
            } elseif ($item['type'] === 'group') {
                // Group: Filter all children first
                $filteredChildren = [];
                $groupBadgeSum = 0;

                foreach ($item['children'] as $child) {
                    if (! $this->isNodeAllowed($child, $user, $store)) {
                        continue;
                    }

                    $isChildActive = $this->isNodeActive($child, $req);
                    $childUrl = !empty($child['route_name']) ? route($child['route_name'], $storeRouteParams) : '#';

                    $childBadge = null;
                    if (!empty($child['badge_resolver']) && is_callable($child['badge_resolver'])) {
                        $childBadge = ($child['badge_resolver'])($store, $user);
                        if (is_numeric($childBadge)) {
                            $groupBadgeSum += (int) $childBadge;
                        }
                    }

                    $filteredChildren[] = [
                        'key' => $child['key'],
                        'label' => __($child['translation_key']),
                        'translation_key' => $child['translation_key'],
                        'type' => 'link',
                        'scope' => 'store',
                        'icon' => $child['icon'],
                        'route_name' => $child['route_name'] ?? null,
                        'url' => $childUrl,
                        'active' => $isChildActive,
                        'badge' => $childBadge,
                    ];
                }

                // Plan §7 rule: Empty groups disappear
                if (empty($filteredChildren)) {
                    continue;
                }

                $isGroupActive = collect($filteredChildren)->contains('active', true);

                $filtered[] = [
                    'key' => $item['key'],
                    'label' => __($item['translation_key']),
                    'translation_key' => $item['translation_key'],
                    'type' => 'group',
                    'scope' => 'store',
                    'icon' => $item['icon'],
                    'icon_class' => $item['icon_class'] ?? '',
                    'active' => $isGroupActive,
                    'badge' => $groupBadgeSum > 0 ? $groupBadgeSum : null,
                    'children' => $filteredChildren,
                ];
            }
        }

        return $filtered;
    }

    /**
     * Check if a navigation node is allowed for the user and store context.
     */
    protected function isNodeAllowed(array $node, User $user, Store $store): bool
    {
        // 1. Channel check
        if (!empty($node['required_channel'])) {
            if (! $store->hasSalesChannel($node['required_channel'])) {
                return false;
            }
        }

        // 2. Capability check
        if (!empty($node['required_capability'])) {
            if (! $store->hasCapability($node['required_capability'])) {
                return false;
            }
        }

        // 3. Role check
        if (!empty($node['required_roles'])) {
            if (! $user->isPlatformOwner() && ! $user->hasStoreRole($store->id, $node['required_roles'])) {
                return false;
            }
        }

        // 4. Permission check
        if (!empty($node['required_permissions'])) {
            if (! $this->userHasPermission($user, $store, $node['required_permissions'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a navigation node matches the current request route or path pattern.
     */
    protected function isNodeActive(array $node, Request $request): bool
    {
        if (!empty($node['active_exclude_patterns'])) {
            foreach ($node['active_exclude_patterns'] as $excludePattern) {
                if (str_starts_with($excludePattern, 'route:')) {
                    if ($request->routeIs(substr($excludePattern, 6))) {
                        return false;
                    }
                } elseif (str_starts_with($excludePattern, 'path:')) {
                    if ($request->is(substr($excludePattern, 5))) {
                        return false;
                    }
                } elseif ($request->is($excludePattern)) {
                    return false;
                }
            }
        }

        if (!empty($node['active_patterns'])) {
            foreach ($node['active_patterns'] as $pattern) {
                if (str_starts_with($pattern, 'route:')) {
                    $routeName = substr($pattern, 6);
                    if ($request->routeIs($routeName)) {
                        return true;
                    }
                } elseif (str_starts_with($pattern, 'path:')) {
                    $pathPattern = substr($pattern, 5);
                    if ($request->is($pathPattern)) {
                        return true;
                    }
                } elseif ($request->is($pattern)) {
                    return true;
                }
            }
            return false;
        }

        if (!empty($node['route_name']) && $request->routeIs($node['route_name'])) {
            return true;
        }

        return false;
    }

    /**
     * Complete raw navigation metadata tree.
     */
    public function getRawNavigationTree(): array
    {
        return [
            'platform' => [
                [
                    'key' => 'platform_dashboard',
                    'translation_key' => 'messages.admin_dashboard',
                    'route_name' => 'admin.dashboard',
                    'active_patterns' => ['route:admin.dashboard', 'path:admin/dashboard*'],
                    'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13h8V3H3v10Zm0 8h8v-4H3v4Zm12 0h6V11h-6v10Zm0-14h6V3h-6v4Z"/></svg>',
                ],
                [
                    'key' => 'platform_stores',
                    'translation_key' => 'messages.store_management',
                    'route_name' => 'admin.stores.index',
                    'active_patterns' => ['path:admin/stores*'],
                    'platform_owner_only' => true,
                    'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21V8l9-5 9 5v13M9 21v-6h6v6M3 21h18"/></svg>',
                ],
                [
                    'key' => 'platform_theme_governance',
                    'translation_key' => 'messages.theme_governance',
                    'route_name' => 'admin.theme-governance.index',
                    'active_patterns' => ['path:admin/theme-governance*'],
                    'platform_owner_only' => true,
                    'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>',
                ],
            ],

            'store' => [
                // 1. Dashboard
                [
                    'key' => 'dashboard',
                    'type' => 'link',
                    'translation_key' => 'messages.admin_dashboard',
                    'route_name' => 'store.admin.dashboard',
                    'active_patterns' => ['path:store/*/admin/dashboard*'],
                    'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13h8V3H3v10Zm0 8h8v-4H3v4Zm12 0h6V11h-6v10Zm0-14h6V3h-6v4Z"/></svg>',
                ],

                // 2. POS Group
                [
                    'key' => 'pos',
                    'type' => 'group',
                    'translation_key' => 'messages.sidebar_pos_group',
                    'icon_class' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
                    'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M4 5h16v5H4V5Zm2 5v9h12v-9M7 12h2m-2 4h2m5-4h3m-3 4h3"/></svg>',
                    'children' => [
                        [
                            'key' => 'pos_sale',
                            'translation_key' => 'messages.pos_sale',
                            'route_name' => 'pos.index',
                            'required_channel' => Store::CHANNEL_POS,
                            'required_permissions' => ['pos_sales.view', 'pos_sales.edit'],
                            'active_patterns' => ['route:pos.index', 'path:store/*/pos', 'path:store/*/pos/'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5h16v14H4V5Zm0 6h16M7 15h4"/></svg>',
                        ],
                        [
                            'key' => 'pos_closing',
                            'translation_key' => 'messages.closing_title',
                            'route_name' => 'pos.closing.index',
                            'required_channel' => Store::CHANNEL_POS,
                            'required_capability' => Capability::OPERATIONS_CASHIER_SHIFTS,
                            'required_permissions' => ['pos_closing.view', 'pos_closing.edit'],
                            'active_patterns' => ['route:pos.closing.*', 'path:store/*/pos/closing*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9 2 2 4-4"/></svg>',
                        ],
                        [
                            'key' => 'pos_returns',
                            'translation_key' => 'messages.sidebar_sales_returns',
                            'route_name' => 'pos.returns.index',
                            'required_channel' => Store::CHANNEL_POS,
                            'required_permissions' => ['pos_returns.view', 'pos_returns.edit'],
                            'active_patterns' => ['route:pos.returns.*', 'path:store/*/pos/returns*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 010 8h-1"/></svg>',
                        ],
                        [
                            'key' => 'pos_buybacks',
                            'translation_key' => 'messages.sidebar_buy_back',
                            'route_name' => 'pos.buybacks.index',
                            'required_channel' => Store::CHANNEL_POS,
                            'required_permissions' => ['pos_buyback.view', 'pos_buyback.edit'],
                            'active_patterns' => ['route:pos.buybacks.*', 'path:store/*/pos/buy-back*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12a9 9 0 109-9m-9 9h9m0 0V3"/></svg>',
                        ],
                        [
                            'key' => 'pos_eload',
                            'translation_key' => 'messages.sidebar_eload',
                            'route_name' => 'store.admin.eload.index',
                            'required_channel' => Store::CHANNEL_POS,
                            'required_capability' => Capability::OPERATIONS_ELOAD,
                            'required_permissions' => ['pos_eload.view', 'pos_eload.edit'],
                            'active_patterns' => ['route:store.admin.eload.*', 'path:store/*/admin/eload*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2zM12 7v4m-2-2h4"/></svg>',
                        ],
                    ],
                ],

                // 3. Inventory Group
                [
                    'key' => 'inventory',
                    'type' => 'group',
                    'translation_key' => 'messages.sidebar_inventory',
                    'icon_class' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
                    'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 8.5 12 3 3 8.5m18 0-9 5.5m9-5.5V16l-9 5.5M3 8.5l9 5.5M3 8.5V16l9 5.5m0-7.5v7.5"/></svg>',
                    'children' => [
                        [
                            'key' => 'master_data',
                            'translation_key' => 'messages.master_data',
                            'route_name' => 'store.admin.products.master-data',
                            'required_permissions' => ['master_data.view', 'master_data.edit'],
                            'active_patterns' => ['route:store.admin.products.master-data*', 'path:store/*/admin/products/master-data*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l9 5-9 5-9-5 9-5Zm-9 10 9 5 9-5M3 17l9 5 9-5"/></svg>',
                        ],
                        [
                            'key' => 'products',
                            'translation_key' => 'messages.products',
                            'route_name' => 'store.admin.products.index',
                            'required_permissions' => ['products.view', 'products.edit'],
                            'active_patterns' => [
                                'route:store.admin.products.index',
                                'route:store.admin.products.create',
                                'route:store.admin.products.edit',
                                'route:store.admin.products.details',
                                'route:store.admin.products.export',
                                'path:store/*/admin/products',
                            ],
                            'active_exclude_patterns' => [
                                'route:store.admin.products.master-data*',
                                'path:store/*/admin/products/master-data*',
                                'route:store.admin.products.import*',
                                'path:store/*/admin/products/import*',
                            ],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 8h12l-1 12H7L6 8Zm3 0a3 3 0 0 1 6 0"/></svg>',
                        ],
                        [
                            'key' => 'barcode',
                            'translation_key' => 'messages.sidebar_barcode',
                            'route_name' => 'store.admin.barcode.index',
                            'required_capability' => Capability::CATALOG_BARCODE_PRINTING,
                            'required_permissions' => ['barcode.view', 'barcode.edit'],
                            'active_patterns' => ['path:store/*/admin/barcode*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-16v16M4 4v16m4-16v16m8-16v16"/></svg>',
                        ],
                        [
                            'key' => 'price_wizard',
                            'translation_key' => 'messages.sidebar_price_wizard',
                            'route_name' => 'store.admin.price_wizard.index',
                            'required_capability' => Capability::CATALOG_PRICE_WIZARD,
                            'required_permissions' => ['price_wizard.view', 'price_wizard.edit'],
                            'active_patterns' => ['path:store/*/admin/price-wizard*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.64 3.64-1.28-1.28a1.21 1.21 0 0 0-1.72 0L2.36 18.64a1.21 1.21 0 0 0 0 1.72l1.28 1.28a1.2 1.2 0 0 0 1.72 0L21.64 5.36a1.2 1.2 0 0 0 0-1.72Z"/><path d="m14 7 3 3"/><path d="M5 6v4"/><path d="M19 14v4"/><path d="M10 2v2"/><path d="M7 8H3"/><path d="M21 16h-4"/><path d="M11 3H9"/></svg>',
                        ],
                        [
                            'key' => 'warranty',
                            'translation_key' => 'messages.sidebar_warranty',
                            'route_name' => 'store.admin.warranty.index',
                            'required_capability' => Capability::SERVICE_WARRANTY_TRACKING,
                            'required_permissions' => ['warranty.view', 'warranty.edit'],
                            'active_patterns' => ['path:store/*/admin/warranty*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>',
                        ],
                        [
                            'key' => 'stock_ledger',
                            'translation_key' => 'messages.sidebar_stock_ledger',
                            'route_name' => 'store.admin.stock_ledger.index',
                            'required_permissions' => ['stock_ledger.view', 'stock_ledger.edit'],
                            'active_patterns' => ['path:store/*/admin/stock-ledger*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20v-6M6 20V10M18 20V4"/></svg>',
                        ],
                        [
                            'key' => 'stock_balance',
                            'translation_key' => 'messages.sidebar_stock_balance',
                            'route_name' => 'pos.reports.stock',
                            'required_permissions' => ['stock_balance.view', 'stock_balance.edit'],
                            'active_patterns' => ['path:store/*/pos/reports/stock*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7.5 12 3 4 7.5M20 7.5 12 12m8-4.5v9l-8 4.5M12 12 4 7.5M12 12v9M4 7.5v9l8 4.5"/></svg>',
                        ],
                        [
                            'key' => 'stock_count',
                            'translation_key' => 'messages.sidebar_stock_count',
                            'route_name' => 'store.admin.stock_count.index',
                            'required_capability' => Capability::INVENTORY_STOCK_AUDIT,
                            'required_permissions' => ['stock_count.view', 'stock_count.edit'],
                            'active_patterns' => ['path:store/*/admin/stock-count*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="m9 14 2 2 4-4"/></svg>',
                        ],
                        [
                            'key' => 'stock_adjustments',
                            'translation_key' => 'messages.sidebar_stock_adjustments',
                            'route_name' => 'pos.adjustments.index',
                            'required_permissions' => ['stock_adjustments.view', 'stock_adjustments.edit'],
                            'active_patterns' => ['path:store/*/pos/adjustments*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 0 5.4-5.4l-2.4 2.4-3-3 2.4-2.4Z"/></svg>',
                        ],
                        [
                            'key' => 'stock_reconciliation',
                            'translation_key' => 'messages.sidebar_stock_reconciliation',
                            'route_name' => 'pos.reconciliation.index',
                            'required_capability' => Capability::INVENTORY_STOCK_AUDIT,
                            'required_permissions' => ['stock_reconciliation.view', 'stock_reconciliation.edit'],
                            'active_patterns' => ['path:store/*/pos/reconciliation*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/></svg>',
                        ],
                        [
                            'key' => 'opening_stock',
                            'translation_key' => 'messages.sidebar_opening_stock',
                            'route_name' => 'pos.opening-stock.index',
                            'required_permissions' => ['opening_stock.view', 'opening_stock.edit'],
                            'active_patterns' => ['path:store/*/pos/opening-stock*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m8 11 4 4 4-4"/><path d="M21 8.5V17a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8.5"/></svg>',
                        ],
                        [
                            'key' => 'product_import',
                            'translation_key' => 'messages.product_import',
                            'route_name' => 'store.admin.products.import',
                            'required_permissions' => ['product_import.view', 'product_import.edit'],
                            'active_patterns' => ['path:store/*/admin/products/import*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v10m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>',
                        ],
                        [
                            'key' => 'import_history',
                            'translation_key' => 'messages.import_history',
                            'route_name' => 'store.admin.import-history.index',
                            'required_permissions' => ['product_import.view', 'import_history.view'],
                            'active_patterns' => ['path:store/*/admin/import-history*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v5l3 2M4 4v5h5M5.5 15a7 7 0 1 0 .8-7.8L4 9"/></svg>',
                        ],
                    ],
                ],

                // 4. Purchasing Group
                [
                    'key' => 'purchasing',
                    'type' => 'group',
                    'translation_key' => 'messages.sidebar_purchasing',
                    'icon_class' => 'bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-300',
                    'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M1 3h15v13H1zM16 8h4l3 3v5h-7zM5.5 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm11 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"/></svg>',
                    'children' => [
                        [
                            'key' => 'suppliers',
                            'translation_key' => 'messages.sidebar_suppliers',
                            'route_name' => 'store.admin.suppliers.index',
                            'required_permissions' => ['suppliers.view', 'suppliers.edit'],
                            'active_patterns' => ['path:store/*/admin/suppliers*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11a4 4 0 1 0-8 0m8 0a4 4 0 1 1-8 0m8 0c3 1 5 3 5 6v1H3v-1c0-3 2-5 5-6"/></svg>',
                        ],
                        [
                            'key' => 'purchases',
                            'translation_key' => 'messages.sidebar_purchases',
                            'route_name' => 'pos.purchases.index',
                            'required_permissions' => ['purchases.view', 'purchases.edit'],
                            'active_patterns' => ['route:pos.purchases.index', 'path:store/*/pos/purchases', 'path:store/*/admin/purchases*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>',
                        ],
                        [
                            'key' => 'purchase_returns',
                            'translation_key' => 'messages.sidebar_purchase_returns',
                            'route_name' => 'pos.purchases.returns',
                            'required_permissions' => ['purchase_returns.view', 'purchase_returns.edit'],
                            'active_patterns' => ['path:store/*/pos/purchases/returns*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 010 8h-1"/></svg>',
                        ],
                        [
                            'key' => 'payables',
                            'translation_key' => 'messages.sidebar_payables',
                            'route_name' => 'pos.purchases.payables',
                            'required_permissions' => ['payables.view', 'payables.edit'],
                            'active_patterns' => ['path:store/*/pos/purchases/payables*', 'path:store/*/admin/payables*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 7h20M2 7v10a2 2 0 002 2h16a2 2 0 002-2V7M10 11h4M10 15h2"/></svg>',
                        ],
                        [
                            'key' => 'transfers',
                            'translation_key' => 'messages.sidebar_transfers',
                            'route_name' => 'pos.transfers.index',
                            'required_capability' => Capability::INVENTORY_TRANSFERS,
                            'required_permissions' => ['transfers.view', 'transfers.edit'],
                            'active_patterns' => ['path:store/*/pos/transfers*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>',
                        ],
                        [
                            'key' => 'warehouses',
                            'translation_key' => 'messages.sidebar_warehouses',
                            'route_name' => 'store.admin.warehouses.index',
                            'required_capability' => Capability::OPERATIONS_WAREHOUSES,
                            'required_permissions' => ['warehouses.view', 'warehouses.edit'],
                            'active_patterns' => ['path:store/*/admin/warehouses*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>',
                        ],
                    ],
                ],

                // 5. Ecommerce Group
                [
                    'key' => 'ecommerce',
                    'type' => 'group',
                    'translation_key' => 'messages.sidebar_ecommerce',
                    'required_channel' => Store::CHANNEL_ONLINE_STORE,
                    'icon_class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
                    'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M3 10.5 5 5h14l2 5.5M4 10.5V19a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8.5M3 10.5h18M8 21v-6h8v6"/></svg>',
                    'children' => [
                        [
                            'key' => 'orders',
                            'translation_key' => 'messages.orders',
                            'route_name' => 'store.admin.orders.index',
                            'required_channel' => Store::CHANNEL_ONLINE_ORDERING,
                            'required_capability' => Capability::STOREFRONT_ONLINE_ORDERING,
                            'required_permissions' => ['ecommerce_orders.view', 'ecommerce_orders.edit'],
                            'active_patterns' => ['path:store/*/admin/orders*'],
                            // Plan §7 & §14: Lazy badge resolver ONLY executed when permitted and active
                            'badge_resolver' => function (Store $store) {
                                return $this->getPendingOrderCount($store);
                            },
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6M9 9h6M9 13h6M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/></svg>',
                        ],
                        [
                            'key' => 'web_products',
                            'translation_key' => 'messages.sidebar_web_products',
                            'route_name' => 'store.admin.web_products.index',
                            'required_channel' => Store::CHANNEL_ONLINE_STORE,
                            'required_permissions' => ['web_products.view', 'web_products.edit'],
                            'active_patterns' => ['path:store/*/admin/web-products*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
                        ],
                        [
                            'key' => 'promotions',
                            'translation_key' => 'messages.sidebar_promotions',
                            'route_name' => 'store.admin.promotions.index',
                            'required_channel' => Store::CHANNEL_ONLINE_STORE,
                            'required_permissions' => ['promotions.view', 'promotions.edit'],
                            'active_patterns' => ['path:store/*/admin/promotions*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
                        ],
                        [
                            'key' => 'reviews',
                            'translation_key' => 'messages.product_reviews',
                            'route_name' => 'store.admin.reviews.index',
                            'required_channel' => Store::CHANNEL_ONLINE_STORE,
                            'required_capability' => Capability::STOREFRONT_REVIEWS,
                            'required_permissions' => ['reviews.view', 'reviews.edit'],
                            'active_patterns' => ['path:store/*/admin/reviews*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1L12 16.9 6.6 19.8l1-6.1-4.4-4.3 6.1-.9L12 3Z"/></svg>',
                        ],
                        [
                            'key' => 'banners',
                            'translation_key' => 'messages.home_banners',
                            'route_name' => 'store.admin.banners.index',
                            'required_channel' => Store::CHANNEL_ONLINE_STORE,
                            'required_permissions' => ['banners.view', 'banners.edit'],
                            'active_patterns' => ['path:store/*/admin/banners*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 21V3m0 0h13l-2 4 2 4H5"/></svg>',
                        ],
                        [
                            'key' => 'blog',
                            'translation_key' => 'messages.blog_posts',
                            'route_name' => 'store.admin.blog.index',
                            'required_channel' => Store::CHANNEL_ONLINE_STORE,
                            'required_capability' => Capability::STOREFRONT_BLOG,
                            'required_permissions' => ['blog.view', 'blog.edit'],
                            'active_patterns' => ['path:store/*/admin/blog*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5h16v14H4V5Zm4 4h8M8 13h8M8 17h5"/></svg>',
                        ],
                        [
                            'key' => 'pages',
                            'translation_key' => 'messages.custom_pages',
                            'route_name' => 'store.admin.pages.index',
                            'required_channel' => Store::CHANNEL_ONLINE_STORE,
                            'required_permissions' => ['pages.view', 'pages.edit'],
                            'active_patterns' => ['path:store/*/admin/pages*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9z"/></svg>',
                        ],
                        [
                            'key' => 'navigation',
                            'translation_key' => 'messages.storefront_navigation',
                            'route_name' => 'store.admin.navigation.index',
                            'required_channel' => Store::CHANNEL_ONLINE_STORE,
                            'required_permissions' => ['navigation.view', 'navigation.edit'],
                            'active_patterns' => ['path:store/*/admin/navigation*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>',
                        ],
                        [
                            'key' => 'glass_finder',
                            'translation_key' => 'messages.glass_finder',
                            'route_name' => 'store.admin.glass-finder.index',
                            'required_channel' => Store::CHANNEL_ONLINE_STORE,
                            'required_capability' => Capability::STOREFRONT_GLASS_FINDER,
                            'required_permissions' => ['glass_finder.view', 'glass_finder.edit'],
                            'active_patterns' => ['path:store/*/admin/glass-finder*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.3-4.3M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z"/></svg>',
                        ],
                        [
                            'key' => 'push',
                            'translation_key' => 'messages.sidebar_web_push',
                            'route_name' => 'store.admin.push.index',
                            'required_channel' => Store::CHANNEL_ONLINE_STORE,
                            'required_permissions' => ['web_push.view', 'web_push.edit'],
                            'active_patterns' => ['path:store/*/admin/push', 'path:store/*/admin/push?*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/></svg>',
                        ],
                        [
                            'key' => 'push_history',
                            'translation_key' => 'messages.sidebar_push_history',
                            'route_name' => 'store.admin.push.history',
                            'required_channel' => Store::CHANNEL_ONLINE_STORE,
                            'required_permissions' => ['web_push.view', 'web_push.edit'],
                            'active_patterns' => ['path:store/*/admin/push/history*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>',
                        ],
                    ],
                ],

                // 6. Customers Group
                [
                    'key' => 'customers',
                    'type' => 'group',
                    'translation_key' => 'messages.sidebar_customers',
                    'icon_class' => 'bg-teal-100 text-teal-700 dark:bg-teal-500/15 dark:text-teal-300',
                    'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M16 11a4 4 0 1 0-8 0m8 0a4 4 0 1 1-8 0m8 0c3 1 5 3 5 6v1H3v-1c0-3 2-5 5-6"/></svg>',
                    'children' => [
                        [
                            'key' => 'customer_directory',
                            'translation_key' => 'messages.sidebar_customer_directory',
                            'route_name' => 'store.admin.customers.index',
                            'required_permissions' => ['customers.view', 'customers.edit'],
                            'active_patterns' => ['path:store/*/admin/customers*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11a4 4 0 1 0-8 0m8 0a4 4 0 1 1-8 0m8 0c3 1 5 3 5 6v1H3v-1c0-3 2-5 5-6"/></svg>',
                        ],
                        [
                            'key' => 'receivables',
                            'translation_key' => 'messages.sidebar_receivables',
                            'route_name' => 'store.admin.receivables.index',
                            'required_capability' => Capability::COMMERCE_CUSTOMER_DEBT,
                            'required_roles' => ['store_owner', 'store_manager'],
                            'required_permissions' => ['customer_debt.view', 'customer_debt.edit'],
                            'active_patterns' => ['path:store/*/admin/receivables*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
                        ],
                        [
                            'key' => 'wholesale',
                            'translation_key' => 'messages.wholesale_applications',
                            'route_name' => 'store.admin.wholesale.applications.index',
                            'required_capability' => Capability::COMMERCE_WHOLESALE,
                            'required_permissions' => ['wholesale.view', 'wholesale.edit'],
                            'active_patterns' => ['path:store/*/admin/wholesale*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6V5a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v1m-9 4h14M5 6h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z"/></svg>',
                        ],
                        [
                            'key' => 'membership',
                            'translation_key' => 'messages.sidebar_membership',
                            'route_name' => 'store.admin.membership.index',
                            'required_capability' => Capability::COMMERCE_LOYALTY,
                            'required_permissions' => ['loyalty.view', 'loyalty.edit'],
                            'active_patterns' => ['path:store/*/admin/membership*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg>',
                        ],
                    ],
                ],

                // 7. Service Group
                [
                    'key' => 'service',
                    'type' => 'group',
                    'translation_key' => 'messages.sidebar_service',
                    'required_capability' => Capability::SERVICE_REPAIR_JOBS,
                    'icon_class' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300',
                    'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 0 5.4-5.4l-2.4 2.4-3-3 2.4-2.4Z"/></svg>',
                    'children' => [
                        [
                            'key' => 'repairs',
                            'translation_key' => 'messages.sidebar_repair_center',
                            'route_name' => 'store.admin.repairs.index',
                            'required_permissions' => ['repair.view', 'repair.edit'],
                            'active_patterns' => ['path:store/*/admin/repairs*', 'path:store/*/admin/service-jobs*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 0 5.4-5.4l-2.4 2.4-3-3 2.4-2.4Z"/></svg>',
                        ],
                        [
                            'key' => 'spare_parts',
                            'translation_key' => 'messages.sidebar_spare_parts',
                            'route_name' => 'store.admin.spare_parts.index',
                            'required_capability' => Capability::SERVICE_SPARE_PARTS,
                            'required_permissions' => ['spare_parts.view', 'spare_parts.edit'],
                            'active_patterns' => ['path:store/*/admin/spare-parts*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
                        ],
                        [
                            'key' => 'service_settings',
                            'translation_key' => 'messages.sidebar_service_settings',
                            'route_name' => 'store.admin.service_settings.index',
                            'required_permissions' => ['service_settings.view', 'service_settings.edit'],
                            'active_patterns' => ['path:store/*/admin/service-settings*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
                        ],
                    ],
                ],

                // 8. Finance Group
                [
                    'key' => 'finance',
                    'type' => 'group',
                    'translation_key' => 'messages.sidebar_finance',
                    'required_roles' => ['store_owner', 'store_manager'],
                    'icon_class' => 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300',
                    'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg>',
                    'children' => [
                        [
                            'key' => 'profit_loss',
                            'translation_key' => 'messages.sidebar_profit_loss',
                            'route_name' => 'store.admin.profit_loss.index',
                            'required_permissions' => ['profit_loss.view', 'profit_loss.edit'],
                            'active_patterns' => ['path:store/*/admin/profit-loss*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>',
                        ],
                        [
                            'key' => 'expenses',
                            'translation_key' => 'messages.sidebar_expenses',
                            'route_name' => 'store.admin.expenses.index',
                            'required_permissions' => ['expenses.view', 'expenses.edit'],
                            'active_patterns' => ['path:store/*/admin/expenses*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
                        ],
                        [
                            'key' => 'expense_categories',
                            'translation_key' => 'messages.sidebar_expense_categories',
                            'route_name' => 'store.admin.expense_categories.index',
                            'required_permissions' => ['expense_categories.view', 'expense_categories.edit'],
                            'active_patterns' => ['path:store/*/admin/expense-categories*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h7"/><circle cx="17" cy="18" r="3"/><path d="M17 17v2m-1-1h2"/></svg>',
                        ],
                        [
                            'key' => 'transactions',
                            'translation_key' => 'messages.sidebar_transactions',
                            'route_name' => 'store.admin.transactions.index',
                            'required_permissions' => ['transactions.view', 'transactions.edit'],
                            'active_patterns' => ['path:store/*/admin/transactions*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>',
                        ],
                    ],
                ],

                // 9. Reports Group
                [
                    'key' => 'reports',
                    'type' => 'group',
                    'translation_key' => 'messages.sidebar_reports',
                    'icon_class' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-300',
                    'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M4 20V10m6 10V4m6 16v-7m4 7H2"/></svg>',
                    'children' => [
                        [
                            'key' => 'pos_reports_sales',
                            'translation_key' => 'messages.reports_sales',
                            'route_name' => 'pos.reports.sales',
                            'required_permissions' => ['reports_sales.view', 'reports_sales.edit'],
                            'active_patterns' => ['route:pos.reports.sales*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1Z"/><path d="M14 8H8"/><path d="M16 12H8"/><path d="M13 16H8"/></svg>',
                        ],
                        [
                            'key' => 'sales_analytics',
                            'translation_key' => 'messages.sidebar_sales_analytics',
                            'route_name' => 'store.admin.sales_analytics.index',
                            'required_permissions' => ['sales_analytics.view', 'sales_analytics.edit'],
                            'active_patterns' => ['route:store.admin.sales_analytics.*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>',
                        ],
                        [
                            'key' => 'pos_reports_cash',
                            'translation_key' => 'messages.reports_cash',
                            'route_name' => 'pos.reports.cash',
                            'required_permissions' => ['reports_cash.view', 'reports_cash.edit'],
                            'active_patterns' => ['route:pos.reports.cash*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>',
                        ],
                        [
                            'key' => 'inventory_valuation',
                            'translation_key' => 'messages.sidebar_inventory_valuation',
                            'route_name' => 'store.admin.inventory_valuation.index',
                            'required_permissions' => ['inventory_valuation.view', 'inventory_valuation.edit'],
                            'active_patterns' => ['route:store.admin.inventory_valuation.*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
                        ],
                        [
                            'key' => 'debt_aging',
                            'translation_key' => 'messages.sidebar_aging_report',
                            'route_name' => 'store.admin.debt_aging.index',
                            'required_permissions' => ['debt_aging.view', 'debt_aging.edit'],
                            'active_patterns' => ['route:store.admin.debt_aging.*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
                        ],
                        [
                            'key' => 'pos_services',
                            'translation_key' => 'messages.sidebar_report_services',
                            'route_name' => 'pos.reports.services',
                            'required_capability' => Capability::SERVICE_REPAIR_JOBS,
                            'required_permissions' => ['reports_services.view', 'reports_services.edit'],
                            'active_patterns' => ['route:pos.reports.services*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
                        ],
                    ],
                ],

                // 10. Security Group
                [
                    'key' => 'security',
                    'type' => 'group',
                    'translation_key' => 'messages.sidebar_security',
                    'icon_class' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300',
                    'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M12 3l8 3v6c0 4.5-3.2 7.8-8 9-4.8-1.2-8-4.5-8-9V6l8-3Z"/></svg>',
                    'children' => [
                        [
                            'key' => 'roles',
                            'translation_key' => 'messages.sidebar_roles',
                            'route_name' => 'store.admin.roles.index',
                            'required_permissions' => ['roles.view', 'roles.edit'],
                            'active_patterns' => ['route:store.admin.roles.*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 5 4 4"/><path d="M13 7 8.7 11.3a2 2 0 0 0-.58 1.23l-.8 4.7 4.7-.8a2 2 0 0 0 1.23-.58L17.5 11.5"/><circle cx="6" cy="6" r="3"/></svg>',
                        ],
                        [
                            'key' => 'users',
                            'translation_key' => 'messages.users',
                            'route_name' => 'store.admin.users.index',
                            'required_roles' => ['store_owner'],
                            'required_permissions' => ['users.view', 'users.edit'],
                            'active_patterns' => ['path:store/*/admin/users*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11a4 4 0 1 0-8 0m8 0a4 4 0 1 1-8 0m8 0c3 1 5 3 5 6v1H3v-1c0-3 2-5 5-6"/></svg>',
                        ],
                        [
                            'key' => 'audit_logs',
                            'translation_key' => 'messages.sidebar_audit_logs',
                            'route_name' => 'store.admin.audit-logs.index',
                            'required_roles' => ['store_owner'],
                            'required_permissions' => ['audit_logs.view'],
                            'active_patterns' => ['route:store.admin.audit-logs.*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M9 14h6"/><path d="M9 18h6"/><path d="M9 10h6"/></svg>',
                        ],
                    ],
                ],

                // 11. Maintenance Group
                [
                    'key' => 'maintenance',
                    'type' => 'group',
                    'translation_key' => 'messages.sidebar_maintenance',
                    'icon_class' => 'bg-slate-200 text-slate-600 dark:bg-slate-700/40 dark:text-slate-300',
                    'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v12c0 1.7 3.6 3 8 3s8-1.3 8-3V6M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/></svg>',
                    'children' => [
                        [
                            'key' => 'alerts',
                            'translation_key' => 'messages.sidebar_alerts',
                            'route_name' => 'store.admin.alerts.index',
                            'required_permissions' => ['alerts.view', 'alerts.edit'],
                            'active_patterns' => ['route:store.admin.alerts.*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
                        ],
                        [
                            'key' => 'database',
                            'translation_key' => 'messages.sidebar_database',
                            'route_name' => 'store.admin.database.index',
                            'required_roles' => ['store_owner'],
                            'required_permissions' => ['database.view', 'database.edit'],
                            'active_patterns' => ['route:store.admin.database.*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
                        ],
                        [
                            'key' => 'sync_manager',
                            'translation_key' => 'messages.sync_manager',
                            'route_name' => 'store.admin.sync.index',
                            'required_permissions' => ['sync_manager.view', 'sync_manager.edit'],
                            'active_patterns' => ['route:store.admin.sync.*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg>',
                        ],
                        [
                            'key' => 'backups',
                            'translation_key' => 'messages.backups',
                            'route_name' => 'store.admin.backups.index',
                            'required_roles' => ['store_owner', 'store_manager'],
                            'required_permissions' => ['backups.view', 'backups.edit'],
                            'active_patterns' => ['path:store/*/admin/backups*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M13 6l6 6-6 6M19 12H5"/></svg>',
                        ],
                        [
                            'key' => 'pilot_import',
                            'translation_key' => 'messages.pilot_import',
                            'route_name' => 'store.admin.pilot-import.index',
                            'required_roles' => ['store_owner'],
                            'required_permissions' => ['pilot_import.view', 'pilot_import.edit'],
                            'active_patterns' => ['path:store/*/admin/pilot-import*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h10M4 12h10M4 17h6M15 8l5 5m0 0-5 5m5-5h-9"/></svg>',
                        ],
                    ],
                ],

                // 12. Setup Group
                [
                    'key' => 'setup',
                    'type' => 'group',
                    'translation_key' => 'messages.sidebar_setup',
                    'required_roles' => ['store_owner', 'store_manager'],
                    'icon_class' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
                    'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm8.5 4a7 7 0 0 0-.1-1l2-1.5-2-3.5-2.4 1a8 8 0 0 0-1.7-1L16 3h-4l-.3 3a8 8 0 0 0-1.7 1l-2.4-1-2 3.5 2 1.5a7 7 0 0 0 0 2l-2 1.5 2 3.5 2.4-1a8 8 0 0 0 1.7 1l.3 3h4l.3-3a8 8 0 0 0 1.7-1l2.4 1 2-3.5-2-1.5c.1-.3.1-.7.1-1Z"/></svg>',
                    'children' => [
                        [
                            'key' => 'theme',
                            'translation_key' => 'messages.sidebar_theme_branding',
                            'route_name' => 'store.admin.theme.index',
                            'required_roles' => ['store_owner', 'store_manager'],
                            'required_permissions' => ['theme.view', 'theme.edit'],
                            'active_patterns' => ['route:store.admin.theme.*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.563-2.512 5.563-5.563C22 6.5 17.5 2 12 2Z"/></svg>',
                        ],
                        [
                            'key' => 'settings',
                            'translation_key' => 'messages.settings_storefront_settings',
                            'route_name' => 'store.admin.settings.edit',
                            'required_roles' => ['store_owner', 'store_manager'],
                            'required_permissions' => ['settings.view', 'settings.edit'],
                            'active_patterns' => ['route:store.admin.settings.*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M7 7v10M12 7v10M17 7v10M4 17h16"/></svg>',
                        ],
                        [
                            'key' => 'modules',
                            'translation_key' => 'messages.business_modules',
                            'route_name' => 'store.admin.modules.index',
                            'required_roles' => ['store_owner', 'store_manager'],
                            'required_permissions' => ['settings.view', 'settings.edit'],
                            'active_patterns' => ['route:store.admin.modules.*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
                        ],
                        [
                            'key' => 'channels',
                            'translation_key' => 'messages.sales_channels',
                            'route_name' => 'store.admin.channels.index',
                            'required_roles' => ['store_owner', 'store_manager'],
                            'required_permissions' => ['settings.view', 'settings.edit'],
                            'active_patterns' => ['route:store.admin.channels.*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
                        ],
                        [
                            'key' => 'branches',
                            'translation_key' => 'messages.sidebar_branches',
                            'route_name' => 'store.admin.branches.index',
                            'required_capability' => Capability::OPERATIONS_BRANCHES,
                            'required_roles' => ['store_owner', 'store_manager'],
                            'required_permissions' => ['settings.view', 'settings.update'],
                            'active_patterns' => ['path:store/*/admin/branches*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M3 7v1a3 3 0 0 0 6 0V7m0 1a3 3 0 0 0 6 0V7m0 1a3 3 0 0 0 6 0V7H3l2-4h14l2 4M5 21V10.85M19 21V10.85M9 21v-4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v4"/></svg>',
                        ],
                        [
                            'key' => 'printers',
                            'translation_key' => 'messages.sidebar_printers',
                            'route_name' => 'store.admin.printers.index',
                            'required_roles' => ['store_owner', 'store_manager'],
                            'required_permissions' => ['settings.view', 'settings.update'],
                            'active_patterns' => ['path:store/*/admin/printers*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>',
                        ],
                        [
                            'key' => 'vouchers',
                            'translation_key' => 'messages.sidebar_vouchers',
                            'route_name' => 'store.admin.vouchers.index',
                            'required_roles' => ['store_owner', 'store_manager'],
                            'required_permissions' => ['settings.view', 'settings.update'],
                            'active_patterns' => ['path:store/*/admin/vouchers*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 6v12"/></svg>',
                        ],
                        [
                            'key' => 'exchange_rates',
                            'translation_key' => 'messages.sidebar_exchange_rates',
                            'route_name' => 'store.admin.exchange_rates.index',
                            'required_roles' => ['store_owner', 'store_manager'],
                            'required_permissions' => ['settings.view', 'settings.update'],
                            'active_patterns' => ['path:store/*/admin/exchange-rates*'],
                            'icon' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
                        ],
                    ],
                ],
            ],
        ];
    }
}
