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

        if ($user !== null) {
            // A staff / manager / owner account can never be claimed by a shopper.
            $isStaffAccount = $user->isPlatformOwner()
                || $user->stores()->wherePivotIn('role', ['store_manager', 'staff'])->exists();

            if ($isStaffAccount) {
                throw ValidationException::withMessages(['phone' => __('messages.phone_already_registered')]);
            }

            // Merge instead of duplicate: the same person re-registers (e.g. an
            // account first created by a POS quick-add, which had no password).
            // One user record per phone — the store membership below is added
            // if the store being registered at isn't attached yet.
            $user->forceFill([
                'name' => $data['name'],
                'password' => Hash::make($data['password']),
            ])->save();
        } else {
            $user = User::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'role' => 'customer',
            ]);
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

        return redirect('/');
    }
}
