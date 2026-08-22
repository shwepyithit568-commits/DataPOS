<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StoreContext;
use Illuminate\Http\Request;

/**
 * Single placeholder page for modules that exist on the roadmap but are not
 * built yet — the sidebar acts as the roadmap map (SoT §13, Phase 4).
 *
 * One route + one blade serves every placeholder; the module registry below
 * is the single list of planned modules with their roadmap phase. Each module
 * becomes a real controller + views when it ships.
 */
class ComingSoonController extends Controller
{
    /** @var array<string, array{label:string, phase:string}> slug → lang label key + roadmap phase */
    protected const MODULES = [
        'eload'               => ['sidebar_eload', 'Phase 4'],
        'stock-count'         => ['sidebar_stock_count', 'Phase 4'],
        'stock-ledger'        => ['sidebar_stock_ledger', 'Phase 4'],
        'price-wizard'        => ['sidebar_price_wizard', 'Phase 4'],
        'barcode'             => ['sidebar_barcode', 'Phase 4'],
        'warranty'            => ['sidebar_warranty', 'Phase 4'],
        'suppliers'           => ['sidebar_suppliers', 'Phase 4'],
        'transfers'           => ['sidebar_transfers', 'Phase 4'],
        'warehouses'          => ['sidebar_warehouses', 'Phase 4'],
        'buy-back'            => ['sidebar_buy_back', 'Phase 4'],
        'promotions'          => ['sidebar_promotions', 'Phase 4'],
        'web-products'        => ['sidebar_web_products', 'Phase 3'],
        'membership'          => ['sidebar_membership', 'Phase 4'],
        'service-jobs'        => ['sidebar_service_jobs', 'Phase 4'],
        'spare-parts'         => ['sidebar_spare_parts', 'Phase 4'],
        'service-settings'    => ['sidebar_service_settings', 'Phase 4'],
        'expenses'            => ['sidebar_expenses', 'Phase 4'],
        'receivables'         => ['sidebar_receivables', 'Phase 4'],
        'payables'            => ['sidebar_payables', 'Phase 4'],
        'profit-loss'         => ['sidebar_profit_loss', 'Phase 4'],
        'transactions'        => ['sidebar_transactions', 'Phase 4'],
        'expense-categories'  => ['sidebar_expense_categories', 'Phase 4'],
        'sales-analytics'     => ['sidebar_sales_analytics', 'Phase 4'],
        'inventory-valuation' => ['sidebar_inventory_valuation', 'Phase 4'],
        'aging-report'        => ['sidebar_aging_report', 'Phase 4'],
        'branches'            => ['sidebar_branches', 'Phase 4'],
        'printers'            => ['sidebar_printers', 'Phase 4'],
        'vouchers'            => ['sidebar_vouchers', 'Phase 4'],
        'exchange-rates'      => ['sidebar_exchange_rates', 'Phase 4'],
        'roles'               => ['sidebar_roles', 'Phase 4'],
        'audit-logs'          => ['sidebar_audit_logs', 'Phase 4'],
        'database'            => ['sidebar_database', 'Phase 4'],
        'alerts'              => ['sidebar_alerts', 'Phase 4'],
    ];

    public static function modules(): array
    {
        return self::MODULES;
    }

    /**
     * The module slug is read from the route explicitly: injected service
     * parameters before a scalar route parameter shift Laravel 11/12's
     * positional resolution (same class of bug as the earlier POS resume/void
     * fix — see CHANGELOG 2026-08-17), so the parameter is never trusted.
     */
    public function index(Request $request, StoreContext $context)
    {
        $module = (string) $request->route('module');

        if (! isset(self::MODULES[$module])) {
            abort(404);
        }

        $store = $context->getStore();
        [$labelKey, $phase] = self::MODULES[$module];

        return view('admin.coming_soon', [
            'store' => $store,
            'module' => $module,
            'moduleLabel' => __("messages.{$labelKey}"),
            'phase' => $phase,
            // Full module registry so the shared view can render the roadmap
            // grid (which modules are planned and in which phase).
            'modules' => self::MODULES,
        ]);
    }
}
