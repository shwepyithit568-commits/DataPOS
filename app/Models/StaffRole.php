<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class StaffRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'description',
        'color',
        'permissions',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_system'   => 'boolean',
        'is_active'   => 'boolean',
    ];

    public const PERMISSION_GROUPS = [
        'pos' => [
            'label' => 'POS & Counter Sales (အရောင်းကောင်တာ လုပ်ဆောင်ချက်များ)',
            'permissions' => [
                'pos.sell'               => 'Create New Sales & Invoices (ဘောက်ချာဖွင့် ရောင်းချခွင့်)',
                'pos.discount_override'  => 'Override Discount / Special Discount (သတ်မှတ်ထက် လျှော့စျေး ပေးခွင့်)',
                'pos.price_edit'         => 'Change Unit Price at Counter (ကောင်တာတွင် စျေးနှုန်း ပြင်ဆင်ခွင့်)',
                'pos.allow_credit'       => 'Sell on Credit / Debt (အကြွေးဖြင့် ရောင်းချခွင့်)',
                'pos.void_sale'          => 'Void / Cancel Invoice (ဘောက်ချာ ဖျက်သိမ်းခွင့်)',
                'pos.open_cash_drawer'   => 'Manual Open Cash Drawer (အံဆွဲ အလွတ်ဖွင့်ခွင့်)',
                'pos.returns'            => 'Process Customer Returns & Refunds (ပစ္စည်း ပြန်သွင်း/ငွေပြန်အမ်းခွင့်)',
            ]
        ],
        'inventory' => [
            'label' => 'Inventory & Warehousing (စတော့နှင့် ဂိုဒေါင် စီမံခန့်ခွဲမှု)',
            'permissions' => [
                'inventory.view'         => 'View Stock Quantities (စတော့လက်ကျန် ကြည့်ရှုခွင့်)',
                'inventory.cost_view'    => 'View Purchase Costs (ဝယ်ရင်းစျေး မြင်တွေ့ခွင့်)',
                'inventory.adjust'       => 'Perform Stock Adjustments (စတော့ အတိုး/အလျော့ ပြုလုပ်ခွင့်)',
                'inventory.transfer'     => 'Stock Transfers between Branches (ဆိုင်ခွဲများသို့ စတော့လွှဲပြောင်းခွင့်)',
                'inventory.audit'        => 'Stock Count Auditing & Reconciliation (စတော့ စစ်ဆေး/အတည်ပြုခွင့်)',
                'inventory.po_manage'    => 'Purchase Orders & Supplier Invoicing (ကုန်ဝယ်အော်ဒါ စီမံခွင့်)',
            ]
        ],
        'finance_reports' => [
            'label' => 'Finance & Business Reports (ငွေစာရင်းနှင့် အစီရင်ခံစာများ)',
            'permissions' => [
                'reports.sales'          => 'View Daily / Period Sales Reports (အရောင်း အစီရင်ခံစာ ကြည့်ခွင့်)',
                'reports.profit_loss'    => 'View Profit & Loss Statements (အရှုံး/အမြတ် စာရင်း ကြည့်ခွင့်)',
                'reports.valuation'      => 'View Inventory Asset Valuation (စတော့တန်ဖိုး စာရင်း ကြည့်ခွင့်)',
                'reports.debt_aging'     => 'View Customer Debt Aging (အကြွေးသက်တမ်း စာရင်း ကြည့်ခွင့်)',
                'expenses.manage'        => 'Record & Approve Shop Expenses (ဆိုင်သုံးစရိတ် စာရင်းသွင်း/ပြင်ခွင့်)',
                'transactions.manage'    => 'Cash & Bank Accounts Management (ငွေစာရင်းနှင့် ဘဏ်အကောင့် စီမံခွင့်)',
            ]
        ],
        'service_repair' => [
            'label' => 'Service & Repair Jobs (ပစ္စည်းပြင်ဆင်ရေး စီမံခန့်ခွဲမှု)',
            'permissions' => [
                'services.create'        => 'Accept New Service & Repair Jobs (ပြင်ဆင်ရန် ပစ္စည်းလက်ခံခွင့်)',
                'services.diagnose'      => 'Update Diagnosis, Parts & Labor Cost (စစ်ဆေးချက်နှင့် လက်ခ ပြင်ခွင့်)',
                'services.deliver'       => 'Complete & Deliver Repaired Devices (ပစ္စည်း ပြန်လည်အပ်နှံခွင့်)',
            ]
        ],
        'settings_security' => [
            'label' => 'Store Setup & Staff Control (ဆိုင်စနစ်နှင့် ဝန်ထမ်း ထိန်းချုပ်မှု)',
            'permissions' => [
                'staff.manage'           => 'Manage Staff Members & Assign Roles (ဝန်ထမ်းစာရင်းနှင့် ရာထူး သတ်မှတ်ခွင့်)',
                'roles.manage'           => 'Create & Customize Staff Roles (ရာထူး အခွင့်အရေးများ စိတ်ကြိုက်ပြင်ခွင့်)',
                'store.settings'         => 'Modify Store Info & Vouchers (ဆိုင် အချက်အလက်နှင့် ဆက်တင်များ ပြင်ခွင့်)',
                'audit.view'             => 'View Security Audit Logs (လုံခြုံရေး မှတ်တမ်းများ စစ်ဆေးခွင့်)',
            ]
        ],
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Check if this role contains a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        $perms = $this->permissions ?? [];
        return in_array('*', $perms, true) || in_array($permission, $perms, true);
    }

    /**
     * Count of active staff members assigned to this role in the store.
     */
    public function getStaffCountAttribute(): int
    {
        return DB::table('store_user')
            ->where('store_id', $this->store_id)
            ->where('staff_role_id', $this->id)
            ->where('status', 'active')
            ->count();
    }

    /**
     * Seed default system roles for a store if none exist.
     */
    public static function bootstrapDefaultRoles(Store $store): void
    {
        if (static::where('store_id', $store->id)->exists()) {
            return;
        }

        $allPerms = [];
        foreach (static::PERMISSION_GROUPS as $group) {
            foreach (array_keys($group['permissions']) as $key) {
                $allPerms[] = $key;
            }
        }

        // 1. Store Manager (All Permissions)
        static::create([
            'store_id'    => $store->id,
            'name'        => 'Store Manager',
            'slug'        => 'store_manager',
            'description' => 'Full administrative access to all POS, Inventory, Financial and Settings features.',
            'color'       => '#0284c7', // Sky blue
            'permissions' => ['*'],
            'is_system'   => true,
            'is_active'   => true,
        ]);

        // 2. Senior Cashier / Counter Sales
        static::create([
            'store_id'    => $store->id,
            'name'        => 'Cashier / Sales Staff',
            'slug'        => 'cashier',
            'description' => 'Daily counter sales, open cash drawer, customer payments, and receipts.',
            'color'       => '#10b981', // Emerald
            'permissions' => [
                'pos.sell',
                'pos.discount_override',
                'pos.allow_credit',
                'pos.open_cash_drawer',
                'pos.returns',
                'inventory.view',
                'services.create',
                'reports.sales',
            ],
            'is_system'   => true,
            'is_active'   => true,
        ]);

        // 3. Accountant / Financial Auditor
        static::create([
            'store_id'    => $store->id,
            'name'        => 'Accountant / စာရင်းကိုင်',
            'slug'        => 'accountant',
            'description' => 'Manages Profit & Loss, Expenses, Banking transactions, Debt Aging and Inventory Valuations.',
            'color'       => '#8b5cf6', // Violet
            'permissions' => [
                'inventory.view',
                'inventory.cost_view',
                'inventory.audit',
                'inventory.po_manage',
                'reports.sales',
                'reports.profit_loss',
                'reports.valuation',
                'reports.debt_aging',
                'expenses.manage',
                'transactions.manage',
            ],
            'is_system'   => true,
            'is_active'   => true,
        ]);

        // 4. Service Technician / Repair Engineer
        static::create([
            'store_id'    => $store->id,
            'name'        => 'Technician / ဝန်ဆောင်မှု ပညာရှင်',
            'slug'        => 'technician',
            'description' => 'Device inspection, spare parts estimation, diagnosis, repair job status updates.',
            'color'       => '#f59e0b', // Amber
            'permissions' => [
                'services.create',
                'services.diagnose',
                'services.deliver',
                'inventory.view',
            ],
            'is_system'   => true,
            'is_active'   => true,
        ]);

        // 5. Stock Keeper / Warehouse Supervisor
        static::create([
            'store_id'    => $store->id,
            'name'        => 'Stock Keeper / စတော့မှူး',
            'slug'        => 'stock_keeper',
            'description' => 'Receiving shipments, warehouse stock adjustments, branch transfers, and stock counts.',
            'color'       => '#06b6d4', // Cyan
            'permissions' => [
                'inventory.view',
                'inventory.cost_view',
                'inventory.adjust',
                'inventory.transfer',
                'inventory.audit',
                'inventory.po_manage',
            ],
            'is_system'   => true,
            'is_active'   => true,
        ]);
    }
}
