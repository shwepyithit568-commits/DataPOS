<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\SupportAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupportModeController extends Controller
{
    public function __construct(
        protected SupportAccessService $supportService
    ) {}

    /**
     * Enter support mode for a specific store.
     */
    public function enter(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'reason'   => ['required', 'string', 'min:5', 'max:255'],
        ]);

        $user = auth()->user();
        abort_unless($user && $user->role === 'platform_owner', 403, 'Only platform owners can initiate support mode sessions.');

        $store = Store::findOrFail($validated['store_id']);

        $this->supportService->startSupportSession($user, $store, $validated['reason']);

        return redirect()
            ->route('store.admin.dashboard', ['store_slug' => $store->slug])
            ->with('success', "Support Mode စတင်လိုက်ပါပြီ: {$store->name} [အကြောင်းပြချက်: {$validated['reason']}]");
    }

    /**
     * Exit active support mode session.
     */
    public function exit(): RedirectResponse
    {
        $this->supportService->exitSupportSession();

        return redirect()
            ->route('admin.stores.index')
            ->with('success', 'Support Mode မှ အောင်မြင်စွာ ထွက်ခွာပြီးပါပြီ။');
    }
}
