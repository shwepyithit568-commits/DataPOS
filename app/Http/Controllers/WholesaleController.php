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

        abort_unless($store, 404, 'Store not found.');

        $application = WholesaleApplication::where('store_id', $store->id)
            ->where('user_id', $user->id)
            ->first();

        return view('storefront.wholesale.apply', compact('store', 'application'));
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $user = auth()->user();

        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
        ]);

        $application = WholesaleApplication::updateOrCreate(
            ['store_id' => $store->id, 'user_id' => $user->id],
            [
                'business_name' => $validated['business_name'],
                'phone' => $validated['phone'],
                'address' => $validated['address'] ?? null,
                'status' => 'pending',
            ]
        );

        // Update or create store_user membership with pending status
        $user->stores()->syncWithoutDetaching([
            $store->id => ['role' => 'wholesale_customer', 'status' => 'pending']
        ]);

        return back()->with('success', 'Wholesale application submitted successfully. Please wait for admin approval.');
    }
}
