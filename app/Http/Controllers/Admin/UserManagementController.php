<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

    private const STORE_ROLES = ['retail_customer', 'wholesale_customer', 'staff', 'store_manager'];

    private const MEMBERSHIP_STATUSES = ['active', 'pending', 'suspended'];

    public function index(Request $request, StoreContext $context): View
    {
        $store = $this->resolveStore($context);

        $users = User::query()
            ->with(['stores' => fn ($query) => $query->where('stores.id', $store->id)])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), function ($query) use ($request, $store) {
                $role = $request->string('role')->toString();

                if ($role === 'platform_owner') {
                    $query->where('role', 'platform_owner');
                    return;
                }

                if (in_array($role, self::STORE_ROLES, true)) {
                    $query->whereHas('stores', fn ($storeQuery) => $storeQuery
                        ->where('stores.id', $store->id)
                        ->wherePivot('role', $role));
                }
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Remember this filtered list URL so an edit/create round-trip can
        // return the user to the exact same search/filter state.
        AdminListReturn::capture($request, 'admin_users_return');

        return view('admin.users.index', [
            'store' => $store,
            'users' => $users,
            'roles' => ['platform_owner', ...self::STORE_ROLES],
            'statuses' => self::MEMBERSHIP_STATUSES,
        ]);
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $this->resolveStore($context);
        $validated = $this->validatePayload($request);

        DB::transaction(function () use ($validated, $store) {
            $user = User::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'] === 'platform_owner' ? 'platform_owner' : 'customer',
            ]);

            if ($validated['role'] !== 'platform_owner') {
                $user->stores()->attach($store->id, [
                    'role' => $validated['role'],
                    'status' => $validated['status'],
                ]);
            }
        });

        return redirect(AdminListReturn::resolve('admin_users_return', route('store.admin.users.index', ['store_slug' => $store->slug])))
            ->with('success', 'User account created successfully.');
    }

    public function edit(string $store_slug, User $user, StoreContext $context): View
    {
        $store = $this->resolveStore($context);
        $user->load(['stores' => fn ($query) => $query->where('stores.id', $store->id)]);

        $returnTo = AdminListReturn::peek('admin_users_return', route('store.admin.users.index', ['store_slug' => $store->slug]));

        return view('admin.users.edit', [
            'store' => $store,
            'managedUser' => $user,
            'membership' => $user->stores->first()?->pivot,
            'roles' => ['platform_owner', ...self::STORE_ROLES],
            'statuses' => self::MEMBERSHIP_STATUSES,
            'returnTo' => $returnTo,
        ]);
    }

    public function update(Request $request, string $store_slug, User $user, StoreContext $context): RedirectResponse
    {
        $store = $this->resolveStore($context);
        $validated = $this->validatePayload($request, $user);

        DB::transaction(function () use ($validated, $user, $store) {
            $user->fill([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
            ]);

            if ($user->id !== auth()->id()) {
                $user->role = $validated['role'] === 'platform_owner' ? 'platform_owner' : 'customer';
            }

            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            if ($user->role === 'platform_owner') {
                $user->stores()->detach($store->id);
                return;
            }

            $user->stores()->syncWithoutDetaching([
                $store->id => [
                    'role' => $validated['role'],
                    'status' => $validated['status'],
                ],
            ]);
        });

        return redirect(AdminListReturn::resolve('admin_users_return', route('store.admin.users.index', ['store_slug' => $store->slug])))
            ->with('success', 'User account updated successfully.');
    }

    public function suspend(string $store_slug, User $user, StoreContext $context): RedirectResponse
    {
        $store = $this->resolveStore($context);

        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'You cannot suspend your own account.']);
        }

        if ($user->isPlatformOwner()) {
            return back()->withErrors(['user' => 'Platform owner accounts cannot be suspended from a store page.']);
        }

        $user->stores()->syncWithoutDetaching([
            $store->id => ['status' => 'suspended'],
        ]);

        return back()->with('success', 'User store access suspended.');
    }

    private function validatePayload(Request $request, ?User $user = null): array
    {
        $passwordRules = [Password::min(12)->mixedCase()->numbers()->symbols()];

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
                Rule::in(['platform_owner', ...self::STORE_ROLES]),
                function ($attribute, $value, $fail) use ($user) {
                    if ($user && $user->id === auth()->id() && $value !== 'platform_owner') {
                        $fail('You cannot remove your own platform owner role.');
                    }
                },
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
        ]);
    }

    private function resolveStore(StoreContext $context): Store
    {
        $store = $context->getStore();

        abort_unless($store, 404, 'Store not found.');
        abort_unless(auth()->user()?->isPlatformOwner(), 403, 'Platform owner access required.');

        return $store;
    }
}
