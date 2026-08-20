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

        return view('auth.login', compact('setting', 'quickLoginUsers'));
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

        return \App\Models\User::select('id', 'name', 'phone')
            ->orderBy('id')
            ->get()
            ->map(fn ($u) => [
                'id'    => $u->id,
                'name'  => $u->name,
                'phone' => $u->phone,
            ])
            ->toArray();
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
