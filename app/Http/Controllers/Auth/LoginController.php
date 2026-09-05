<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        $store = app(\App\Services\StoreContext::class)->getStore();
        $setting = $store?->setting ?? \App\Models\StorefrontSetting::first();
        $quickLoginUsers = $this->getQuickLoginUsers();
        $quickLoginStores = $this->getQuickLoginStoreGroups();

        return view('auth.login', compact('setting', 'quickLoginUsers', 'quickLoginStores'));
    }

    /**
     * Quick-login — passwordless sign-in for local/testing/uat only.
     * Accepts a phone number, looks up the user, and logs them in.
     * BLOCKED in production and when show_quick_login is false.
     */
    public function quickLogin(Request $request): RedirectResponse
    {
        // Hard block: never in production or staging
        $env = config('app.env', 'production');
        if (in_array($env, ['production', 'staging'], true)) {
            abort(403, 'Quick login is disabled in production/staging.');
        }

        // Hard block: config must be explicitly enabled
        if (! config('app.show_quick_login')) {
            abort(403, 'Quick login is not enabled.');
        }

        $phone = $request->input('phone');
        if (! $phone) {
            return back()->withErrors(['phone' => 'Phone number required.']);
        }

        $user = \App\Models\User::where('phone', $phone)->first();
        if (! $user) {
            return back()->withErrors(['phone' => 'User not found.']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        $redirectTo = $request->input('redirect_to');
        if (! empty($redirectTo) && str_starts_with($redirectTo, '/')) {
            return redirect($redirectTo);
        }

        return redirect()->intended($this->resolveRedirectPath($user));
    }

    /**
     * Returns the list of users available for quick-login on the login page.
     * Only non-empty when the feature flag is on AND env allows it.
     */
    private function getQuickLoginUsers(): array
    {
        if (! config('app.show_quick_login')) {
            return [];
        }

        $env = config('app.env', 'production');
        if (in_array($env, ['production', 'staging'], true)) {
            return [];
        }

        return \App\Models\User::with(['stores:id,name,slug'])
            ->select('id', 'name', 'phone', 'role')
            ->orderBy('id')
            ->get()
            ->map(function ($u) {
                $isPlatformOwner = in_array($u->role, ['platform_owner', 'admin'], true);
                $pivotRoles = $u->stores->pluck('pivot.role')->unique()->all();
                $isStoreOwner = in_array('store_owner', $pivotRoles, true);
                $isStoreManager = in_array('store_manager', $pivotRoles, true);
                $isStaff = in_array('staff', $pivotRoles, true);
                $isWholesale = in_array('wholesale_customer', $pivotRoles, true);

                $category = 'customer';
                $roleLabel = 'Retail Customer (လက်လီဝယ်သူ)';
                $badgeColor = 'slate';
                $icon = '🛒';

                if ($isPlatformOwner) {
                    $category = 'super_admin';
                    $roleLabel = 'Platform Owner (Super Admin)';
                    $badgeColor = 'violet';
                    $icon = '⚡';
                } elseif ($isStoreOwner) {
                    $category = 'owner';
                    $roleLabel = 'Store Owner (ဆိုင်ပိုင်ရှင်)';
                    $badgeColor = 'amber';
                    $icon = '👑';
                } elseif ($isStoreManager) {
                    $category = 'manager';
                    $roleLabel = 'Store Manager (ဆိုင်မန်နေဂျာ)';
                    $badgeColor = 'sky';
                    $icon = '👔';
                } elseif (str_contains(strtolower($u->name), 'cashier') || str_contains($u->name, 'ငွေကိုင်') || $u->phone === '09160000003') {
                    $category = 'cashier';
                    $roleLabel = 'Cashier (အရောင်းစာရေး)';
                    $badgeColor = 'emerald';
                    $icon = '💵';
                } elseif (str_contains(strtolower($u->name), 'stock') || str_contains($u->name, 'စတော့') || $u->phone === '09100000003') {
                    $category = 'staff';
                    $roleLabel = 'Stock Keeper (စတော့မှူး)';
                    $badgeColor = 'indigo';
                    $icon = '📦';
                } elseif (str_contains(strtolower($u->name), 'accountant') || str_contains($u->name, 'စာရင်းကိုင်') || $u->phone === '09100000008') {
                    $category = 'staff';
                    $roleLabel = 'Accountant (စာရင်းကိုင်)';
                    $badgeColor = 'teal';
                    $icon = '📊';
                } elseif (str_contains(strtolower($u->name), 'tech') || str_contains($u->name, 'စက်ပြင်') || $u->phone === '09160000002') {
                    $category = 'staff';
                    $roleLabel = 'Technician (စက်ပြင်ပညာရှင်)';
                    $badgeColor = 'orange';
                    $icon = '🔧';
                } elseif ($isWholesale || $u->phone === '09100000004') {
                    $category = 'wholesale';
                    $roleLabel = 'Wholesale Buyer (လက်ကားဝယ်သူ)';
                    $badgeColor = 'purple';
                    $icon = '🏬';
                }

                return [
                    'id'          => $u->id,
                    'name'        => $u->name,
                    'phone'       => $u->phone,
                    'category'    => $category,
                    'role_label'  => $roleLabel,
                    'badge_color' => $badgeColor,
                    'icon'        => $icon,
                    'stores'      => $u->stores->pluck('name')->take(3)->toArray(),
                ];
            })
            ->toArray();
    }

    /**
     * Returns categorized store groups with their respective roles and direct dashboard routes for quick login.
     */
    private function getQuickLoginStoreGroups(): array
    {
        if (! config('app.show_quick_login')) {
            return [];
        }

        $env = config('app.env', 'production');
        if (in_array($env, ['production', 'staging'], true)) {
            return [];
        }

        $stores = \App\Models\Store::where('is_active', true)->orderBy('id')->get();
        if ($stores->isEmpty()) {
            return [];
        }

        $allUsers = \App\Models\User::with(['stores'])->get();
        $superAdmin = $allUsers->firstWhere('role', 'platform_owner') ?? $allUsers->firstWhere('phone', '09100000001');

        $groups = [];

        // 1. Active Stores Groups (Primary store first)
        foreach ($stores as $store) {
            $slug = $store->slug;
            $icon = match ($slug) {
                'diamond-stone-agri' => '🌾',
                'datapos-mobile' => '📱',
                'cctv-network-computer' => '📹',
                'mobile-sale-service' => '🔧',
                'pharmacy' => '💊',
                'si-taw-gyi-food-bar' => '🍲',
                default => '🏪',
            };

            $typeLabel = match ($slug) {
                'diamond-stone-agri' => 'စိုက်ပျိုးရေးနှင့် မြေသြဇာ',
                'datapos-mobile' => 'မိုဘိုင်းဖုန်းနှင့် ဆက်စပ်ပစ္စည်း',
                'cctv-network-computer' => 'CCTV၊ PC & Network',
                'mobile-sale-service' => 'ဖုန်းအရောင်းနှင့် စက်ပြင်',
                'pharmacy' => 'ဆေးဝါးနှင့် ကျန်းမာရေး',
                'si-taw-gyi-food-bar' => 'အစားအသောက်/စားသောက်ဆိုင်',
                default => 'အထွေထွေ အရောင်းဆိုင်',
            };

            $storeUsers = $store->users()->withPivot(['role', 'staff_role_id'])->get();

            // 1. Store Owner (strictly exclude platform_owner so actual store owner is selected)
            $owner = $storeUsers->first(fn($u) => $u->pivot->role === 'store_owner' && $u->role !== 'platform_owner')
                ?? $storeUsers->first(fn($u) => $u->pivot->role === 'store_owner')
                ?? $allUsers->firstWhere('phone', '09100000099');

            // 2. Store Manager
            $manager = $storeUsers->first(fn($u) => $u->pivot->role === 'store_manager' && (!$owner || $u->id !== $owner->id))
                ?? $allUsers->firstWhere('phone', '09100000002');

            // 3. Cashier (Front Counter Cashier / Sales Staff)
            $cashier = $storeUsers->first(fn($u) => $u->pivot->staff_role_id == 3 || str_contains(strtolower($u->name), 'cashier') || str_contains($u->name, 'ငွေကိုင်'))
                ?? $allUsers->firstWhere('phone', '09160000003');

            // 4. Stock Keeper (Inventory & Warehouse)
            $stockKeeper = $storeUsers->first(fn($u) => $u->pivot->staff_role_id == 6 || str_contains(strtolower($u->name), 'stock') || str_contains($u->name, 'စတော့'))
                ?? $allUsers->firstWhere('phone', '09100000003');

            // 5. Technician (Service & Repair Master)
            $technician = $storeUsers->first(fn($u) => $u->pivot->staff_role_id == 5 || str_contains(strtolower($u->name), 'technician') || str_contains($u->name, 'စက်ပြင်'))
                ?? $allUsers->firstWhere('phone', '09160000002');

            // 6. Accountant (Finance & Profit/Loss)
            $accountant = $storeUsers->first(fn($u) => $u->pivot->staff_role_id == 4 || str_contains(strtolower($u->name), 'accountant') || str_contains($u->name, 'စာရင်းကိုင်'))
                ?? $allUsers->firstWhere('phone', '09100000008');

            // 7. Wholesale Customer (B2B Buyer)
            $wholesale = $storeUsers->first(fn($u) => $u->pivot->role === 'wholesale_customer')
                ?? $allUsers->firstWhere('phone', '09100000004');

            // 8. Retail Customer (Public Shopper)
            $retail = $storeUsers->first(fn($u) => $u->pivot->role === 'retail_customer')
                ?? $allUsers->firstWhere('phone', '09100000006');

            $accounts = [];

            if ($owner) {
                $accounts[] = [
                    'id'          => $owner->id,
                    'name'        => $owner->name,
                    'phone'       => $owner->phone,
                    'role_title'  => 'ဆိုင်ပိုင်ရှင် (Store Owner)',
                    'role_desc'   => 'အုပ်ချုပ်ခွင့် အပြည့်အစုံ & ဆက်တင်',
                    'icon'        => '👑',
                    'color'       => 'amber',
                    'redirect_to' => '/store/' . $slug . '/admin/dashboard',
                ];
            }

            if ($manager && (!$owner || $manager->id !== $owner->id)) {
                $accounts[] = [
                    'id'          => $manager->id,
                    'name'        => $manager->name,
                    'phone'       => $manager->phone,
                    'role_title'  => 'ဆိုင်မန်နေဂျာ (Manager)',
                    'role_desc'   => 'စတော့၊ အရောင်းနှင့် နေ့စဉ်လုပ်ငန်း',
                    'icon'        => '👔',
                    'color'       => 'sky',
                    'redirect_to' => '/store/' . $slug . '/admin/dashboard',
                ];
            }

            if ($cashier) {
                $accounts[] = [
                    'id'          => $cashier->id,
                    'name'        => $cashier->name,
                    'phone'       => $cashier->phone,
                    'role_title'  => 'အရောင်းစာရေး (Cashier)',
                    'role_desc'   => 'POS ကောင်တာ အရောင်း & ဘေလ်ထုတ်',
                    'icon'        => '💵',
                    'color'       => 'emerald',
                    'redirect_to' => '/store/' . $slug . '/pos',
                ];
            }

            if ($stockKeeper) {
                $accounts[] = [
                    'id'          => $stockKeeper->id,
                    'name'        => $stockKeeper->name,
                    'phone'       => $stockKeeper->phone,
                    'role_title'  => 'စတော့မှူး (Stock Keeper)',
                    'role_desc'   => 'ကုန်ပစ္စည်းလက်ကျန် & ဂိုဒေါင်စာရင်း',
                    'icon'        => '📦',
                    'color'       => 'indigo',
                    'redirect_to' => '/store/' . $slug . '/admin/products',
                ];
            }

            if ($technician) {
                $accounts[] = [
                    'id'          => $technician->id,
                    'name'        => $technician->name,
                    'phone'       => $technician->phone,
                    'role_title'  => 'စက်ပြင်ပညာရှင် (Technician)',
                    'role_desc'   => 'စက်ပြင်နှင့် ဝန်ဆောင်မှု မှတ်တမ်း',
                    'icon'        => '🔧',
                    'color'       => 'orange',
                    'redirect_to' => '/store/' . $slug . '/admin/service-jobs',
                ];
            }

            if ($accountant) {
                $accounts[] = [
                    'id'          => $accountant->id,
                    'name'        => $accountant->name,
                    'phone'       => $accountant->phone,
                    'role_title'  => 'စာရင်းကိုင် (Accountant)',
                    'role_desc'   => 'အသုံးစရိတ်၊ အမြတ်/အရှုံး စာရင်းများ',
                    'icon'        => '📊',
                    'color'       => 'teal',
                    'redirect_to' => '/store/' . $slug . '/admin/profit-loss',
                ];
            }

            if ($wholesale) {
                $accounts[] = [
                    'id'          => $wholesale->id,
                    'name'        => $wholesale->name,
                    'phone'       => $wholesale->phone,
                    'role_title'  => 'လက်ကားဝယ်သူ (Wholesale)',
                    'role_desc'   => 'B2B လက်ကားဈေးနှုန်း & အော်ဒါ',
                    'icon'        => '🏬',
                    'color'       => 'purple',
                    'redirect_to' => '/store/' . $slug,
                ];
            }

            if ($retail) {
                $accounts[] = [
                    'id'          => $retail->id,
                    'name'        => $retail->name,
                    'phone'       => $retail->phone,
                    'role_title'  => 'လက်လီဝယ်သူ (Retail Customer)',
                    'role_desc'   => 'အွန်လိုင်းစတိုး ကုန်ပစ္စည်းဝယ်ယူမှု',
                    'icon'        => '🛒',
                    'color'       => 'slate',
                    'redirect_to' => '/store/' . $slug,
                ];
            }

            $groups[] = [
                'id'          => $store->id,
                'name'        => $store->name,
                'slug'        => $store->slug,
                'icon'        => $icon,
                'type_label'  => $typeLabel,
                'subtitle'    => $typeLabel,
                'accounts'    => $accounts,
            ];
        }

        // 2. Platform Super Admin Group (Boss Only)
        if ($superAdmin) {
            $groups[] = [
                'id'            => 'platform',
                'name'          => 'Platform Super Admin',
                'slug'          => 'platform-admin',
                'icon'          => '⚡',
                'type_label'    => 'Platform Head (Boss)',
                'subtitle'      => 'ဆိုင်ခွဲအားလုံး ထိန်းချုပ်မှု',
                'accounts'      => [
                    [
                        'id'          => $superAdmin->id,
                        'name'        => $superAdmin->name,
                        'phone'       => $superAdmin->phone,
                        'role_title'  => 'စနစ်ပိုင်ရှင် (Platform Owner)',
                        'role_desc'   => 'ဆိုင်ခွဲအားလုံး စီမံခန့်ခွဲမှု စာမျက်နှာ',
                        'icon'        => '⚡',
                        'color'       => 'violet',
                        'redirect_to' => '/admin/stores',
                    ],
                ],
            ];
        }

        return $groups;
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt(['phone' => $credentials['phone'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended($this->resolveRedirectPath(Auth::user()));
        }

        return back()->withErrors([
            'phone' => __('auth.failed'),
        ])->onlyInput('phone');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Resolve the post-login redirect path based on the user's role.
     *
     * Priority:
     *   1. platform_owner  → /admin/dashboard (global store selector)
     *   2. store_manager/staff → their store admin dashboard
     *   3. retail/wholesale customer → storefront home with store context
     *   4. fallback → / (no store context)
     */
    private function resolveRedirectPath($user): string
    {
        // 1. Platform owners go to the global admin dashboard
        if ($user->isPlatformOwner()) {
            return '/admin/dashboard';
        }

        // 2. Store managers / staff go to their store admin dashboard
        if ($user->isStoreAdmin()) {
            $store = $user->getPrimaryStore();
            if ($store) {
                return '/store/' . $store->slug . '/admin/dashboard';
            }
        }

        // 3. Customers (retail / wholesale) go to the storefront of their store
        $store = $user->getPrimaryStore();
        if ($store) {
            return '/store/' . $store->slug;
        }

        // 4. Fallback — no store membership at all
        return '/';
    }
}
