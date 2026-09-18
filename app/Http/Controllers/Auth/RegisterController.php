<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\StorefrontSetting;
use App\Models\User;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        $store = app(StoreContext::class)->getStore();
        $setting = $store?->setting ?? StorefrontSetting::first();

        return view('auth.register', compact('setting'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $phone = User::normalizePhone($data['phone']);

        if ($phone === '' || strlen($phone) < 7 || strlen($phone) > 15 || !preg_match('/^\d+$/', $phone)) {
            throw ValidationException::withMessages(['phone' => __('messages.phone_invalid')]);
        }

        // Role is strictly hardcoded to 'customer' to prevent client role tampering.
        $user = User::findByNormalizedPhone($data['phone']);

        $claimed = false;

        if ($user !== null) {
            // A staff / manager / owner account can never be claimed by a shopper.
            // Anything that is not a plain customer membership counts: the old
            // list ('store_manager', 'staff') missed `store_owner`, so a store
            // owner with no orders yet was one phone number away from being
            // taken over on the storefront.
            $isStaffAccount = $user->isPlatformOwner()
                || $user->stores()
                    ->wherePivotNotIn('role', ['retail_customer', 'wholesale_customer'])
                    ->exists();

            if ($isStaffAccount) {
                throw ValidationException::withMessages(['phone' => __('messages.phone_already_registered')]);
            }

            // The account already has a password its owner chose. Registering the
            // same phone must NOT reset it: that would let anyone who knows a
            // phone number take the account over (and with it the order history,
            // favourites and loyalty points behind it).
            if ($user->password_set_at !== null) {
                throw ValidationException::withMessages(['phone' => __('messages.phone_taken_sign_in')]);
            }

            // A counter-created account (placeholder password, nobody has ever
            // signed in) may be claimed — that is how a POS customer gets online
            // access. Only while it is still empty: once it holds orders or
            // points, the shop verifies the person first.
            if ($this->accountHoldsValue($user)) {
                throw ValidationException::withMessages(['phone' => __('messages.phone_needs_staff_verification')]);
            }

            $claimed = true;
        }

        if ($user !== null) {
            $user->forceFill([
                'name' => $data['name'],
                'password' => Hash::make($data['password']),
                'password_set_at' => now(),
            ])->save();
        } else {
            $user = User::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'password_set_at' => now(),
                'role' => 'customer',
            ]);
        }

        if ($claimed) {
            \App\Models\AuditLog::write(
                storeId: app(StoreContext::class)->getStore()?->id,
                action: 'customer_account_claimed',
                entityType: 'users',
                entityId: $user->id,
                metadata: ['phone' => $user->phone, 'method' => 'storefront_registration'],
                actorId: $user->id,
            );
        }

        // Enroll as a retail customer of the store being registered at — this is
        // what makes ecommerce customers appear in that store's POS customer list.
        // (No store context, e.g. a fresh install, still allows registration.)
        $store = app(StoreContext::class)->getStore();
        if ($store !== null && ! $user->stores()->wherePivot('store_id', $store->id)->exists()) {
            $user->stores()->attach($store->id, [
                'role' => 'retail_customer',
                'status' => 'active',
            ]);
        }

        Auth::login($user);

        $request->session()->regenerate();

        // Land back on the storefront they registered at, not on whichever store
        // happens to be primary.
        return redirect($store !== null ? '/store/' . $store->slug : '/');
    }

    /**
     * Whether a counter-created account already carries something worth
     * protecting: online orders, or loyalty points/spending at any store.
     *
     * An empty account can be claimed from the storefront (that is how a POS
     * customer gets online access). Once there is value in it, only the shop can
     * hand it over — there is no SMS/Viber verification in the app yet, so a
     * phone number alone is not proof of anything.
     */
    private function accountHoldsValue(User $user): bool
    {
        if ($user->orders()->exists()) {
            return true;
        }

        return $user->stores()
            ->where(function ($query) {
                $query->where('store_user.loyalty_points', '>', 0)
                    ->orWhere('store_user.total_spent', '>', 0);
            })
            ->exists();
    }
}
