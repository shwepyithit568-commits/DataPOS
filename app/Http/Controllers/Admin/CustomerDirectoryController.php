<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\POS\Models\CustomerLedgerEntry;
use App\POS\Models\PosSale;
use App\POS\Services\CustomerDebtService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerDirectoryController extends Controller
{
    public function __construct(
        protected CustomerDebtService $debts,
    ) {}

    /**
     * List all customers (users with retail_customer / wholesale_customer
     * role in this store's store_user pivot).
     */
    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $storeRouteParams = ['store_slug' => $store->slug];

        $query = User::query()
            ->whereHas('stores', function ($q) use ($store) {
                $q->where('stores.id', $store->id)
                  ->whereIn('store_user.role', ['retail_customer', 'wholesale_customer']);
            })
            ->with(['stores' => function ($rel) use ($store) {
                $rel->where('stores.id', $store->id);
            }]);

        // Search by name, phone, or email
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Tab or Role Filter
        $tab = $request->query('tab', 'all');
        if ($tab === 'retail') {
            $query->whereHas('stores', fn ($q) => $q->where('stores.id', $store->id)->where('store_user.role', 'retail_customer'));
        } elseif ($tab === 'wholesale') {
            $query->whereHas('stores', fn ($q) => $q->where('stores.id', $store->id)->where('store_user.role', 'wholesale_customer'));
        } elseif ($tab === 'debt') {
            $debtCustomerIds = CustomerLedgerEntry::where('store_id', $store->id)
                ->groupBy('customer_id')
                ->havingRaw('SUM(amount) < 0 OR SUM(amount) > 0')
                ->pluck('customer_id');
            $query->whereIn('users.id', $debtCustomerIds);
        } elseif ($request->filled('role') && in_array($request->input('role'), ['retail_customer', 'wholesale_customer'], true)) {
            $filterRole = $request->input('role');
            $query->whereHas('stores', fn ($q) => $q->where('stores.id', $store->id)->where('store_user.role', $filterRole));
        }

        // Status Filter
        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->whereHas('stores', fn ($q) => $q->where('stores.id', $store->id)->where('store_user.status', $status));
        }

        // Sorting
        $sort = $request->input('sort', 'name_asc');
        $query = match ($sort) {
            'newest'   => $query->latest('users.created_at'),
            'oldest'   => $query->oldest('users.created_at'),
            'name_desc'=> $query->orderByDesc('users.name'),
            'phone'    => $query->orderBy('users.phone'),
            default    => $query->orderBy('users.name'),
        };

        // KPI Stats
        $baseRoleCount = fn (string $r) => DB::table('store_user')
            ->where('store_id', $store->id)
            ->where('role', $r)
            ->count();

        $totalCustomers = DB::table('store_user')
            ->where('store_id', $store->id)
            ->whereIn('role', ['retail_customer', 'wholesale_customer'])
            ->count();

        $retailCount = $baseRoleCount('retail_customer');
        $wholesaleCount = $baseRoleCount('wholesale_customer');

        // Total Outstanding Debt Calculation
        $debtBalances = CustomerLedgerEntry::where('store_id', $store->id)
            ->selectRaw('customer_id, SUM(amount) as net_balance')
            ->groupBy('customer_id')
            ->get();

        $debtCustomersCount = 0;
        $totalDebtAmount = 0.0;
        $debtMap = [];

        foreach ($debtBalances as $dbRow) {
            $bal = (float) $dbRow->net_balance;
            $debtMap[$dbRow->customer_id] = $bal;
            if ($bal > 0) { // Customer owes store
                $debtCustomersCount++;
                $totalDebtAmount += $bal;
            }
        }

        $stats = [
            'total'               => $totalCustomers,
            'retail'              => $retailCount,
            'wholesale'           => $wholesaleCount,
            'debt_customers_count'=> $debtCustomersCount,
            'total_debt_amount'   => $totalDebtAmount,
        ];

        $perPage = request('per_page') === 'all' ? 1000 : (int) request('per_page', 25);
        $customers = $query->paginate($perPage)->withQueryString();

        // Attach debt balance and sales overview to current page collection
        $customerIds = $customers->pluck('id')->toArray();
        $salesAgg = PosSale::where('store_id', $store->id)
            ->whereIn('customer_id', $customerIds)
            ->whereIn('status', ['posted', 'partially_refunded'])
            ->selectRaw('customer_id, COUNT(*) as orders_count, SUM(total) as total_spent, MAX(created_at) as last_order_at')
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $customers->getCollection()->transform(function (User $user) use ($debtMap, $salesAgg) {
            $user->debt_balance = $debtMap[$user->id] ?? 0.0;
            $salesInfo = $salesAgg->get($user->id);
            $user->orders_count = $salesInfo ? (int) $salesInfo->orders_count : 0;
            $user->total_spent = $salesInfo ? (float) $salesInfo->total_spent : 0.0;
            $user->last_order_at = $salesInfo ? $salesInfo->last_order_at : null;
            return $user;
        });

        $totalCount = $customers->total();

        return view('admin.customers.index', compact(
            'store',
            'storeRouteParams',
            'customers',
            'totalCount',
            'stats',
            'tab',
            'sort'
        ));
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => [
                'required',
                'string',
                'regex:/^09[0-9]{7,12}$/',
            ],
            'email' => 'nullable|email|max:255',
            'role'  => 'required|string|in:retail_customer,wholesale_customer',
        ]);

        $normalizedPhone = User::normalizePhone($validated['phone']);
        $existingUser = User::findByNormalizedPhone($normalizedPhone);

        DB::transaction(function () use ($validated, $existingUser, $store) {
            if ($existingUser) {
                $user = $existingUser;
                $user->update([
                    'name'  => $validated['name'],
                    'email' => $validated['email'] ?? $user->email,
                ]);
            } else {
                $user = User::create([
                    'name'     => $validated['name'],
                    'phone'    => $validated['phone'],
                    'email'    => $validated['email'] ?? null,
                    'password' => Hash::make('password123'),
                    'role'     => 'customer',
                ]);
            }

            // Attach / sync to store
            $user->stores()->syncWithoutDetaching([
                $store->id => [
                    'role'   => $validated['role'],
                    'status' => 'active',
                ],
            ]);
        });

        return back()->with('success', 'ဖောက်သည်အသစ် အောင်မြင်စွာ စာရင်းသွင်းပြီးပါပြီ။ (Customer created successfully.)');
    }

    /**
     * Update an existing customer profile.
     */
    public function update(Request $request, StoreContext $context, string $store_slug, User $customer): RedirectResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $hasMembership = $customer->stores()
            ->where('stores.id', $store->id)
            ->whereIn('store_user.role', ['retail_customer', 'wholesale_customer'])
            ->exists();

        if (! $hasMembership) {
            abort(404);
        }

        $validated = $request->validate([
            'name'   => 'required|string|max:255',
            'phone'  => [
                'required',
                'string',
                'regex:/^09[0-9]{7,12}$/',
                Rule::unique('users', 'phone')->ignore($customer->id),
            ],
            'email'  => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($customer->id),
            ],
            'role'   => 'required|string|in:retail_customer,wholesale_customer',
            'status' => 'required|string|in:active,pending,suspended',
        ]);

        DB::transaction(function () use ($validated, $customer, $store) {
            $customer->update([
                'name'  => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
            ]);

            $customer->stores()->syncWithoutDetaching([
                $store->id => [
                    'role'   => $validated['role'],
                    'status' => $validated['status'],
                ],
            ]);
        });

        return back()->with('success', 'ဖောက်သည် အချက်အလက်များ အောင်မြင်စွာ ပြင်ဆင်ပြီးပါပြီ။ (Customer updated successfully.)');
    }

    /**
     * Customer detail: profile info, recent POS orders, debt balance.
     */
    public function show(StoreContext $context, string $store_slug, User $customer): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $storeRouteParams = ['store_slug' => $store->slug];

        // Verify this customer belongs to this store
        $hasMembership = $customer->stores()
            ->where('stores.id', $store->id)
            ->whereIn('store_user.role', ['retail_customer', 'wholesale_customer'])
            ->exists();

        if (! $hasMembership) {
            abort(404);
        }

        $customer->load('stores');

        // Debt balance
        $debtBalance = $this->debts->balanceFor($store->id, $customer->id);

        // Recent POS orders
        $recentOrders = PosSale::where('store_id', $store->id)
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['posted', 'partially_refunded'])
            ->with('items')
            ->latest()
            ->limit(15)
            ->get();

        // Summary stats
        $orderStats = [
            'total_orders' => PosSale::where('store_id', $store->id)
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['posted', 'partially_refunded', 'refunded'])
                ->count(),
            'total_spent' => (float) PosSale::where('store_id', $store->id)
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['posted', 'partially_refunded'])
                ->sum('total'),
        ];

        // Membership info
        $membership = $customer->getStoreMembership($store->id);

        return view('admin.customers.show', compact(
            'store', 'storeRouteParams', 'customer',
            'debtBalance', 'recentOrders', 'orderStats', 'membership'
        ));
    }

    /**
     * Export Customers Directory to CSV.
     */
    public function exportCsv(Request $request, StoreContext $context): StreamedResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $customers = User::query()
            ->whereHas('stores', function ($q) use ($store) {
                $q->where('stores.id', $store->id)
                  ->whereIn('store_user.role', ['retail_customer', 'wholesale_customer']);
            })
            ->with(['stores' => fn ($rel) => $rel->where('stores.id', $store->id)])
            ->orderBy('name')
            ->get();

        $filename = 'customers-directory-' . $store->slug . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($store, $customers) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, ['Customer Directory', $store->name]);
            fputcsv($handle, ['Exported Date', now()->toFormattedDateString() . ' ' . now()->format('h:i A')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'Customer Name',
                'Phone',
                'Email',
                'Customer Type',
                'Store Status',
                'Debt Balance (MMK)',
                'Joined Date',
            ]);

            foreach ($customers as $c) {
                $membership = $c->stores->first()?->pivot;
                $debt = $this->debts->balanceFor($store->id, $c->id);

                fputcsv($handle, [
                    $c->name,
                    $c->phone ?? '-',
                    $c->email ?? '-',
                    $membership?->role === 'wholesale_customer' ? 'Wholesale Customer' : 'Retail Customer',
                    ucfirst($membership?->status ?? 'active'),
                    number_format($debt, 0, '.', ''),
                    $c->created_at ? $c->created_at->format('Y-m-d') : '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
