<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Store;
use App\Models\User;
use App\Services\StoreContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    /**
     * Map of action categories with metadata for filtering and presentation.
     */
    public const ACTION_CATEGORIES = [
        'pricing_sales' => [
            'label_en' => 'Pricing & Sales',
            'label_my' => 'စျေးနှုန်းနှင့် အရောင်းမှတ်တမ်း',
            'icon'     => '💰',
            'color'    => 'amber',
            'actions'  => [
                'bulk_price_updated',
                'product_price_changed',
                'pos_receipt_printed',
                'pos_receipt_reprinted',
                'pos_return_processed',
                'web_order_imported',
            ],
        ],
        'inventory' => [
            'label_en' => 'Inventory & Stock',
            'label_my' => 'စတော့နှင့် ကုန်ပစ္စည်းမှတ်တမ်း',
            'icon'     => '📦',
            'color'    => 'blue',
            'actions'  => [
                'inventory_adjustment_created',
                'inventory_adjustment_posted',
                'inventory_adjustment_voided',
                'opening_stock_created',
                'opening_stock_posted',
                'goods_receipt_posted',
                'reconciliation_posted',
                'product_created',
                'product_updated',
                'product_deleted',
                'stock_transferred',
            ],
        ],
        'financial' => [
            'label_en' => 'Cash & Finance',
            'label_my' => 'ငွေစာရင်းနှင့် နေ့ချုပ်မှတ်တမ်း',
            'icon'     => '💵',
            'color'    => 'emerald',
            'actions'  => [
                'daily_closing_created',
                'daily_closing_approved',
                'financial_transaction_created',
                'financial_transaction_approved',
                'customer_debt_collected',
                'supplier_debt_paid',
                'cash_withdrawn',
                'cash_deposited',
                'expense_created',
            ],
        ],
        'security' => [
            'label_en' => 'Security & Roles',
            'label_my' => 'လုံခြုံရေးနှင့် ဝန်ထမ်းခွင့်ပြုချက်',
            'icon'     => '🛡️',
            'color'    => 'rose',
            'actions'  => [
                'pos_pin_failed',
                'staff_role_created',
                'staff_role_updated',
                'staff_role_deleted',
                'staff_role_assigned',
                'user_created',
                'user_updated',
                'user_status_changed',
                'branch_created',
                'branch_updated',
                'branch_deleted',
                'backup_created',
                'backup_restored',
            ],
        ],
        'marketing_loyalty' => [
            'label_en' => 'Promotions & Loyalty',
            'label_my' => 'ပရိုမိုးရှင်းနှင့် အဖွဲ့ဝင်မှတ်တမ်း',
            'icon'     => '🏷️',
            'color'    => 'purple',
            'actions'  => [
                'promotion_created',
                'promotion_updated',
                'promotion_deleted',
                'membership_tier_created',
                'membership_tier_updated',
                'loyalty_points_adjusted',
                'voucher_template_created',
                'voucher_template_updated',
            ],
        ],
    ];

    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $storeRouteParams = ['store_slug' => $store->slug];
        $category = $request->query('category', 'all');
        $sort = $request->query('sort', 'newest');

        $query = $this->filteredQuery($request, $store);

        // Category filter
        if ($category !== 'all' && isset(self::ACTION_CATEGORIES[$category])) {
            $actions = self::ACTION_CATEGORIES[$category]['actions'];
            $query->whereIn('action', $actions);
        }

        // Sorting
        match ($sort) {
            'oldest' => $query->oldest('created_at'),
            default  => $query->latest('created_at'),
        };

        $perPage = request('per_page') === 'all' ? 1000 : (int) request('per_page', 25);
        $logs = $query->paginate($perPage)->withQueryString();
        $totalCount = $logs->total();

        // Summary KPI statistics (store-level, unfiltered)
        $baseQuery = AuditLog::where('store_id', $store->id);
        $pricingActions = self::ACTION_CATEGORIES['pricing_sales']['actions'];
        $inventoryActions = self::ACTION_CATEGORIES['inventory']['actions'];
        $financialActions = self::ACTION_CATEGORIES['financial']['actions'];
        $securityActions = self::ACTION_CATEGORIES['security']['actions'];

        $stats = [
            'total'          => (clone $baseQuery)->count(),
            'pricing_sales'  => (clone $baseQuery)->whereIn('action', $pricingActions)->count(),
            'inventory'      => (clone $baseQuery)->whereIn('action', $inventoryActions)->count(),
            'financial'      => (clone $baseQuery)->whereIn('action', $financialActions)->count(),
            'security'       => (clone $baseQuery)->whereIn('action', $securityActions)->count(),
            'today_events'   => (clone $baseQuery)->whereDate('created_at', today())->count(),
        ];

        // Unique actors for filter dropdown
        $actorIds = (clone $baseQuery)->whereNotNull('actor_id')->distinct()->pluck('actor_id');
        $actors = User::whereIn('id', $actorIds)->get(['id', 'name', 'phone']);

        $categories = self::ACTION_CATEGORIES;
        $exportUrl = route('store.admin.audit-logs.export', array_merge($storeRouteParams, request()->only(['search', 'category', 'action', 'actor_id', 'date_from', 'date_to', 'sort'])));

        return view('admin.audit_logs.index', compact(
            'store',
            'storeRouteParams',
            'logs',
            'totalCount',
            'stats',
            'category',
            'sort',
            'categories',
            'actors',
            'exportUrl'
        ));
    }

    public function show(Request $request, string $store_slug, AuditLog $log, StoreContext $context): View|JsonResponse
    {
        $store = $context->getStore();
        if (! $store || ($log->store_id !== null && $log->store_id !== $store->id)) {
            abort(403, 'Unauthorized store audit log access.');
        }

        $log->load('actor');
        $storeRouteParams = ['store_slug' => $store->slug];

        if ($request->wantsJson()) {
            return response()->json([
                'id'          => $log->id,
                'action'      => $log->action,
                'action_name' => self::humanizeAction($log->action),
                'actor'       => $log->actor ? [
                    'id'    => $log->actor->id,
                    'name'  => $log->actor->name,
                    'phone' => $log->actor->phone,
                ] : null,
                'entity_type' => $log->entity_type,
                'entity_id'   => $log->entity_id,
                'metadata'    => $log->metadata,
                'ip_address'  => $log->ip_address,
                'created_at'  => $log->created_at?->format('d M Y, h:i:s A'),
                'time_ago'    => $log->created_at?->diffForHumans(),
            ]);
        }

        return view('admin.audit_logs.show', compact('store', 'storeRouteParams', 'log'));
    }

    public function export(Request $request, StoreContext $context): StreamedResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $category = $request->query('category', 'all');
        $query = $this->filteredQuery($request, $store);

        if ($category !== 'all' && isset(self::ACTION_CATEGORIES[$category])) {
            $actions = self::ACTION_CATEGORIES[$category]['actions'];
            $query->whereIn('action', $actions);
        }

        $logs = $query->latest('created_at')->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="audit-trail-logs-' . $store->slug . '-' . now()->format('Ymd-His') . '.csv"',
        ];

        return response()->streamDownload(function () use ($logs, $store) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            fputcsv($stream, ['System Audit Trail Logs Report', $store->name]);
            fputcsv($stream, ['Export Date', now()->toFormattedDateString() . ' ' . now()->format('h:i A')]);
            fputcsv($stream, []);

            fputcsv($stream, [
                'Log ID', 'Date & Time', 'Actor Name', 'Actor Phone',
                'Action Code', 'Action Description', 'Category',
                'Target Entity Type', 'Target Entity ID', 'IP Address', 'Metadata Summary',
            ]);

            foreach ($logs as $log) {
                $categoryLabel = self::categoryOfAction($log->action);
                $metadataStr = is_array($log->metadata) ? json_encode($log->metadata, JSON_UNESCAPED_UNICODE) : '';

                fputcsv($stream, [
                    $log->id,
                    $log->created_at?->format('Y-m-d H:i:s') ?? '',
                    $log->actor?->name ?? 'System / Anonymous',
                    $log->actor?->phone ?? '',
                    $log->action,
                    self::humanizeAction($log->action),
                    $categoryLabel,
                    $log->entity_type ?? '',
                    $log->entity_id ?? '',
                    $log->ip_address ?? '',
                    $metadataStr,
                ]);
            }

            fclose($stream);
        }, 'audit-trail-logs-' . $store->slug . '-' . now()->format('Ymd-His') . '.csv', $headers);
    }

    private function filteredQuery(Request $request, Store $store): Builder
    {
        $query = AuditLog::where('store_id', $store->id)->with('actor');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', '%' . $search . '%')
                  ->orWhere('entity_type', 'like', '%' . $search . '%')
                  ->orWhere('ip_address', 'like', '%' . $search . '%')
                  ->orWhere('entity_id', 'like', '%' . $search . '%')
                  ->orWhereHas('actor', function ($uq) use ($search) {
                      $uq->where('name', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                  });
            });
        }

        if ($request->filled('actor_id')) {
            $query->where('actor_id', (int) $request->actor_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return $query;
    }

    public static function humanizeAction(string $action): string
    {
        $map = [
            'bulk_price_updated'             => 'စျေးနှုန်း အစုလိုက် ပြင်ဆင်ခြင်း (Bulk Price Updated)',
            'product_price_changed'          => 'ကုန်ပစ္စည်းစျေးနှုန်း ပြင်ဆင်ခြင်း (Product Price Changed)',
            'pos_receipt_printed'            => 'POS ဘောက်ချာ စာရွက်ထုတ်ခြင်း (Receipt Printed)',
            'pos_receipt_reprinted'          => 'POS ဘောက်ချာ ပြန်လည်ထုတ်ခြင်း (Receipt Reprinted)',
            'pos_return_processed'           => 'ပစ္စည်းပြန်သွင်း/ပြန်အပ်ခြင်း (POS Return Processed)',
            'web_order_imported'             => 'အွန်လိုင်းအော်ဒါ ထည့်သွင်းခြင်း (Web Order Imported)',
            'inventory_adjustment_created'   => 'စတော့ပြင်ဆင်လွှာ စတင်ပြုလုပ်ခြင်း (Stock Adj Created)',
            'inventory_adjustment_posted'    => 'စတော့ပြင်ဆင်မှု အတည်ပြုဖြတ်တောက်ခြင်း (Stock Adj Posted)',
            'inventory_adjustment_voided'    => 'စတော့ပြင်ဆင်လွှာ ပယ်ဖျက်ခြင်း (Stock Adj Voided)',
            'opening_stock_created'          => 'စတော့အဖွင့်စာရင်း စတင်ပြုလုပ်ခြင်း (Opening Stock Created)',
            'opening_stock_posted'           => 'စတော့အဖွင့်စာရင်း အတည်ပြုခြင်း (Opening Stock Posted)',
            'goods_receipt_posted'           => 'ကုန်ပစ္စည်းအဝင်စာရင်း အတည်ပြုခြင်း (Goods Receipt Posted)',
            'reconciliation_posted'          => 'စတော့ကိုက်ညီမှု စစ်ဆေးအတည်ပြုခြင်း (Stock Recon Posted)',
            'daily_closing_created'          => 'နေ့ချုပ်စာရင်း စတင်ပြုလုပ်ခြင်း (Daily Closing Created)',
            'daily_closing_approved'         => 'နေ့ချုပ်စာရင်း အတည်ပြုချုပ်ဆိုခြင်း (Daily Closing Approved)',
            'financial_transaction_created'  => 'ငွေစာရင်းလွှဲပြောင်း/ထုတ်ယူမှု ပြုလုပ်ခြင်း (Cash Tx Created)',
            'financial_transaction_approved' => 'ငွေစာရင်းလွှဲပြောင်း/ထုတ်ယူမှု အတည်ပြုခြင်း (Cash Tx Approved)',
            'customer_debt_collected'        => 'ဖောက်သည်ကြွေးကျန် ကောက်ခံသိမ်းဆည်းခြင်း (Debt Collected)',
            'supplier_debt_paid'             => 'ကုန်ပေးသွင်းသူထံ ကြွေးကျန်ပေးချေခြင်း (Supplier Debt Paid)',
            'pos_pin_failed'                 => 'POS မန်နေဂျာ PIN အမှားရိုက်ထည့်ခြင်း (PIN Attempt Failed)',
            'staff_role_created'             => 'ဝန်ထမ်းရာထူးနှင့် ခွင့်ပြုချက် အသစ်ဖွင့်ခြင်း (Role Created)',
            'staff_role_updated'             => 'ဝန်ထမ်းရာထူး ခွင့်ပြုချက်များ ပြင်ဆင်ခြင်း (Role Updated)',
            'staff_role_deleted'             => 'ဝန်ထမ်းရာထူး ဖျက်သိမ်းခြင်း (Role Deleted)',
            'staff_role_assigned'            => 'ဝန်ထမ်းထံ ရာထူးခွဲဝေသတ်မှတ်ခြင်း (Role Assigned)',
            'promotion_created'              => 'ပရိုမိုးရှင်း အစီအစဉ် အသစ်ဖွင့်ခြင်း (Promo Created)',
            'promotion_updated'              => 'ပရိုမိုးရှင်း အစီအစဉ် ပြင်ဆင်ခြင်း (Promo Updated)',
            'promotion_deleted'              => 'ပရိုမိုးရှင်း အစီအစဉ် ဖျက်သိမ်းခြင်း (Promo Deleted)',
            'membership_tier_created'        => 'အဖွဲ့ဝင်အဆင့် အသစ်သတ်မှတ်ခြင်း (Tier Created)',
            'loyalty_points_adjusted'        => 'အဖွဲ့ဝင် ရမှတ် ပြင်ဆင်ဖြတ်တောက်ခြင်း (Points Adjusted)',
            'voucher_template_created'       => 'ဘောက်ချာဒီဇိုင်း အသစ်သတ်မှတ်ခြင်း (Voucher Template Created)',
            'voucher_template_updated'       => 'ဘောက်ချာဒီဇိုင်း ပြင်ဆင်ခြင်း (Voucher Template Updated)',
            'branch_created'                 => 'ဆိုင်ခွဲအသစ် ဖွင့်လှစ်ခြင်း (Branch Created)',
            'branch_updated'                 => 'ဆိုင်ခွဲအချက်အလက် ပြင်ဆင်ခြင်း (Branch Updated)',
            'branch_deleted'                 => 'ဆိုင်ခွဲ ဖျက်သိမ်းခြင်း (Branch Deleted)',
            'backup_created'                 => 'စနစ်ဒေတာဘေ့စ် Backup ပြုလုပ်ခြင်း (Backup Created)',
        ];

        return $map[$action] ?? ucwords(str_replace('_', ' ', $action));
    }

    public static function categoryOfAction(string $action): string
    {
        foreach (self::ACTION_CATEGORIES as $key => $meta) {
            if (in_array($action, $meta['actions'], true)) {
                return $meta['label_en'] . ' (' . $meta['label_my'] . ')';
            }
        }

        return 'General Activity';
    }
}
