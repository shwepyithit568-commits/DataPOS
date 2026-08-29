<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffRole;
use App\Models\Store;
use App\Models\User;
use App\Support\AdminListReturn;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    private const GLOBAL_ROLES = ['customer', 'platform_owner'];

    private const STORE_ROLES = ['store_owner', 'store_manager', 'staff', 'wholesale_customer', 'retail_customer'];

    private const MEMBERSHIP_STATUSES = ['active', 'pending', 'suspended'];

    public function index(Request $request, StoreContext $context): View
    {
        $store = $this->resolveStore($context);

        // Ensure default staff roles exist
        StaffRole::bootstrapDefaultRoles($store);

        $query = User::query()
            ->with(['stores' => fn ($query) => $query->where('stores.id', $store->id)])
            ->where(function ($q) use ($store) {
                $q->whereHas('stores', fn ($storeQuery) => $storeQuery->where('stores.id', $store->id))
                  ->orWhere('role', 'platform_owner');
            });

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function ($nested) use ($search) {
                $nested->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $role = $request->string('role')->toString();
            if ($role === 'platform_owner') {
                $query->where('role', 'platform_owner');
            } elseif (in_array($role, self::STORE_ROLES, true)) {
                $query->whereHas('stores', fn ($storeQuery) => $storeQuery
                    ->where('stores.id', $store->id)
                    ->wherePivot('role', $role));
            }
        }

        if ($request->filled('staff_role_id')) {
            $staffRoleId = (int) $request->input('staff_role_id');
            $query->whereHas('stores', fn ($storeQuery) => $storeQuery
                ->where('stores.id', $store->id)
                ->wherePivot('staff_role_id', $staffRoleId));
        }

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            if (in_array($status, self::MEMBERSHIP_STATUSES, true)) {
                $query->whereHas('stores', fn ($storeQuery) => $storeQuery
                    ->where('stores.id', $store->id)
                    ->wherePivot('status', $status));
            }
        }

        $tab = $request->query('tab', 'all');
        if ($tab === 'staff') {
            $query->whereHas('stores', fn ($storeQuery) => $storeQuery
                ->where('stores.id', $store->id)
                ->wherePivotIn('role', ['store_owner', 'store_manager', 'staff']));
        } elseif ($tab === 'customers') {
            $query->whereHas('stores', fn ($storeQuery) => $storeQuery
                ->where('stores.id', $store->id)
                ->wherePivotIn('role', ['retail_customer', 'wholesale_customer']));
        } elseif ($tab === 'suspended') {
            $query->whereHas('stores', fn ($storeQuery) => $storeQuery
                ->where('stores.id', $store->id)
                ->wherePivot('status', 'suspended'));
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        // Load all active and available staff role templates for this store
        $staffRoles = StaffRole::where('store_id', $store->id)->where('is_active', true)->orderBy('name')->get();
        $allStaffRolesMap = StaffRole::where('store_id', $store->id)->get()->keyBy('id');

        // Calculate KPI metrics
        $totalUsers = DB::table('store_user')->where('store_id', $store->id)->count();
        $staffCount = DB::table('store_user')->where('store_id', $store->id)->whereIn('role', ['store_owner', 'store_manager', 'staff'])->count();
        $customerCount = DB::table('store_user')->where('store_id', $store->id)->whereIn('role', ['retail_customer', 'wholesale_customer'])->count();
        $suspendedCount = DB::table('store_user')->where('store_id', $store->id)->where('status', 'suspended')->count();

        $metrics = [
            'total_users'     => $totalUsers,
            'staff_count'     => $staffCount,
            'customer_count'  => $customerCount,
            'suspended_count' => $suspendedCount,
        ];

        $availableRoles = auth()->user()?->isPlatformOwner()
            ? ['platform_owner', ...self::STORE_ROLES]
            : self::STORE_ROLES;

        AdminListReturn::capture($request, 'admin_users_return');

        return view('admin.users.index', [
            'store'            => $store,
            'users'            => $users,
            'roles'            => $availableRoles,
            'statuses'         => self::MEMBERSHIP_STATUSES,
            'staffRoles'       => $staffRoles,
            'allStaffRolesMap' => $allStaffRolesMap,
            'metrics'          => $metrics,
            'currentTab'       => $tab,
        ]);
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $this->resolveStore($context);

        $phone = preg_replace('/[^0-9]/', '', (string) $request->input('phone'));
        $request->merge(['phone' => $phone]);

        $existingUser = User::where('phone', $phone)->first();

        if ($existingUser && $existingUser->stores()->where('stores.id', $store->id)->exists()) {
            return back()->withInput()->withErrors(['phone' => 'ဤဖုန်းနံပါတ်ဖြင့် အကောင့်သည် ဤဆိုင်တွင် စာရင်းသွင်းပြီးသား ဖြစ်နေပါသည်။ (User with this phone is already enrolled in this store.)']);
        }

        $validated = $this->validatePayload($request, $existingUser, $store);

        DB::transaction(function () use ($validated, $store, $existingUser) {
            if ($existingUser) {
                $user = $existingUser;
                $user->name = $validated['name'] ?: $user->name;
                if (! empty($validated['email'])) {
                    $user->email = $validated['email'];
                }
                if (! empty($validated['password'])) {
                    $user->password = Hash::make($validated['password']);
                }
                if (! empty($validated['pos_pin'])) {
                    $user->pos_pin = $validated['pos_pin'];
                }
                $user->save();
            } else {
                $user = User::create([
                    'name'     => $validated['name'],
                    'phone'    => $validated['phone'],
                    'email'    => $validated['email'] ?? null,
                    'password' => Hash::make($validated['password'] ?? 'password'),
                    'role'     => (auth()->user()?->isPlatformOwner() && ($validated['role'] ?? '') === 'platform_owner') ? 'platform_owner' : 'customer',
                    'pos_pin'  => $validated['pos_pin'] ?? null,
                ]);
            }

            if ($user->role !== 'platform_owner') {
                $isStaff = in_array($validated['role'], ['store_owner', 'store_manager', 'staff'], true);
                $user->stores()->syncWithoutDetaching([
                    $store->id => [
                        'role'          => $validated['role'],
                        'staff_role_id' => $isStaff ? ($validated['staff_role_id'] ?? null) : null,
                        'status'        => $validated['status'] ?? 'active',
                    ],
                ]);
            }
        });

        return redirect(AdminListReturn::resolve('admin_users_return', route('store.admin.users.index', ['store_slug' => $store->slug])))
            ->with('success', 'ဝန်ထမ်း/အသုံးပြုသူ အကောင့် အောင်မြင်စွာ ထည့်သွင်းပြီးပါပြီ။ (User enrolled successfully.)');
    }

    public function edit(string $store_slug, User $user, StoreContext $context): View
    {
        $store = $this->resolveStore($context);
        $user->load(['stores' => fn ($query) => $query->where('stores.id', $store->id)]);

        $staffRoles = StaffRole::where('store_id', $store->id)->where('is_active', true)->orderBy('name')->get();
        $availableRoles = auth()->user()?->isPlatformOwner()
            ? ['platform_owner', ...self::STORE_ROLES]
            : self::STORE_ROLES;

        $returnTo = AdminListReturn::peek('admin_users_return', route('store.admin.users.index', ['store_slug' => $store->slug]));

        return view('admin.users.edit', [
            'store'          => $store,
            'managedUser'    => $user,
            'membership'     => $user->stores->first()?->pivot,
            'roles'          => $availableRoles,
            'statuses'       => self::MEMBERSHIP_STATUSES,
            'staffRoles'     => $staffRoles,
            'returnTo'       => $returnTo,
        ]);
    }

    public function update(Request $request, string $store_slug, User $user, StoreContext $context): RedirectResponse
    {
        $store = $this->resolveStore($context);
        $validated = $this->validatePayload($request, $user, $store);

        DB::transaction(function () use ($validated, $user, $store) {
            $user->fill([
                'name'  => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
            ]);

            if ($user->id !== auth()->id() && auth()->user()?->isPlatformOwner()) {
                $user->role = $validated['role'] === 'platform_owner' ? 'platform_owner' : 'customer';
            }

            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            if (array_key_exists('pos_pin', $validated)) {
                $user->pos_pin = $validated['pos_pin'] !== null && $validated['pos_pin'] !== ''
                    ? $validated['pos_pin']
                    : null;
            }

            $user->save();

            if ($user->role === 'platform_owner') {
                $user->stores()->detach($store->id);
                return;
            }

            $isStaff = in_array($validated['role'], ['store_owner', 'store_manager', 'staff'], true);
            $user->stores()->syncWithoutDetaching([
                $store->id => [
                    'role'          => $validated['role'],
                    'staff_role_id' => $isStaff ? ($validated['staff_role_id'] ?? null) : null,
                    'status'        => $validated['status'] ?? 'active',
                ],
            ]);
        });

        return redirect(AdminListReturn::resolve('admin_users_return', route('store.admin.users.index', ['store_slug' => $store->slug])))
            ->with('success', 'အသုံးပြုသူ အချက်အလက်များ အောင်မြင်စွာ ပြင်ဆင်ပြီးပါပြီ။ (User updated successfully.)');
    }

    public function destroy(string $store_slug, User $user, StoreContext $context): RedirectResponse
    {
        $store = $this->resolveStore($context);

        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'မိမိကိုယ်ပိုင် အကောင့်အား ဖျက်ခွင့်/ဖယ်ရှားခွင့် မရှိပါ။ (You cannot remove your own account.)']);
        }

        if ($user->isPlatformOwner() && ! auth()->user()?->isPlatformOwner()) {
            return back()->withErrors(['user' => 'Platform Owner အကောင့်အား ဖယ်ရှားခွင့်မရှိပါ။ (Platform owner accounts cannot be removed.)']);
        }

        // Store Owner accounts cannot be removed by Store Managers
        if ($user->stores()->where('stores.id', $store->id)->wherePivot('role', 'store_owner')->exists() && ! auth()->user()?->isStoreOwner($store->id)) {
            return back()->withErrors(['user' => 'ဆိုင်ပိုင်ရှင် (Store Owner) အကောင့်အား ဖယ်ရှားခွင့်မရှိပါ။ (Store Owner accounts cannot be removed.)']);
        }

        // Detach user membership from this specific store
        $user->stores()->detach($store->id);

        return redirect(AdminListReturn::resolve('admin_users_return', route('store.admin.users.index', ['store_slug' => $store->slug])))
            ->with('success', 'ဝန်ထမ်း/အသုံးပြုသူအား ဤဆိုင်စာရင်းမှ အောင်မြင်စွာ ဖယ်ရှားပြီးပါပြီ။ (User removed from store successfully.)');
    }

    public function suspend(string $store_slug, User $user, StoreContext $context): RedirectResponse
    {
        $store = $this->resolveStore($context);

        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'မိမိကိုယ်ပိုင် အကောင့်အား ပိတ်ပင်ခွင့်မရှိပါ။ (You cannot suspend your own account.)']);
        }

        if ($user->isPlatformOwner()) {
            return back()->withErrors(['user' => 'Platform owner accounts cannot be suspended from a store page.']);
        }

        $currentMembership = $user->stores()->where('stores.id', $store->id)->first()?->pivot;
        $newStatus = ($currentMembership && $currentMembership->status === 'suspended') ? 'active' : 'suspended';

        $user->stores()->syncWithoutDetaching([
            $store->id => ['status' => $newStatus],
        ]);

        $message = $newStatus === 'active'
            ? 'အသုံးပြုသူ အကောင့်အား ပြန်လည် အသုံးပြုခွင့် ပေးလိုက်ပါပြီ။ (Account activated.)'
            : 'အသုံးပြုသူ အကောင့်အား ယာယီ ပိတ်ပင်လိုက်ပါပြီ။ (Account suspended.)';

        return back()->with('success', $message);
    }

    private function validatePayload(Request $request, ?User $user = null, ?Store $store = null): array
    {
        $passwordRules = [Password::min(6)];

        $allowedRoles = auth()->user()?->isPlatformOwner()
            ? ['platform_owner', ...self::STORE_ROLES]
            : self::STORE_ROLES;

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'regex:/^09[0-9]{7,12}$/',
                Rule::unique('users', 'phone')->ignore($user),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
            ],
            'role' => [
                'required',
                'string',
                Rule::in($allowedRoles),
                function ($attribute, $value, $fail) use ($user) {
                    if ($user && $user->id === auth()->id() && $value !== 'platform_owner' && $user->isPlatformOwner()) {
                        $fail('You cannot remove your own platform owner role.');
                    }
                },
            ],
            'staff_role_id' => [
                'nullable',
                'integer',
                $store ? Rule::exists('staff_roles', 'id')->where('store_id', $store->id) : 'nullable',
            ],
            'status' => [
                Rule::requiredIf(fn () => $request->input('role') !== 'platform_owner'),
                'nullable',
                'string',
                Rule::in(self::MEMBERSHIP_STATUSES),
            ],
            'password' => [
                $user ? 'nullable' : 'required',
                'confirmed',
                ...$passwordRules,
            ],
            'pos_pin' => ['nullable', 'string', 'regex:/^[0-9]{4,6}$/'],
        ]);
    }

    private function resolveStore(StoreContext $context): Store
    {
        $store = $context->getStore();

        abort_unless((bool) $store, 404, 'Store not found.');
        $user = auth()->user();
        abort_unless(
            $user && ($user->isPlatformOwner() || $user->isStoreOwner($store->id)),
            403,
            'Store owner access required.'
        );

        return $store;
    }
}
