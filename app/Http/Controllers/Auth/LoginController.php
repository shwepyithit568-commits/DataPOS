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
                    $icon = '👑';
                } elseif ($isStoreManager) {
                    $category = 'manager';
                    $roleLabel = 'Store Manager (ဆိုင်မန်နေဂျာ)';
                    $badgeColor = 'sky';
                    $icon = '👔';
                } elseif (str_contains(strtolower($u->name), 'cashier') || str_contains($u->name, 'လှလှ') || $u->phone === '09222333444') {
                    $category = 'cashier';
                    $roleLabel = 'Cashier (အရောင်းစာရေး)';
                    $badgeColor = 'emerald';
                    $icon = '💵';
                } elseif (str_contains(strtolower($u->name), 'tech') || str_contains($u->name, 'မင်းမင်း') || $u->phone === '09333444555' || $isStaff) {
                    $category = 'staff';
                    $roleLabel = 'Technician / Staff (စက်ပြင်/ဝန်ထမ်း)';
                    $badgeColor = 'amber';
                    $icon = '🔧';
                } elseif ($isWholesale || str_contains($u->name, 'ဘသိန်း') || $u->phone === '09988776655') {
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

        $users = \App\Models\User::with(['stores'])->get();
        $superAdmin = $users->firstWhere('phone', '09777000111') ?? $users->firstWhere('role', 'platform_owner');

        $groups = [];

        // 1. Platform Super Admin Group (Boss Only)
        if ($superAdmin) {
            $groups[] = [
                'id'            => 'platform',
                'name'          => 'Platform Super Admin',
                'slug'          => 'platform-admin',
                'icon'          => '👑',
                'type_label'    => 'Platform Head (Boss)',
                'subtitle'      => 'ဆိုင်ခွဲအားလုံး ထိန်းချုပ်မှု',
                'accounts'      => [
                    [
                        'id'          => $superAdmin->id,
                        'name'        => $superAdmin->name,
                        'phone'       => $superAdmin->phone,
                        'role_title'  => 'Platform Owner',
                        'role_desc'   => 'Store စီမံခန့်ခွဲမှု စာမျက်နှာ',
                        'icon'        => '👑',
                        'color'       => 'violet',
                        'redirect_to' => '/admin/stores',
                    ],
                ],
            ];
        }

        // 2. Active Stores Groups
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

            // Find store-specific accounts matching phone prefixes or memberships
            $owner = match ($slug) {
                'diamond-stone-agri'    => $users->firstWhere('phone', '09130000001'),
                'datapos-mobile'        => $users->firstWhere('phone', '09150000001'),
                'cctv-network-computer' => $users->firstWhere('phone', '09170000001'),
                'mobile-sale-service'   => $users->firstWhere('phone', '09160000001'),
                'pharmacy'              => $users->firstWhere('phone', '09180000001'),
                'si-taw-gyi-food-bar'   => $users->firstWhere('phone', '09140000001'),
                default                 => null,
            } ?? $users->firstWhere('phone', '09111222333');

            $manager = match ($slug) {
                'diamond-stone-agri'    => $users->firstWhere('phone', '09130000002'),
                'datapos-mobile'        => $users->firstWhere('phone', '09150000002'),
                'cctv-network-computer' => $users->firstWhere('phone', '09170000002'),
                'mobile-sale-service'   => $users->firstWhere('phone', '09160000002'),
                'pharmacy'              => $users->firstWhere('phone', '09180000002'),
                'si-taw-gyi-food-bar'   => $users->firstWhere('phone', '09140000002'),
                default                 => null,
            } ?? $users->firstWhere('phone', '09111222333');

            $cashier = match ($slug) {
                'diamond-stone-agri'    => $users->firstWhere('phone', '09130000003'),
                'datapos-mobile'        => $users->firstWhere('phone', '09150000003'),
                'cctv-network-computer' => $users->firstWhere('phone', '09170000003'),
                'mobile-sale-service'   => $users->firstWhere('phone', '09160000003'),
                'pharmacy'              => $users->firstWhere('phone', '09180000003'),
                'si-taw-gyi-food-bar'   => $users->firstWhere('phone', '09140000003'),
                default                 => null,
            } ?? $users->firstWhere('phone', '09222333444');

            $technician = match ($slug) {
                'mobile-sale-service'   => $users->firstWhere('phone', '09160000004') ?? $users->firstWhere('phone', '09333444555'),
                'cctv-network-computer' => $users->firstWhere('phone', '09170000004') ?? $users->firstWhere('phone', '09333444555'),
                default                 => null,
            };

            $wholesale = match ($slug) {
                'diamond-stone-agri'    => $users->firstWhere('phone', '09130000004'),
                'datapos-mobile'        => $users->firstWhere('phone', '09150000004'),
                'cctv-network-computer' => $users->firstWhere('phone', '09170000005'),
                'mobile-sale-service'   => $users->firstWhere('phone', '09160000005'),
                'pharmacy'              => $users->firstWhere('phone', '09180000004'),
                default                 => null,
            } ?? ($slug !== 'si-taw-gyi-food-bar' ? $users->firstWhere('phone', '09988776655') : null);

            $retail = match ($slug) {
                'diamond-stone-agri'    => $users->firstWhere('phone', '09130000005'),
                'datapos-mobile'        => $users->firstWhere('phone', '09150000005'),
                'cctv-network-computer' => $users->firstWhere('phone', '09170000006'),
                'mobile-sale-service'   => $users->firstWhere('phone', '09160000006'),
                'pharmacy'              => $users->firstWhere('phone', '09180000005'),
                'si-taw-gyi-food-bar'   => $users->firstWhere('phone', '09140000004'),
                default                 => null,
            } ?? $users->firstWhere('phone', '09776655443');

            $accounts = [];

            if ($owner) {
                $accounts[] = [
                    'id'          => $owner->id,
                    'name'        => $owner->name,
                    'phone'       => $owner->phone,
                    'role_title'  => 'ဆိုင်ပိုင်ရှင် (Store Owner)',
                    'role_desc'   => 'Admin Dashboard',
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
                    'role_desc'   => 'Admin Dashboard',
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
                    'role_desc'   => 'POS Terminal',
                    'icon'        => '💵',
                    'color'       => 'emerald',
                    'redirect_to' => '/store/' . $slug . '/pos',
                ];
            }

            if ($technician) {
                $accounts[] = [
                    'id'          => $technician->id,
                    'name'        => $technician->name,
                    'phone'       => $technician->phone,
                    'role_title'  => 'နည်းပညာ/စက်ပြင် (Technician)',
                    'role_desc'   => 'Service Jobs',
                    'icon'        => '🔧',
                    'color'       => 'orange',
                    'redirect_to' => '/store/' . $slug . '/admin/service-jobs',
                ];
            }

            if ($wholesale) {
                $accounts[] = [
                    'id'          => $wholesale->id,
                    'name'        => $wholesale->name,
                    'phone'       => $wholesale->phone,
                    'role_title'  => 'လက်ကားဝယ်သူ (Wholesale)',
                    'role_desc'   => 'Wholesale Portal',
                    'icon'        => '🏬',
                    'color'       => 'purple',
                    'redirect_to' => '/store/' . $slug . '/wholesale',
                ];
            }

            if ($retail) {
                $accounts[] = [
                    'id'          => $retail->id,
                    'name'        => $retail->name,
                    'phone'       => $retail->phone,
                    'role_title'  => 'လက်လီဝယ်သူ (Retail)',
                    'role_desc'   => 'Storefront Shopping',
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
