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
        $setting = \App\Models\StorefrontSetting::first();

        return view('auth.login', compact('setting'));
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
