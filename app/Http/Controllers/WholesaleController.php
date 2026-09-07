<?php

namespace App\Http\Controllers;

use App\Models\WholesaleApplication;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WholesaleController extends Controller
{
    public function create(StoreContext $context): View
    {
        $store = $context->getStore();
        $user = auth()->user();

        abort_unless($store !== null, 404, 'Store not found.');

        $application = $user
            ? WholesaleApplication::where('store_id', $store->id)
                ->where('user_id', $user->id)
                ->first()
            : null;

        $setting = $store->setting;

        return view('storefront.wholesale.apply', compact('store', 'application', 'setting'));
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $user = auth()->user();

        abort_unless($store !== null, 404, 'Store not found.');
        if (!$user) {
            return redirect()->route('customer.login', ['store_slug' => $store->slug])
                ->with('error', __('messages.wholesale_login_required') ?? 'Please login first to apply for wholesale.');
        }

        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $application = WholesaleApplication::updateOrCreate(
            ['store_id' => $store->id, 'user_id' => $user->id],
            [
                'business_name' => $validated['business_name'],
                'phone' => $validated['phone'],
                'address' => $validated['address'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
            ]
        );

        // Update or create store_user membership with pending status
        $user->stores()->syncWithoutDetaching([
            $store->id => ['role' => 'wholesale_customer', 'status' => 'pending']
        ]);

        return back()->with('success', __('messages.wholesale_applied_success') ?? 'Wholesale application submitted successfully. Please wait for admin approval.');
    }
}
