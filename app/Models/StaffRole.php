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
            'label' => 'POS & Counter Sales (အရောင်းကောင်တာ)',
            'icon'  => '🛒',
            'color' => '#0284c7',
            'modules' => [
                'pos_sales' => [
                    'name' => 'POS Counter Sales (အရောင်းကောင်တာ)',
                    'desc' => 'Counter checkout, discounts, price overrides & receipts',
                    'permissions' => [
                        'view'   => 'pos_sales.view',
                        'create' => 'pos_sales.create',
                        'update' => 'pos_sales.update',
                        'delete' => 'pos_sales.delete',
                        'refund' => 'pos_sales.refund',
                    ]
                ],
                'pos_closing' => [
                    'name' => 'Daily Shift Closing (နေ့ချုပ်စာရင်း)',
                    'desc' => 'Shift handover & cash register closing',
                    'permissions' => [
                        'view'   => 'pos_closing.view',
                        'create' => 'pos_closing.create',
                        'update' => 'pos_closing.update',
                        'delete' => 'pos_closing.delete',
                    ]
                ],
                'pos_returns' => [
                    'name' => 'Sales Returns & Refunds (အရောင်းပြန်သွင်း/ငွေပြန်အမ်း)',
                    'desc' => 'Customer product returns & invoice refunds',
                    'permissions' => [
                        'view'   => 'pos_returns.view',
                        'create' => 'pos_returns.create',
                        'update' => 'pos_returns.update',
                        'delete' => 'pos_returns.delete',
                    ]
                ],
                'pos_buyback' => [
                    'name' => 'Buy Back & Trade-in (အဝယ်တော်/Trade-in)',
                    'desc' => 'Secondhand device trade-in & buy back appraisal',
                    'permissions' => [
                        'view'   => 'pos_buyback.view',
                        'create' => 'pos_buyback.create',
                        'update' => 'pos_buyback.update',
                        'delete' => 'pos_buyback.delete',
                    ]
                ],
                'pos_eload' => [
                    'name' => 'E-Load & Mobile Top-up (ဖုန်းဘေလ်ဖြည့်သွင်းမှု)',
                    'desc' => 'Carrier top-up transactions & PIN balances',
                    'permissions' => [
                        'view'   => 'pos_eload.view',
                        'create' => 'pos_eload.create',
                        'update' => 'pos_eload.update',
                        'delete' => 'pos_eload.delete',
                    ]
                ],
            ]
        ],
        'inventory' => [
            'label' => 'Inventory & Products (စတော့နှင့် ကုန်ပစ္စည်း)',
            'icon'  => '📦',
            'color' => '#f59e0b',
            'modules' => [
                'master_data' => [
                    'name' => 'Master Data (အမျိုးအစား/အမှတ်တံဆိပ်/ယူနစ်)',
                    'desc' => 'Categories, brands, units, and variant presets',
                    'permissions' => [
                        'view'   => 'master_data.view',
                        'create' => 'master_data.create',
                        'update' => 'master_data.update',
                        'delete' => 'master_data.delete',
                    ]
                ],
                'products' => [
                    'name' => 'Products Catalog (ကုန်ပစ္စည်းစာရင်း)',
                    'desc' => 'Item catalog, SKU, barcodes, retail & wholesale prices',
                    'permissions' => [
                        'view'   => 'products.view',
                        'create' => 'products.create',
                        'update' => 'products.update',
                        'delete' => 'products.delete',
                        'export' => 'products.export',
                    ]
                ],
                'barcode' => [
                    'name' => 'Barcode Printing (ဘားကုဒ် ထုတ်ယူခြင်း)',
                    'desc' => 'Generate and print thermal barcode labels',
                    'permissions' => [
                        'view'   => 'barcode.view',
                        'create' => 'barcode.create',
                        'update' => 'barcode.update',
                        'delete' => 'barcode.delete',
                    ]
                ],
                'price_wizard' => [
                    'name' => 'Price Wizard (စျေးနှုန်း တွက်ချက်မှု)',
                    'desc' => 'Bulk profit margin adjustments & retail markup',
                    'permissions' => [
                        'view'   => 'price_wizard.view',
                        'create' => 'price_wizard.create',
                        'update' => 'price_wizard.update',
                        'delete' => 'price_wizard.delete',
                    ]
                ],
                'warranty' => [
                    'name' => 'Warranty Management (အာမခံ စီမံခန့်ခွဲမှု)',
                    'desc' => 'Warranty policy setup, serial lookups & claims',
                    'permissions' => [
                        'view'   => 'warranty.view',
                        'create' => 'warranty.create',
                        'update' => 'warranty.update',
                        'delete' => 'warranty.delete',
                    ]
                ],
                'stock_ledger' => [
                    'name' => 'Stock Ledger & Bin Card (စတော့ လှုပ်ရှားမှု မှတ်တမ်း)',
                    'desc' => 'Detailed stock movement audit trail and historical ledger',
                    'permissions' => [
                        'view'   => 'stock_ledger.view',
                        'export' => 'stock_ledger.export',
                    ]
                ],
                'stock_balance' => [
                    'name' => 'Stock Balance (စတော့လက်ကျန် စာရင်း)',
                    'desc' => 'Real-time inventory levels by warehouse & branch',
                    'permissions' => [
                        'view'   => 'stock_balance.view',
                        'export' => 'stock_balance.export',
                    ]
                ],
                'stock_count' => [
                    'name' => 'Stock Count & Audit (စတော့ ရေတွက်စစ်ဆေးခြင်း)',
                    'desc' => 'Physical stock count worksheets and discrepancy check',
                    'permissions' => [
                        'view'   => 'stock_count.view',
                        'create' => 'stock_count.create',
                        'update' => 'stock_count.update',
                        'delete' => 'stock_count.delete',
                    ]
                ],
                'stock_adjustments' => [
                    'name' => 'Stock Adjustments (စတော့ ချိန်ညှိမှု)',
                    'desc' => 'Increase / decrease manual stock adjustments',
                    'permissions' => [
                        'view'   => 'stock_adjustments.view',
                        'create' => 'stock_adjustments.create',
                        'update' => 'stock_adjustments.update',
                        'delete' => 'stock_adjustments.delete',
                    ]
                ],
                'stock_reconciliation' => [
                    'name' => 'Stock Reconciliation (စတော့ ကွာဟချက် စစ်ဆေးမှု)',
                    'desc' => 'Reconcile counted vs system stock variance',
                    'permissions' => [
                        'view'   => 'stock_reconciliation.view',
                        'create' => 'stock_reconciliation.create',
                        'update' => 'stock_reconciliation.update',
                        'delete' => 'stock_reconciliation.delete',
                    ]
                ],
                'opening_stock' => [
                    'name' => 'Opening Stock (စတော့ အဖွင့်စာရင်း)',
                    'desc' => 'Initial inventory balance setup upon shop onboarding',
                    'permissions' => [
                        'view'   => 'opening_stock.view',
                        'create' => 'opening_stock.create',
                        'update' => 'opening_stock.update',
                        'delete' => 'opening_stock.delete',
                    ]
                ],
                'product_import' => [
                    'name' => 'Product Batch Import (ကုန်ပစ္စည်း တင်သွင်းခြင်း)',
                    'desc' => 'Excel & CSV batch product import and mapping',
                    'permissions' => [
                        'view'   => 'product_import.view',
                        'create' => 'product_import.create',
                        'delete' => 'product_import.delete',
                    ]
                ],
            ]
        ],
        'purchasing' => [
            'label' => 'Purchasing & Warehouses (ဝယ်ယူမှုနှင့် ဂိုဒေါင်)',
            'icon'  => '🚚',
            'color' => '#ea580c',
            'modules' => [
                'suppliers' => [
                    'name' => 'Suppliers Directory (ကုန်သွင်းသူများ)',
                    'desc' => 'Vendor contact info, payment terms & catalog',
                    'permissions' => [
                        'view'   => 'suppliers.view',
                        'create' => 'suppliers.create',
                        'update' => 'suppliers.update',
                        'delete' => 'suppliers.delete',
                    ]
                ],
                'purchases' => [
                    'name' => 'Purchase Orders & Bills (ကုန်ဝယ်ယူမှုများ)',
                    'desc' => 'Purchase orders, bills, receive goods & cost tracking',
                    'permissions' => [
                        'view'   => 'purchases.view',
                        'create' => 'purchases.create',
                        'update' => 'purchases.update',
                        'delete' => 'purchases.delete',
                    ]
                ],
                'purchase_returns' => [
                    'name' => 'Purchase Returns (ဝယ်ထားသော ပစ္စည်း ပြန်သွင်း)',
                    'desc' => 'Return damaged/excess goods to supplier',
                    'permissions' => [
                        'view'   => 'purchase_returns.view',
                        'create' => 'purchase_returns.create',
                        'update' => 'purchase_returns.update',
                        'delete' => 'purchase_returns.delete',
                    ]
                ],
                'payables' => [
                    'name' => 'Accounts Payable (ကုန်သည် ပေးရန်ရှိ စာရင်း)',
                    'desc' => 'Supplier payables, credit balance & payment vouchers',
                    'permissions' => [
                        'view'   => 'payables.view',
                        'create' => 'payables.create',
                        'update' => 'payables.update',
                        'delete' => 'payables.delete',
                    ]
                ],
                'transfers' => [
                    'name' => 'Stock Transfers (ဆိုင်ခွဲ/ဂိုဒေါင် လွှဲပြောင်းမှု)',
                    'desc' => 'Inter-branch and warehouse stock transfer orders',
                    'permissions' => [
                        'view'   => 'transfers.view',
                        'create' => 'transfers.create',
                        'update' => 'transfers.update',
                        'delete' => 'transfers.delete',
                    ]
                ],
                'warehouses' => [
                    'name' => 'Warehouses Management (ဂိုဒေါင်များ စီမံခန့်ခွဲမှု)',
                    'desc' => 'Multiple warehouse locations, racks & bin setup',
                    'permissions' => [
                        'view'   => 'warehouses.view',
                        'create' => 'warehouses.create',
                        'update' => 'warehouses.update',
                        'delete' => 'warehouses.delete',
                    ]
                ],
            ]
        ],
        'ecommerce' => [
            'label' => 'E-Commerce & Online Store (အွန်လိုင်းဆိုင်)',
            'icon'  => '🌐',
            'color' => '#10b981',
            'modules' => [
                'orders' => [
                    'name' => 'Online Store Orders (အွန်လိုင်း အော်ဒါများ)',
                    'desc' => 'Order fulfillment, packing, delivery & payments',
                    'permissions' => [
                        'view'   => 'ecommerce_orders.view',
                        'create' => 'ecommerce_orders.create',
                        'update' => 'ecommerce_orders.update',
                        'delete' => 'ecommerce_orders.delete',
                    ]
                ],
                'web_products' => [
                    'name' => 'Web Store Products (ဝဘ်ဆိုင် ကုန်ပစ္စည်းများ)',
                    'desc' => 'Online storefront visibility, descriptions & SEO',
                    'permissions' => [
                        'view'   => 'web_products.view',
                        'create' => 'web_products.create',
                        'update' => 'web_products.update',
                        'delete' => 'web_products.delete',
                    ]
                ],
                'promotions' => [
                    'name' => 'Promotions & Discounts (ပရိုမိုးရှင်းနှင့် လျှော့စျေး)',
                    'desc' => 'Coupon codes, buy-x-get-y & flash sales',
                    'permissions' => [
                        'view'   => 'promotions.view',
                        'create' => 'promotions.create',
                        'update' => 'promotions.update',
                        'delete' => 'promotions.delete',
                    ]
                ],
                'reviews' => [
                    'name' => 'Product Reviews (ကုန်ပစ္စည်း မှတ်ချက်များ)',
                    'desc' => 'Moderate, approve or remove customer product ratings',
                    'permissions' => [
                        'view'   => 'reviews.view',
                        'update' => 'reviews.update',
                        'delete' => 'reviews.delete',
                    ]
                ],
                'banners' => [
                    'name' => 'Home Banners & Sliders (ဝဘ်ဆိုက် ဘန်နာများ)',
                    'desc' => 'Storefront advertising banners & slider artwork',
                    'permissions' => [
                        'view'   => 'banners.view',
                        'create' => 'banners.create',
                        'update' => 'banners.update',
                        'delete' => 'banners.delete',
                    ]
                ],
                'blog' => [
                    'name' => 'Blog Posts & News (သတင်းနှင့် ဆောင်းပါးများ)',
                    'desc' => 'Tech guides, news articles and promotions blog',
                    'permissions' => [
                        'view'   => 'blog.view',
                        'create' => 'blog.create',
                        'update' => 'blog.update',
                        'delete' => 'blog.delete',
                    ]
                ],
                'glass_finder' => [
                    'name' => 'Glass Finder (မှန်ကပ် ရှာဖွေမှု)',
                    'desc' => 'Screen protector compatibility database for phone models',
                    'permissions' => [
                        'view'   => 'glass_finder.view',
                        'create' => 'glass_finder.create',
                        'update' => 'glass_finder.update',
                        'delete' => 'glass_finder.delete',
                    ]
                ],
                'web_push' => [
                    'name' => 'Web Push Notifications (Web Push အသိပေးချက်)',
                    'desc' => 'Send push notifications to subscriber browsers',
                    'permissions' => [
                        'view'   => 'web_push.view',
                        'create' => 'web_push.create',
                        'update' => 'web_push.update',
                        'delete' => 'web_push.delete',
                    ]
                ],
                'pages' => [
                    'name' => 'Custom Storefront Pages (ဝဘ်ဆိုင် စာမျက်နှာများ)',
                    'desc' => 'Manage custom informational pages and policy documents',
                    'permissions' => [
                        'view'   => 'pages.view',
                        'create' => 'pages.create',
                        'update' => 'pages.update',
                        'delete' => 'pages.delete',
                        'export' => 'pages.export',
                    ]
                ],
                'navigation' => [
                    'name' => 'Storefront Navigation (ဝဘ်ဆိုင် မီနူးစီမံမှု)',
                    'desc' => 'Configure storefront header menus, mobile drawer and footer links',
                    'permissions' => [
                        'view'   => 'navigation.view',
                        'create' => 'navigation.create',
                        'update' => 'navigation.update',
                        'delete' => 'navigation.delete',
                        'export' => 'navigation.export',
                    ]
                ],
            ]
        ],
        'customers' => [
            'label' => 'Customers & Receivables (ဖောက်သည်နှင့် အကြွေး)',
            'icon'  => '👥',
            'color' => '#0d9488',
            'modules' => [
                'customers' => [
                    'name' => 'Customer Directory (ဖောက်သည် စာရင်း)',
                    'desc' => 'Customer contact profiles, history & address book',
                    'permissions' => [
                        'view'   => 'customers.view',
                        'create' => 'customers.create',
                        'update' => 'customers.update',
                        'delete' => 'customers.delete',
                        'export' => 'customers.export',
                    ]
                ],
                'receivables' => [
                    'name' => 'Accounts Receivable (ဖောက်သည် ရရန်ရှိ/အကြွေး)',
                    'desc' => 'Credit ledger, payment collection & debt receipts',
                    'permissions' => [
                        'view'   => 'receivables.view',
                        'create' => 'receivables.create',
                        'update' => 'receivables.update',
                        'delete' => 'receivables.delete',
                    ]
                ],
                'wholesale' => [
                    'name' => 'Wholesale Applications (လက်ကား လျှောက်လွှာများ)',
                    'desc' => 'Approve/reject B2B wholesale customer accounts',
                    'permissions' => [
                        'view'   => 'wholesale.view',
                        'update' => 'wholesale.update',
                        'delete' => 'wholesale.delete',
                    ]
                ],
                'membership' => [
                    'name' => 'VIP Membership & Loyalty (VIP အသင်းဝင် စနစ်)',
                    'desc' => 'Customer tiers, loyalty reward points & benefits',
                    'permissions' => [
                        'view'   => 'membership.view',
                        'create' => 'membership.create',
                        'update' => 'membership.update',
                        'delete' => 'membership.delete',
                    ]
                ],
            ]
        ],
        'service' => [
            'label' => 'Service & Repair Center (ပစ္စည်း ပြင်ဆင်ရေး)',
            'icon'  => '🔧',
            'color' => '#6366f1',
            'modules' => [
                'repairs' => [
                    'name' => 'Repair Center & Jobs (ပြင်ဆင်ရေး ဂျော့ခ်ျများ)',
                    'desc' => 'Intake, diagnostics, technician repair jobs & delivery',
                    'permissions' => [
                        'view'   => 'repairs.view',
                        'create' => 'repairs.create',
                        'update' => 'repairs.update',
                        'delete' => 'repairs.delete',
                    ]
                ],
                'spare_parts' => [
                    'name' => 'Spare Parts Catalog (အပိုပစ္စည်း စာရင်း)',
                    'desc' => 'Service parts inventory, labor costs & estimation',
                    'permissions' => [
                        'view'   => 'spare_parts.view',
                        'create' => 'spare_parts.create',
                        'update' => 'spare_parts.update',
                        'delete' => 'spare_parts.delete',
                    ]
                ],
                'service_settings' => [
                    'name' => 'Service Settings (ပြင်ဆင်ရေး ဆက်တင်များ)',
                    'desc' => 'Job status workflows, terms & print layouts',
                    'permissions' => [
                        'view'   => 'service_settings.view',
                        'create' => 'service_settings.create',
                        'update' => 'service_settings.update',
                        'delete' => 'service_settings.delete',
                    ]
                ],
            ]
        ],
        'finance' => [
            'label' => 'Finance & Accounting (ငွေစာရင်းနှင့် အသုံးစရိတ်)',
            'icon'  => '💵',
            'color' => '#16a34a',
            'modules' => [
                'profit_loss' => [
                    'name' => 'Profit & Loss Statements (အရှုံး/အမြတ် စာရင်း)',
                    'desc' => 'Monthly / period gross profit and net income report',
                    'permissions' => [
                        'view'   => 'profit_loss.view',
                        'export' => 'profit_loss.export',
                    ]
                ],
                'expenses' => [
                    'name' => 'Shop Expenses (ဆိုင်သုံးစရိတ်များ)',
                    'desc' => 'Record, edit, approve and attach expense receipts',
                    'permissions' => [
                        'view'   => 'expenses.view',
                        'create' => 'expenses.create',
                        'update' => 'expenses.update',
                        'delete' => 'expenses.delete',
                    ]
                ],
                'expense_categories' => [
                    'name' => 'Expense Categories (သုံးစရိတ် အမျိုးအစားများ)',
                    'desc' => 'Expense type classification (Utilities, Rent, Salaries)',
                    'permissions' => [
                        'view'   => 'expense_categories.view',
                        'create' => 'expense_categories.create',
                        'update' => 'expense_categories.update',
                        'delete' => 'expense_categories.delete',
                    ]
                ],
                'transactions' => [
                    'name' => 'Cash & Bank Accounts (ငွေစာရင်းနှင့် ဘဏ်အကောင့်များ)',
                    'desc' => 'Cash register vaults, KPay, WavePay, bank transfers',
                    'permissions' => [
                        'view'   => 'transactions.view',
                        'create' => 'transactions.create',
                        'update' => 'transactions.update',
                        'delete' => 'transactions.delete',
                    ]
                ],
            ]
        ],
        'reports' => [
            'label' => 'Reports & Deep Analytics (အစီရင်ခံစာများ)',
            'icon'  => '📊',
            'color' => '#0891b2',
            'modules' => [
                'reports_sales' => [
                    'name' => 'POS Sales Report (အရောင်း အစီရင်ခံစာ)',
                    'desc' => 'Daily, monthly, payment method breakdown',
                    'permissions' => [
                        'view'   => 'reports_sales.view',
                        'export' => 'reports_sales.export',
                    ]
                ],
                'sales_analytics' => [
                    'name' => 'Sales Analytics & Charts (အရောင်း စာရင်းဇယား)',
                    'desc' => 'Deep product sales trends, top sellers & margin curves',
                    'permissions' => [
                        'view'   => 'sales_analytics.view',
                        'export' => 'sales_analytics.export',
                    ]
                ],
                'reports_cash' => [
                    'name' => 'Cash Drawer Shift Report (ငွေအံဆွဲ အစီရင်ခံစာ)',
                    'desc' => 'Cash in / cash out audit, cash float tracking',
                    'permissions' => [
                        'view'   => 'reports_cash.view',
                        'export' => 'reports_cash.export',
                    ]
                ],
                'inventory_valuation' => [
                    'name' => 'Inventory Asset Valuation (စတော့တန်ဖိုး စာရင်း)',
                    'desc' => 'Stock valuation by FIFO / moving average cost',
                    'permissions' => [
                        'view'   => 'inventory_valuation.view',
                        'export' => 'inventory_valuation.export',
                    ]
                ],
                'debt_aging' => [
                    'name' => 'Customer Debt Aging (အကြွေးသက်တမ်း စာရင်း)',
                    'desc' => 'Overdue aging brackets (0-30, 31-60, 61-90, 90+ days)',
                    'permissions' => [
                        'view'   => 'debt_aging.view',
                        'export' => 'debt_aging.export',
                    ]
                ],
                'reports_services' => [
                    'name' => 'Service & Repair Report (ပြင်ဆင်ရေး အစီရင်ခံစာ)',
                    'desc' => 'Technician performance, parts usage & repair profit',
                    'permissions' => [
                        'view'   => 'reports_services.view',
                        'export' => 'reports_services.export',
                    ]
                ],
            ]
        ],
        'security_setup' => [
            'label' => 'Security, System & Settings (လုံခြုံရေးနှင့် စနစ်)',
            'icon'  => '🛡️',
            'color' => '#e11d48',
            'modules' => [
                'roles' => [
                    'name' => 'Staff Roles & Permissions (ဝန်ထမ်း ရာထူးများ)',
                    'desc' => 'Configure granular access matrices and assign staff',
                    'permissions' => [
                        'view'   => 'roles.view',
                        'create' => 'roles.create',
                        'update' => 'roles.update',
                        'delete' => 'roles.delete',
                        'export' => 'roles.export',
                    ]
                ],
                'audit_logs' => [
                    'name' => 'System Audit Trail Logs (လုပ်ဆောင်ချက် မှတ်တမ်း)',
                    'desc' => 'Track admin actions, price edits, deletes & IP logs',
                    'permissions' => [
                        'view'   => 'audit_logs.view',
                        'export' => 'audit_logs.export',
                    ]
                ],
                'alerts' => [
                    'name' => 'System Alert Center (စနစ် သတိပေးချက်များ)',
                    'desc' => 'Low stock notifications, overdue debt alerts & broadcast',
                    'permissions' => [
                        'view'   => 'alerts.view',
                        'create' => 'alerts.create',
                        'update' => 'alerts.update',
                        'delete' => 'alerts.delete',
                    ]
                ],
                'database' => [
                    'name' => 'Database Tools & Optimizer (ဒေတာဘေ့စ် စီမံခန့်ခွဲမှု)',
                    'desc' => 'Table optimization, vacuum, indexes & integrity check',
                    'permissions' => [
                        'view'   => 'database.view',
                        'update' => 'database.update',
                    ]
                ],
                'backups' => [
                    'name' => 'Backup & Restore (ဒေတာ Backup သိမ်းဆည်းမှု)',
                    'desc' => 'Automated snapshot creation & secure SQL downloads',
                    'permissions' => [
                        'view'   => 'backups.view',
                        'create' => 'backups.create',
                        'delete' => 'backups.delete',
                    ]
                ],
                'settings' => [
                    'name' => 'Store Settings & Vouchers (ဆိုင် အချက်အလက်နှင့် ဆက်တင်)',
                    'desc' => 'Store profile, receipt header/footer, thermal printers',
                    'permissions' => [
                        'view'   => 'settings.view',
                        'update' => 'settings.update',
                        'delete' => 'settings.delete',
                    ]
                ],
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
     * Get a collection of all registered permission keys.
     */
    public static function allPermissionKeys(): \Illuminate\Support\Collection
    {
        $keys = collect();
        foreach (static::PERMISSION_GROUPS as $group) {
            foreach ($group['modules'] as $module) {
                foreach ($module['permissions'] as $permKey) {
                    $keys->push($permKey);
                }
            }
        }
        return $keys->unique()->values();
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
     * Seed or sync default system roles for a store.
     */
    public static function bootstrapDefaultRoles(Store $store): void
    {
        // 1. Store Owner / ဆိုင်ပိုင်ရှင် (All Permissions & Owner Control)
        static::updateOrCreate(
            ['store_id' => $store->id, 'slug' => 'store_owner'],
            [
                'name'        => 'Store Owner / ဆိုင်ပိုင်ရှင်',
                'description' => 'Full store administrative control, security roles delegation, and owner-only settings.',
                'color'       => '#4f46e5', // Indigo
                'permissions' => ['*'],
                'is_system'   => true,
                'is_active'   => true,
            ]
        );

        // 2. Store Manager / ဆိုင်မန်နေဂျာ (Operations & Daily Management)
        static::updateOrCreate(
            ['store_id' => $store->id, 'slug' => 'store_manager'],
            [
                'name'        => 'Store Manager / ဆိုင်မန်နေဂျာ',
                'description' => 'Daily store operations, inventory management, counter POS, customer debt, and sales reports.',
                'color'       => '#0284c7', // Sky blue
                'permissions' => [
                    'pos_sales.view',
                    'pos_sales.create',
                    'pos_sales.update',
                    'pos_sales.edit',
                    'pos_closing.view',
                    'pos_closing.create',
                    'pos_closing.update',
                    'pos_closing.edit',
                    'pos_returns.view',
                    'pos_returns.create',
                    'pos_returns.update',
                    'pos_returns.edit',
                    'pos_buyback.view',
                    'pos_buyback.create',
                    'pos_buyback.update',
                    'pos_buyback.edit',
                    'pos_eload.view',
                    'pos_eload.create',
                    'pos_eload.update',
                    'pos_eload.edit',
                    'master_data.view',
                    'master_data.create',
                    'master_data.update',
                    'master_data.edit',
                    'products.view',
                    'products.create',
                    'products.update',
                    'products.edit',
                    'stock_balance.view',
                    'stock_ledger.view',
                    'stock_count.view',
                    'stock_count.create',
                    'stock_count.update',
                    'stock_count.edit',
                    'stock_adjustments.view',
                    'stock_adjustments.create',
                    'stock_adjustments.update',
                    'stock_adjustments.edit',
                    'transfers.view',
                    'transfers.create',
                    'transfers.update',
                    'transfers.edit',
                    'warehouses.view',
                    'warehouses.create',
                    'warehouses.update',
                    'warehouses.edit',
                    'barcode.view',
                    'barcode.create',
                    'barcode.update',
                    'barcode.edit',
                    'purchases.view',
                    'purchases.create',
                    'purchases.update',
                    'purchases.edit',
                    'purchase_returns.view',
                    'purchase_returns.create',
                    'purchase_returns.update',
                    'purchase_returns.edit',
                    'suppliers.view',
                    'suppliers.create',
                    'suppliers.update',
                    'suppliers.edit',
                    'orders.view',
                    'orders.create',
                    'orders.update',
                    'orders.edit',
                    'ecommerce_orders.view',
                    'ecommerce_orders.create',
                    'ecommerce_orders.update',
                    'ecommerce_orders.edit',
                    'flash_sales.view',
                    'coupons.view',
                    'promotions.view',
                    'promotions.create',
                    'promotions.update',
                    'promotions.edit',
                    'shipping_rates.view',
                    'storefront_design.view',
                    'customers.view',
                    'customers.create',
                    'customers.update',
                    'customers.edit',
                    'membership_tiers.view',
                    'membership_tiers.create',
                    'membership_tiers.update',
                    'membership_tiers.edit',
                    'loyalty.view',
                    'loyalty.edit',
                    'customer_groups.view',
                    'repairs.view',
                    'repairs.create',
                    'repairs.update',
                    'repairs.edit',
                    'spare_parts.view',
                    'spare_parts.create',
                    'spare_parts.update',
                    'spare_parts.edit',
                    'expenses.view',
                    'expenses.create',
                    'expenses.update',
                    'expenses.edit',
                    'receivables.view',
                    'receivables.create',
                    'receivables.update',
                    'receivables.edit',
                    'payables.view',
                    'payables.create',
                    'payables.update',
                    'payables.edit',
                    'profit_loss.view',
                    'reports_sales.view',
                    'sales_analytics.view',
                    'reports_cash.view',
                    'inventory_valuation.view',
                    'debt_aging.view',
                    'reports_services.view',
                    'alerts.view',
                    'settings.view',
                ],
                'is_system'   => true,
                'is_active'   => true,
            ]
        );

        // 3. Senior Cashier / Counter Sales
        static::updateOrCreate(
            ['store_id' => $store->id, 'slug' => 'cashier'],
            [
                'name'        => 'Cashier / Sales Staff',
                'description' => 'Daily counter sales, open cash drawer, customer payments, and receipts.',
                'color'       => '#10b981', // Emerald
                'permissions' => [
                    'pos_sales.view',
                    'pos_sales.create',
                    'pos_sales.update',
                    'pos_sales.edit',
                    'pos_closing.view',
                    'pos_closing.create',
                    'pos_closing.update',
                    'pos_closing.edit',
                    'pos_returns.view',
                    'pos_returns.create',
                    'pos_returns.update',
                    'pos_returns.edit',
                    'pos_buyback.view',
                    'pos_buyback.create',
                    'pos_buyback.update',
                    'pos_buyback.edit',
                    'products.view',
                    'customers.view',
                    'customers.create',
                    'customers.update',
                    'customers.edit',
                    'reports_sales.view',
                ],
                'is_system'   => true,
                'is_active'   => true,
            ]
        );

        // 4. Accountant / Financial Auditor
        static::updateOrCreate(
            ['store_id' => $store->id, 'slug' => 'accountant'],
            [
                'name'        => 'Accountant / စာရင်းကိုင်',
                'description' => 'Manages Profit & Loss, Expenses, Banking transactions, Debt Aging and Inventory Valuations.',
                'color'       => '#8b5cf6', // Violet
                'permissions' => [
                    'products.view',
                    'stock_balance.view',
                    'stock_ledger.view',
                    'purchases.view',
                    'payables.view',
                    'payables.create',
                    'payables.update',
                    'payables.edit',
                    'customers.view',
                    'receivables.view',
                    'receivables.create',
                    'receivables.update',
                    'receivables.edit',
                    'profit_loss.view',
                    'expenses.view',
                    'expenses.create',
                    'expenses.update',
                    'expenses.edit',
                    'expenses.delete',
                    'transactions.view',
                    'transactions.create',
                    'transactions.update',
                    'transactions.edit',
                    'reports_sales.view',
                    'sales_analytics.view',
                    'reports_cash.view',
                    'inventory_valuation.view',
                    'debt_aging.view',
                ],
                'is_system'   => true,
                'is_active'   => true,
            ]
        );

        // 5. Service Technician / Repair Engineer
        static::updateOrCreate(
            ['store_id' => $store->id, 'slug' => 'technician'],
            [
                'name'        => 'Technician / ဝန်ဆောင်မှု ပညာရှင်',
                'description' => 'Device inspection, spare parts estimation, diagnosis, repair job status updates.',
                'color'       => '#f59e0b', // Amber
                'permissions' => [
                    'repairs.view',
                    'repairs.create',
                    'repairs.update',
                    'repairs.edit',
                    'spare_parts.view',
                    'spare_parts.create',
                    'spare_parts.update',
                    'spare_parts.edit',
                    'products.view',
                    'reports_services.view',
                ],
                'is_system'   => true,
                'is_active'   => true,
            ]
        );

        // 6. Stock Keeper / Warehouse Supervisor
        static::updateOrCreate(
            ['store_id' => $store->id, 'slug' => 'stock_keeper'],
            [
                'name'        => 'Stock Keeper / စတော့မှူး',
                'description' => 'Receiving shipments, warehouse stock adjustments, branch transfers, and stock counts.',
                'color'       => '#06b6d4', // Cyan
                'permissions' => [
                    'products.view',
                    'products.create',
                    'products.update',
                    'products.edit',
                    'stock_ledger.view',
                    'stock_balance.view',
                    'stock_count.view',
                    'stock_count.create',
                    'stock_count.update',
                    'stock_count.edit',
                    'stock_adjustments.view',
                    'stock_adjustments.create',
                    'stock_adjustments.update',
                    'stock_adjustments.edit',
                    'stock_reconciliation.view',
                    'transfers.view',
                    'transfers.create',
                    'transfers.update',
                    'transfers.edit',
                    'warehouses.view',
                    'warehouses.create',
                    'warehouses.update',
                    'warehouses.edit',
                    'barcode.view',
                    'barcode.create',
                    'barcode.update',
                    'barcode.edit',
                ],
                'is_system'   => true,
                'is_active'   => true,
            ]
        );
    }
}
